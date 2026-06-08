<?php
session_start();
if (!isset($_SESSION["id_user"])) {
    header("Location: ../index.php");
    exit;
}
require_once "../Koneksi.php";

$npm    = trim($_SESSION["id_user"]);

$periode = $_GET["periode"] ?? ($_SESSION["periode"] ?? "1");
$semester = $_GET["semester"] ?? ($_SESSION["semester"] ?? "25");

$id_sem = $semester . "-" . $periode;

//query semester
$stmt = $conn->prepare("SELECT Periode, Tahun_Akademik FROM Semester WHERE Id_Sem = ?");
$stmt->execute([$id_sem]);
$sem = $stmt->fetch(PDO::FETCH_ASSOC);
$semLabel = trim($sem['Periode'] ?? 'Ganjil') . ' ' . ($sem['Tahun_Akademik'] ?? '2025') . '/' . (($sem['Tahun_Akademik'] ?? 2025) + 1);

$msg = '';
//cek apakah request beradal dri form post dan apakah ada data selected_mk
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_mk'])) {
    $selected = array_map('trim', $_POST['selected_mk']); //ambil mata kuliah yang dicentang user
    if (!empty($selected)) { //kalau ada matkul yang dipilih
        $placeholders = implode(',', array_fill(0, count($selected), '?')); //mengguabung kode=kode matakuliah yang dipilih menjadi array of string
        $params = array_merge([trim($id_sem)], $selected); //gabungkan semester dan daftar mata kuliah
        //query untuk mengambbil id mk, nama mk, sks mk, dan jadwalnya
        $stmt2 = $conn->prepare("
            SELECT mk.Id_MK, mk.Nama AS NamaMK, mk.SKS
            FROM Matakuliah as mk
            JOIN Detail_Akademik as da ON da.Id_MK = mk.id_mk
            WHERE da.id_sem = ? AND mk.Id_MK IN ($placeholders)  AND mk.Status_Aktif = 1
        "); //arti dari ? adalah placeholder yang nantinya bisa diisi
        $stmt2->execute($params);
        $MKSelected = $stmt2->fetchAll(PDO::FETCH_ASSOC);//ambil semua jadwal matkul yang dipilih

        try {
            $conn->beginTransaction(); //muai transaction database
            $id_frs = substr($npm, -4) . $id_sem;// substring dan trim, substring dari berapa sampe berapa kalo trim langsung potong supaya jadi id frs
            $stmt2 = $conn->prepare("SELECT Id_FRS FROM FRS WHERE Id_FRS = ?"); //cek apakah frs udh ada, kalau belum insert kode frs baru
            //insert kode frs baru     
            $stmt2->execute([$id_frs]); 
            if (!$stmt2->fetch()) {
                $conn->prepare("INSERT INTO FRS (Id_FRS, NPM, Id_Sem) VALUES (?, ?, ?)")->execute([$id_frs, $npm, $id_sem]);
            }

            //hapus enroll lama apabila terjaid perubahan frs
            $conn->prepare("
                DELETE FROM Enroll 
                WHERE Id_FRS = (
                    SELECT Id_FRS 
                    FROM FRS 
                    WHERE NPM = ? AND Id_Sem = ?
                ) 
            ")->execute([$npm, $id_frs, $id_sem]);
            //inseert enroll baru
            $ins = $conn->prepare("
                INSERT INTO Enroll (NPM, Id_MK, Id_Sem, Id_FRS) 
                VALUES (?, ?, ?, (
                    SELECT Id_FRS 
                    FROM FRS 
                    WHERE NPM = ? AND Id_Sem = ?
                ))
            ");
            //loop untuk semua matkul yang dipilih, masukan ke tabel enroll
            foreach ($selected as $id_mk) {
                $ins->execute([$npm, $id_mk, $id_sem, $npm, $id_sem]);
            }
            $conn->commit();//simpan semua perubahan permanen
            $_SESSION['frs_result'] = ['semester' => $semLabel, 'courses' => $MKSelected];//simpan hasil ke session
            header("Location: frs_sukses.php");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $msg = 'error: ' . $e->getMessage();
        }
    }
}

//ambil semua mata kuliah yang tersedia pada semester tertentu
$stmt = $conn->prepare("
    SELECT j.Id_Jadwal, mk.Id_MK, mk.Nama AS NamaMK, mk.SKS, j.Hari, j.Jam_Mulai, j.Jam_Selesai, d.Nama AS NamaDosen
    FROM Jadwal j
    JOIN MataKuliah mk ON j.Id_MK = mk.Id_MK
    JOIN Dosen d ON j.NID = d.NID
    WHERE j.Id_Sem = ? AND mk.Status_Aktif = 1
    ORDER BY mk.Nama
");
$stmt->execute([$id_sem]);
$rawCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by Id_MK supaya 1 matkul = 1 card meskipun punya banyak jadwal
$courses = [];
foreach ($rawCourses as $row) {
    $id = $row['Id_MK'];
    if (!isset($courses[$id])) {
        $courses[$id] = $row;
        $courses[$id]['jadwal'] = [];
    }
    $courses[$id]['jadwal'][] = $row['Hari'] . ', ' . fmtTime($row['Jam_Mulai']);
}
$courses = array_values($courses);

//ambil mata kuliah yang sudah diambil di enroll
$stmt = $conn->prepare("SELECT Id_MK FROM Enroll WHERE NPM = ? AND Id_Sem = ?");
$stmt->execute([$npm, $id_sem]);
$enrolled = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'Id_MK');

function fmtTime($t) { return substr($t, 0, 5); }

// SKS colors for badges
$sksColors = [2=>'#ef4444', 3=>'#f97316', 4=>'#2563eb'];
?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FRS – CampusFlow</title>

<style>

/* reset semua elemen biar konsisten */
*{
    box-sizing:border-box;
    margin:0;
    padding:0;
    font-family:'Calibri',Calibri,sans-serif;
}

/* body: latar abu, minimal full layar */
body{
    background:#f1f5f9;
    min-height:100vh;
    display:flex;
    flex-direction:column;
}

/* TOPBAR - navigasi atas */

.topbar{
    background:#fff;
    border-bottom:1px solid #e2e8f0;
    padding:0 2.5rem;
    display:flex;
    align-items:center;
    justify-content:space-between;
    height:4rem;
    flex-shrink:0; /* ga boleh mengecil kalau konten panjang */
}

/* nama app di kiri */
.app-name{
    font-size:1.25rem;
    font-weight:800;
    color:#2563eb;
}

/* container link navigasi */
.nav-links{
    display:flex;
    gap:0.375rem;
}

/* tiap link nav */
.nav-links a{
    padding:0.625rem 1.25rem;
    border-radius:0.5rem;
    font-size:1rem;
    font-weight:600;
    color:#64748b;
    text-decoration:none;
}

/* hover link: background berubah */
.nav-links a:hover{
    background:#f1f5f9;
    color:#0f172a;
}

/* link aktif */
.nav-links a.active{
    background:#eff6ff;
    color:#2563eb;
}

/* MAIN LAYOUT - 2 kolom: kiri (daftar matkul), kanan (summary SKS) */

.main{
    flex:1;
    display:flex; /* layout 2 kolom sejajar */
    gap:1.75rem; /* jarak antar kolom */
    padding:2.5rem;
    max-width:75rem;
    width:100%;
    margin:0 auto;
    align-items:flex-start; /* kolom mulai dari atas */
}

/* kolom kiri: tempat daftar mata kuliah */
.left-col{
    flex:1; /* ambil sisa ruang */
    min-width:0; /* supaya bisa shrink kalau sempit */
}

/* kolom kanan: summary card (sticky) */
.right-col{
    width:21.25rem;
    flex-shrink:0; /* ga boleh mengecil */
}

/* judul halaman */
.page-title{
    font-size:1.875rem;
    font-weight:800;
    color:#0f172a;
    margin-bottom:0.375rem;
}

/* subtitle info semester */
.page-sub{
    font-size:1.0625rem;
    color:#64748b;
    margin-bottom:1.5rem;
}

/* COURSE CARDS - kartu tiap mata kuliah yang bisa dipilih */

/* satu kartu matkul */
.course-card{
    background:#fff;
    border-radius:0.75rem;
    padding:1rem 1.125rem;
    margin-bottom:0.75rem;
    display:flex;
    gap:0.875rem;
    align-items:flex-start;
    box-shadow:0 0.0625rem 0.25rem rgba(0,0,0,.06);
    cursor:pointer; /* kursor jadi tangan pas hover */
    border:2px solid transparent;
    transition:border-color .15s; /* animasi halus saat dipilih */
}

/* kartu yang sudah dipilih/dicentang: border biru + background biru muda */
.course-card.selected{
    border-color:#2563eb;
    background:#eff6ff;
}

/* kartu disembunyikan (pas filter/search) */
.course-card.hidden{
    display:none;
}

/* bagian info di dalam kartu */
.course-info{
    flex:1;
    min-width:0;
}

/* baris atas kartu: kode MK di kiri, badge SKS di kanan */
.course-top{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:0.3125rem;
}

/* kode mata kuliah (misal: AIF1920) */
.course-code{
    font-size:0.8125rem;
    font-weight:700;
    color:#64748b;
}

/* badge SKS: bulat kecil berwarna */
.sks-badge{
    font-size:0.75rem;
    font-weight:700;
    color:#fff;
    padding:0.1875rem 0.625rem;
    border-radius:1.25rem;
}

/* nama mata kuliah */
.course-name{
    font-size:1.0625rem;
    font-weight:700;
    color:#0f172a;
    margin-bottom:0.4375rem;
    line-height:1.3;
}

/* baris meta: info dosen dan jadwal */
.course-meta{
    display:flex;
    gap:1rem;
    font-size:0.875rem;
    color:#64748b;
}

/* satu item meta (ikon + teks) */
.meta-item{
    display:flex;
    align-items:center;
    gap:0.3125rem;
}

/* ikon kecil di meta */
.meta-icon svg{
    width:0.875rem;
    height:0.875rem;
    stroke:#94a3b8;
    fill:none;
    stroke-width:2;
    stroke-linecap:round;
    stroke-linejoin:round;
}

/* SUMMARY CARD - kartu ringkasan di kanan, nempel saat scroll */

.summary-card{
    background:#fff;
    border-radius:0.75rem;
    box-shadow:0 0.0625rem 0.375rem rgba(0,0,0,.08);
    overflow:hidden;
    position:sticky; /* nempel di atas saat scroll */
    top:1.5rem;
}

/* banner atas summary: background gradient, nunjukin total SKS */
.sks-banner{
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    padding:1.5rem;
    color:#fff;
}

/* label "Total SKS" */
.sks-lbl{
    font-size:0.875rem;
    opacity:.85;
    margin-bottom:0.375rem;
    text-transform:uppercase;
    letter-spacing:.3px;
}

/* angka total SKS yang gede */
.sks-val{
    font-size:2.25rem;
    font-weight:800;
}

/* body summary: info jumlah MK + tombol kirim */
.summary-body{
    padding:1.25rem;
}

/* baris info (misal: "Mata kuliah dipilih: 3 MK") */
.summary-row{
    display:flex;
    justify-content:space-between;
    font-size:1rem;
    color:#475569;
    margin-bottom:0.875rem;
}

/* angka di kanan summary row: tebal */
.summary-row span:last-child{
    font-weight:700;
    color:#0f172a;
}

/* SEND BUTTON - tombol "Kirim FRS" */

.send-btn{
    width:100%;
    border:none;
    border-radius:0.625rem;
    padding:1rem;
    font-size:1.0625rem;
    font-weight:700;
    cursor:pointer;
    transition:all .2s;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:0.5rem;
}

/* state disabled: abu-abu, ga bisa diklik (belum pilih matkul) */
.send-btn.disabled{
    background:#e2e8f0;
    color:#94a3b8;
    cursor:not-allowed;
}

/* state active: gradient biru-ungu, siap kirim */
.send-btn.active{
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    color:#fff;
    box-shadow:0 0.25rem 0.875rem rgba(37,99,235,.3);
}

/* ikon panah di tombol kirim */
.send-arrow svg{
    width:1.0625rem;
    height:1.0625rem;
    stroke:currentColor;
    fill:none;
    stroke-width:2.5;
    stroke-linecap:round;
    stroke-linejoin:round;
}

/* ERROR - pesan error kalau gagal simpan FRS */

.error-msg{
    background:#fee2e2;
    color:#b91c1c;
    padding:0.75rem 1rem;
    border-radius:0.5rem;
    font-size:0.9375rem;
    margin-bottom:1.125rem;
}

</style>

</head>

<body>

<!-- TOPBAR: navigasi atas -->

<div class="topbar">

    <div class="app-name">WombatStudent</div>

    <!-- menu navigasi -->
    <nav class="nav-links">
        <a href="dashboardMahasiswa.php">Beranda</a>
        <a href="jadwalMahasiswa.php">Jadwal</a>
        <a href="historyFRS.php">Riwayat</a>
    </nav>

</div>

<!-- MAIN: layout 2 kolom (daftar matkul kiri, summary kanan) -->

<div class="main">

    <!-- LEFT COLUMN: daftar mata kuliah yang bisa dipilih -->

    <div class="left-col">

        <div class="page-title">Pilih Mata Kuliah</div>
        <div class="page-sub">Semester <?= htmlspecialchars($semLabel) ?></div>

        <!-- error message kalau gagal simpan -->
        <?php if (str_starts_with($msg, 'error')): ?>
            <div class="error-msg"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <!-- form FRS: semua checkbox matkul ada di sini -->
        <form method="POST" id="frsForm">
            <div id="courseList">

                <?php
                $sksColorMap = [2=>'#ef4444', 3=>'#f97316', 4=>'#2563eb'];
                foreach ($courses as $c):
                    $isChecked = in_array(trim($c['Id_MK']), array_map('trim', $enrolled));
                    $badgeColor = $sksColorMap[$c['SKS']] ?? '#64748b';
                ?>

                <!-- satu kartu mata kuliah (klik untuk pilih/batal) -->
                <div class="course-card <?= $isChecked ? 'selected' : '' ?>"
                     data-sks="<?= $c['SKS'] ?>"
                     onclick="toggleCourse(this)">

                    <div class="cb <?= $isChecked ? 'checked' : '' ?>"></div>

                    <div class="course-info">

                        <!-- baris atas: kode MK + badge SKS -->
                        <div class="course-top">
                            <span class="course-code"><?= htmlspecialchars($c['Id_MK']) ?></span>
                            <span class="sks-badge" style="background:<?= $badgeColor ?>">
                                <?= $c['SKS'] ?> SKS
                            </span>
                        </div>

                        <!-- nama mata kuliah -->
                        <div class="course-name">
                            <?= htmlspecialchars($c['NamaMK']) ?>
                        </div>

                        <!-- info dosen dan jadwal -->
                        <div class="course-meta">
                            <span class="meta-item">
                                <span class="meta-icon">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                        <circle cx="12" cy="7" r="4"/>
                                    </svg>
                                </span>
                                <?= htmlspecialchars($c['NamaDosen']) ?>
                            </span>
                            <span class="meta-item">
                                <span class="meta-icon">
                                    <svg viewBox="0 0 24 24">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                </span>
                                <?= htmlspecialchars(implode(' | ', $c['jadwal'])) ?>
                            </span>
                        </div>

                    </div>

                    <!-- checkbox tersembunyi (dipilih via klik kartu) -->
                    <input type="checkbox"
                           name="selected_mk[]"
                           value="<?= htmlspecialchars($c['Id_MK']) ?>"
                           <?= $isChecked ? 'checked' : '' ?>
                           style="display:none"
                           class="mk-checkbox">

                </div>

                <?php endforeach; ?>

            </div>
        </form>

    </div>

    <!-- RIGHT COLUMN: summary card (total SKS + tombol kirim) -->

    <div class="right-col">

        <div class="summary-card">

            <!-- banner gradient: nunjukin total SKS yang dipilih -->
            <div class="sks-banner">
                <div class="sks-lbl">Total SKS</div>
                <div class="sks-val" id="totalSKS">0</div>
            </div>

            <div class="summary-body">

                <!-- info jumlah MK yang dipilih -->
                <div class="summary-row">
                    <span>Mata kuliah dipilih</span>
                    <span><span id="countLabel">0</span> MK</span>
                </div>

                <!-- tombol kirim FRS (disabled sampai ada yang dipilih) -->
                <button type="button"
                        class="send-btn disabled"
                        id="sendBtn"
                        onclick="document.getElementById('frsForm').submit()">
                    Kirim FRS
                    <span class="send-arrow">
                        <svg viewBox="0 0 24 24">
                            <line x1="5" y1="12" x2="19" y2="12"/>
                            <polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </span>
                </button>

            </div>

        </div>

    </div>

</div>

<!-- SCRIPTS: JavaScript untuk interaksi pilih matkul -->

<script>
// toggle pilih/batal pilih matkul saat kartu diklik
function toggleCourse(card) {
    const cb = card.querySelector('.cb'),
          chk = card.querySelector('.mk-checkbox');
    const checked = !chk.checked;
    chk.checked = checked;
    cb.classList.toggle('checked', checked);
    card.classList.toggle('selected', checked);
    updateSummary();
}

// update angka total SKS dan jumlah MK di summary card
function updateSummary() {
    let total = 0, count = 0;
    document.querySelectorAll('.mk-checkbox:checked').forEach(chk => {
        total += parseInt(chk.closest('.course-card').dataset.sks);
        count++;
    });
    document.getElementById('totalSKS').textContent = total;
    document.getElementById('countLabel').textContent = count;
    const btn = document.getElementById('sendBtn');
    btn.classList.toggle('disabled', count === 0);
    btn.classList.toggle('active', count > 0);
}

// filter matkul berdasarkan input search
function filterCourses() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.course-card').forEach(card => {
        card.classList.toggle('hidden', q && !card.dataset.search.includes(q));
    });
}

// jalankan pas halaman pertama kali load
updateSummary();
</script>

</body>
</html>
