<?php
session_start();
if (!isset($_SESSION["id_user"])) { header("Location: ../index.php"); exit; }
require_once "../Koneksi.php";

//ambil NPM user
$npm = $_SESSION["id_user"];

$periode = $_GET["periode"] ?? ($_SESSION["periode"] ?? "1");
$semester = $_GET["semester"] ?? ($_SESSION["semester"] ?? "25");

$id_sem = $semester . "-" . $periode;

// Ambil semua data enrollment + jadwal dalam 1 query
$stmt = $conn->prepare("
    SELECT s.Id_Sem, s.Periode, s.Tahun_Akademik,
           mk.Id_MK, mk.Nama AS NamaMK, mk.SKS, j.Hari, j.Jam_Mulai, j.Jam_Selesai
    FROM Enroll e
    JOIN Semester s ON e.Id_Sem = s.Id_Sem
    JOIN MataKuliah mk ON e.Id_MK = mk.Id_MK
    JOIN Jadwal j ON j.Id_MK = mk.Id_MK AND j.Id_Sem = e.Id_Sem
    WHERE e.NPM = ? AND mk.Status_Aktif = 1
    ORDER BY s.Tahun_Akademik DESC, s.Periode DESC, mk.Nama
");
$stmt->execute([$npm]);
$allRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by semester, lalu by Id_MK
$history = [];
foreach ($allRows as $row) {
    $sid = $row['Id_Sem'];
    if (!isset($history[$sid])) {
        $history[$sid] = [
            'sem' => ['Id_Sem' => $sid, 'Periode' => $row['Periode'], 'Tahun_Akademik' => $row['Tahun_Akademik']],
            'courses' => [],
            'totalSKS' => 0,
        ];
    }
    $id = $row['Id_MK'];
    if (!isset($history[$sid]['courses'][$id])) {
        $history[$sid]['courses'][$id] = $row;
        $history[$sid]['courses'][$id]['jadwal'] = [];
    }
    $history[$sid]['courses'][$id]['jadwal'][] = $row['Hari'] . ', ' . substr($row['Jam_Mulai'],0,5) . '–' . substr($row['Jam_Selesai'],0,5);
}
// Finalize: convert courses to array and calculate totalSKS
foreach ($history as &$h) {
    $h['courses'] = array_values($h['courses']);
    $h['totalSKS'] = array_sum(array_column($h['courses'], 'SKS'));
}
unset($h);
$history = array_values($history);

function fmtTime($t){ return substr($t,0,5); }
?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat FRS – CampusFlow</title>

<style>

/* reset semua elemen biar konsisten di semua browser */
*{
    box-sizing:border-box;
    margin:0;
    padding:0;
    font-family:'Calibri',Calibri,sans-serif;
}

/* body: latar abu terang, minimal setinggi layar */
body{
    background:#f1f5f9;
    min-height:100vh;
    display:flex;
    flex-direction:column;
}

/* TOPBAR - bar navigasi paling atas */

.topbar{
    background:#fff;
    border-bottom:1px solid #e2e8f0;
    padding:0 2.5rem;
    display:flex;
    align-items:center; /* rata tengah vertikal */
    justify-content:space-between; /* nama kiri, nav kanan */
    height:4rem;
}

/* nama app di pojok kiri */
.app-name{
    font-size:1.25rem;
    font-weight:800;
    color:#2563eb;
}

/* container link-link navigasi */
.nav-links{
    display:flex;
    gap:0.375rem;
}

/* tiap link navigasi */
.nav-links a{
    padding:0.625rem 1.25rem;
    border-radius:0.5rem;
    font-size:1rem;
    font-weight:600;
    color:#64748b;
    text-decoration:none;
    transition:background .15s;
}

/* hover: background berubah saat mouse lewat */
.nav-links a:hover{
    background:#f1f5f9;
    color:#0f172a;
}

/* link yang lagi aktif (halaman ini) */
.nav-links a.active{
    background:#eff6ff;
    color:#2563eb;
}

/* MAIN - area konten utama, lebih sempit karena ini halaman riwayat */

.main{
    flex:1;
    padding:2.5rem;
    max-width:56.25rem; /* lebih kecil dari dashboard, cocok buat timeline */
    width:100%;
    margin:0 auto;
}

/* judul halaman */
.page-title{
    font-size:1.875rem;
    font-weight:800;
    color:#0f172a;
    margin-bottom:0.375rem;
}

/* subtitle di bawah judul */
.page-sub{
    font-size:1.0625rem;
    color:#64748b;
    margin-bottom:2.25rem;
}

/* TIMELINE - garis vertikal di kiri yang menghubungkan tiap semester */

.timeline{
    position:relative;
    padding-left:2rem; /* kasih ruang buat dot di kiri */
}

/* garis vertikal timeline (pseudo-element) */
.timeline::before{
    content:'';
    position:absolute;
    left:0.5rem;
    top:0.5rem;
    bottom:0.5rem;
    width:2px;
    background:#e2e8f0;
}

/* satu blok semester dalam timeline */
.sem-block{
    position:relative;
    margin-bottom:2rem;
}

/* bulatan kecil di kiri timeline (penanda tiap semester) */
.sem-dot{
    position:absolute;
    left:-1.75rem;
    top:1rem;
    width:0.875rem;
    height:0.875rem;
    border-radius:50%;
    background:#cbd5e1;
    border:2px solid #fff;
    box-shadow:0 0 0 2px #cbd5e1;
}

/* dot semester aktif: warna biru */
.sem-dot.active{
    background:#2563eb;
    box-shadow:0 0 0 3px #bfdbfe;
}

/* dot semester bentrok: warna merah */
.sem-dot.bentrok{
    background:#dc2626;
    box-shadow:0 0 0 3px #fecaca;
}

/* SEMESTER CARD - kartu putih tiap semester berisi daftar matkul */

.sem-card{
    background:#fff;
    border-radius:0.875rem;
    box-shadow:0 0.0625rem 0.375rem rgba(0,0,0,.08);
    overflow:hidden;
}

/* header kartu semester: judul semester + meta info */
.sem-header{
    padding:1.125rem 1.375rem;
    border-bottom:1px solid #f1f5f9;
    display:flex;
    align-items:center;
    justify-content:space-between;
}

/* judul semester (misal: "Ganjil 2025/2026") */
.sem-title{
    font-size:1.25rem;
    font-weight:800;
    color:#0f172a;
}

/* info tambahan: total SKS, jumlah MK */
.sem-meta{
    font-size:0.9375rem;
    color:#64748b;
    margin-top:0.1875rem;
}

/* badge status semester */
.sem-badge{
    display:inline-flex;
    align-items:center;
    gap:0.375rem;
    padding:0.3125rem 0.75rem;
    border-radius:1.25rem;
    font-size:0.8125rem;
    font-weight:700;
}

/* badge hijau: semester OK */
.badge-ok{
    background:#dcfce7;
    color:#16a34a;
}

/* badge merah: ada bentrok */
.badge-bentrok{
    background:#fee2e2;
    color:#dc2626;
}

/* COURSE LIST - daftar mata kuliah di dalam kartu semester */

.course-list{
    padding:0.5rem 0;
}

/* satu baris mata kuliah */
.course-row{
    display:flex;
    align-items:center;
    padding:0.75rem 1.375rem;
    border-bottom:1px solid #f8fafc;
    gap:0.875rem;
}

/* baris terakhir ga perlu garis bawah */
.course-row:last-child{
    border-bottom:none;
}

/* bar kecil di kiri tiap matkul (indikator status) */
.conflict-bar{
    width:0.25rem;
    height:2.5rem;
    border-radius:0.125rem;
    flex-shrink:0;
}

/* bar abu: ga ada masalah */
.bar-ok{
    background:#e2e8f0;
}

/* bar merah: ada bentrok */
.bar-bentrok{
    background:#dc2626;
}

/* nama mata kuliah */
.course-name{
    font-size:1rem;
    font-weight:700;
    color:#0f172a;
    flex:1;
}

/* detail jadwal (hari, jam) */
.course-detail{
    font-size:0.875rem;
    color:#64748b;
    margin-top:0.125rem;
}

/* angka SKS di kanan */
.sks-num{
    font-size:0.875rem;
    font-weight:700;
    color:#475569;
    white-space:nowrap;
}

/* tag bentrok kecil merah */
.bentrok-tag{
    display:inline-block;
    background:#fee2e2;
    color:#dc2626;
    border-radius:0.25rem;
    padding:0.125rem 0.5rem;
    font-size:0.75rem;
    font-weight:700;
    margin-left:0.5rem;
}

/* tampilan kosong kalau belum ada riwayat */
.empty{
    text-align:center;
    padding:3rem;
    color:#94a3b8;
    font-size:1.0625rem;
}

/* EDIT LINK - link "Edit FRS" di header semester aktif */

.edit-link{
    display:inline-flex;
    align-items:center;
    gap:0.375rem;
    color:#2563eb;
    font-size:0.875rem;
    font-weight:600;
    text-decoration:none;
}

/* ikon pensil di link edit */
.edit-link svg{
    width:0.875rem;
    height:0.875rem;
    stroke:#2563eb;
    fill:none;
    stroke-width:2.5;
    stroke-linecap:round;
    stroke-linejoin:round;
}

/* tombol disabled buat semester yang udah lewat (ga bisa di-edit) */
.edit-btn.disabled{
    background:#e2e8f0;
    color:#94a3b8;
    cursor:not-allowed;
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
        <a href="historyFRS.php" class="active">Riwayat</a>
    </nav>

</div>

<!-- MAIN: konten utama halaman riwayat -->

<div class="main">

    <!-- HEADER: judul + deskripsi halaman -->

    <div class="page-title">Histori Akademik</div>
    <div class="page-sub">Riwayat FRS per semester</div>

    <?php if (empty($history)): ?>

        <!-- pesan kalau belum ada riwayat sama sekali -->
        <div class="empty">Belum ada riwayat FRS. Isi FRS terlebih dahulu.</div>

    <?php else: ?>

    <!-- TIMELINE: daftar semester dari yang terbaru -->

    <div class="timeline">

        <?php foreach ($history as $i => $h):
            $s = $h['sem'];
            $label = trim($s['Periode']).' '.$s['Tahun_Akademik'].'/'.($s['Tahun_Akademik']+1);
            $dotClass = $i === 0 ? 'active' : '';
        ?>

        <!-- satu blok semester -->
        <div class="sem-block">

            <!-- dot penanda di timeline -->
            <div class="sem-dot <?= $dotClass ?>"></div>

            <div class="sem-card">

                <!-- SEMESTER HEADER: info semester + tombol edit -->

                <div class="sem-header">

                    <div>
                        <div class="sem-title"><?= htmlspecialchars($label) ?></div>
                        <div class="sem-meta">
                            <?= $h['totalSKS'] ?> SKS &bull; <?= count($h['courses']) ?> mata kuliah &bull; Disimpan
                        </div>
                    </div>

                    <div style="display:flex;align-items:center;gap:0.875rem">
                        <?php if (trim($h['sem']['Id_Sem']) === trim($id_sem)): ?>
                            <!-- semester aktif bisa di-edit -->
                            <a href="frs.php" class="edit-link">
                                <svg viewBox="0 0 24 24">
                                    <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                    <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                Edit FRS
                            </a>
                        <?php else: ?>
                            <!-- semester lama ga bisa di-edit -->
                            <button class="edit-btn disabled" disabled>
                                Tidak Bisa Edit
                            </button>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- COURSE LIST: daftar matkul yang diambil semester itu -->

                <div class="course-list">
                    <?php foreach ($h['courses'] as $c): ?>
                    <div class="course-row">
                        <div class="conflict-bar bar-ok"></div>
                        <div style="flex:1">
                            <div class="course-name">
                                <?= htmlspecialchars($c['Id_MK']) ?> : <?= htmlspecialchars($c['NamaMK']) ?>
                            </div>
                            <div class="course-detail">
                                <?= htmlspecialchars(implode(' | ', $c['jadwal'])) ?>
                            </div>
                        </div>
                        <div class="sks-num"><?= $c['SKS'] ?> SKS</div>
                    </div>
                    <?php endforeach; ?>
                </div>

            </div>

        </div>

        <?php endforeach; ?>

    </div>

    <?php endif; ?>

</div>

</body>
</html>
