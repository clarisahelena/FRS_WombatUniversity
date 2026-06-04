<?php
session_start();
if (!isset($_SESSION["id_user"])) {
    header("Location: ../index.php");
    exit;
}
require_once "../Koneksi.php";

$npm    = $_SESSION["id_user"];

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
            SELECT mk.Id_MK, mk.Nama AS NamaMK, mk.SKS, j.Hari, j.Jam_Mulai, j.Jam_Selesai
            FROM Jadwal j JOIN MataKuliah mk ON j.Id_MK = mk.Id_MK
            WHERE j.Id_Sem = ? AND mk.Id_MK IN ($placeholders) 
        "); //arti dari ? adalah placeholder yang nantinya bisa diisi
        $stmt2->execute($params);
        $jadwalSelected = $stmt2->fetchAll(PDO::FETCH_ASSOC);//ambil semua jadwal matkul yang dipilih

        try {
            $conn->beginTransaction(); //muai transaction database
            $id_frs = substr($npm, -4) . $id_sem;// substring dan trim, substring dari berapa sampe berapa kalo trim langsung potong supaya jadi id frs
            $stmt2 = $conn->prepare("SELECT Id_FRS FROM FRS WHERE Id_FRS = ?"); //cek apakah frs udh ada, kalau belum insert kode frs baru
            //insert kode frs baru     
            $stmt2->execute([$id_frs]); 
            if (!$stmt2->fetch()) {
                $conn->prepare("INSERT INTO FRS (Id_FRS, NPM) VALUES (?, ?)")->execute([$id_frs, $npm]);
            }

            //hapus enroll lama apabila terjaid perubahan frs
            $conn->prepare("DELETE FROM Enroll WHERE NPM = ? AND Id_Sem = ?")->execute([$npm, $id_sem]);
            //inseert enroll baru
            $ins = $conn->prepare("INSERT INTO Enroll (NPM, Id_MK, Id_Sem, Id_FRS, Dibuat_pada) VALUES (?, ?, ?, ?, GETDATE())");
            //loop untuk semua matkul yang dipilih, masukan ke tabel enroll
            foreach ($selected as $id_mk) {
                $ins->execute([$npm, $id_mk, $id_sem, $id_frs]);
            }
            $conn->commit();//simpan semua perubahan permanen
            $_SESSION['frs_result'] = ['semester' => $semLabel, 'courses' => $jadwalSelected];//simpan hasil ke session
            header("Location: frs_sukses.php");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            $msg = 'error';
        }
    }
}

//ambil semua mata kuliah yang tersedia pada semester tertentu
$stmt = $conn->prepare("
    SELECT j.Id_Jadwal, mk.Id_MK, mk.Nama AS NamaMK, mk.SKS, j.Hari, j.Jam_Mulai, j.Jam_Selesai, d.Nama AS NamaDosen
    FROM Jadwal j
    JOIN MataKuliah mk ON j.Id_MK = mk.Id_MK
    JOIN Dosen d ON j.NID = d.NID
    WHERE j.Id_Sem = ?
    ORDER BY mk.Nama
");
$stmt->execute([$id_sem]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
*{box-sizing:border-box;margin:0;padding:0;font-family:'Calibri',Calibri,sans-serif}
body{background:#f1f5f9;min-height:100vh;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px;flex-shrink:0}
.app-name{font-size:20px;font-weight:800;color:#2563eb}
.nav-links{display:flex;gap:6px}
.nav-links a{padding:10px 20px;border-radius:8px;font-size:16px;font-weight:600;color:#64748b;text-decoration:none}
.nav-links a:hover{background:#f1f5f9;color:#0f172a}
.nav-links a.active{background:#eff6ff;color:#2563eb}
.main{flex:1;display:flex;gap:28px;padding:40px;max-width:1200px;width:100%;margin:0 auto;align-items:flex-start}
.left-col{flex:1;min-width:0}
.right-col{width:340px;flex-shrink:0}
.page-title{font-size:30px;font-weight:800;color:#0f172a;margin-bottom:6px}
.page-sub{font-size:17px;color:#64748b;margin-bottom:24px}
.course-card{background:#fff;border-radius:12px;padding:16px 18px;margin-bottom:12px;display:flex;gap:14px;align-items:flex-start;box-shadow:0 1px 4px rgba(0,0,0,.06);cursor:pointer;border:2px solid transparent;transition:border-color .15s}
.course-card.selected{border-color:#2563eb;background:#eff6ff}
.course-card.hidden{display:none}
.course-info{flex:1;min-width:0}
.course-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px}
.course-code{font-size:13px;font-weight:700;color:#64748b}
.sks-badge{font-size:12px;font-weight:700;color:#fff;padding:3px 10px;border-radius:20px}
.course-name{font-size:17px;font-weight:700;color:#0f172a;margin-bottom:7px;line-height:1.3}
.course-meta{display:flex;gap:16px;font-size:14px;color:#64748b}
.meta-item{display:flex;align-items:center;gap:5px}
.meta-icon svg{width:14px;height:14px;stroke:#94a3b8;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.summary-card{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.08);overflow:hidden;position:sticky;top:24px}
.sks-banner{background:linear-gradient(135deg,#2563eb,#7c3aed);padding:24px;color:#fff}
.sks-lbl{font-size:14px;opacity:.85;margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px}
.sks-val{font-size:36px;font-weight:800}
.summary-body{padding:20px}
.summary-row{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.summary-row span:last-child{font-weight:700;color:#0f172a}
.send-btn{width:100%;border:none;border-radius:10px;padding:16px;font-size:17px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:8px}
.send-btn.disabled{background:#e2e8f0;color:#94a3b8;cursor:not-allowed}
.send-btn.active{background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.3)}
.send-arrow svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}
.error-msg{background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:8px;font-size:15px;margin-bottom:18px}
</style>
</head>
<body>
<div class="topbar">
  <div class="app-name">WombatStudent</div>
  <nav class="nav-links">
    <a href="dashboard.php">Beranda</a>
    <a href="jadwal.php">Jadwal</a>
    <a href="history.php">Riwayat</a>
  </nav>
</div>

<div class="main">
  <div class="left-col">
    <div class="page-title">Pilih Mata Kuliah</div>
    <div class="page-sub">Semester <?= htmlspecialchars($semLabel) ?></div>

    <?php if ($msg === 'error'): ?>
    <div class="error-msg">Gagal menyimpan FRS, coba lagi.</div>
    <?php endif; ?>

    <form method="POST" id="frsForm">
      <div id="courseList">
        <?php
        $sksColorMap = [2=>'#ef4444', 3=>'#f97316', 4=>'#2563eb'];
        foreach ($courses as $c):
          $isChecked = in_array(trim($c['Id_MK']), array_map('trim', $enrolled));
          $badgeColor = $sksColorMap[$c['SKS']] ?? '#64748b';
        ?>
        <div class="course-card <?= $isChecked ? 'selected' : '' ?>"
             data-sks="<?= $c['SKS'] ?>"
             onclick="toggleCourse(this)">
          <div class="cb <?= $isChecked ? 'checked' : '' ?>"></div>
          <div class="course-info">
            <div class="course-top">
              <span class="course-code"><?= htmlspecialchars($c['Id_MK']) ?></span>
              <span class="sks-badge" style="background:<?= $badgeColor ?>"><?= $c['SKS'] ?> SKS</span>
            </div>
            <div class="course-name"><?= htmlspecialchars($c['NamaMK']) ?></div>
            <div class="course-meta">
              <span class="meta-item"><span class="meta-icon"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span><?= htmlspecialchars($c['NamaDosen']) ?></span>
              <span class="meta-item"><span class="meta-icon"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span><?= htmlspecialchars($c['Hari']) ?>, <?= fmtTime($c['Jam_Mulai']) ?></span>
            </div>
          </div>
          <input type="checkbox" name="selected_mk[]" value="<?= htmlspecialchars($c['Id_MK']) ?>"
                 <?= $isChecked ? 'checked' : '' ?> style="display:none" class="mk-checkbox">
        </div>
        <?php endforeach; ?>
      </div>
    </form>
  </div>

  <div class="right-col">
    <div class="summary-card">
      <div class="sks-banner">
        <div class="sks-lbl">Total SKS</div>
        <div class="sks-val" id="totalSKS">0</div>
      </div>
      <div class="summary-body">
        <div class="summary-row">
          <span>Mata kuliah dipilih</span>
          <span><span id="countLabel">0</span> MK</span>
        </div>
        <button type="button" class="send-btn disabled" id="sendBtn" onclick="document.getElementById('frsForm').submit()">
          Kirim FRS
          <span class="send-arrow"><svg viewBox="0 0 24 24"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg></span>
        </button>
      </div>
    </div>
  </div>
</div>

<script>
function toggleCourse(card){
    const cb=card.querySelector('.cb'), chk=card.querySelector('.mk-checkbox');
    const checked=!chk.checked;
    chk.checked=checked;
    cb.classList.toggle('checked',checked);
    card.classList.toggle('selected',checked);
    updateSummary();
}
function updateSummary(){
    let total=0,count=0;
    document.querySelectorAll('.mk-checkbox:checked').forEach(chk=>{
        total+=parseInt(chk.closest('.course-card').dataset.sks);
        count++;
    });
    document.getElementById('totalSKS').textContent=total;
    document.getElementById('countLabel').textContent=count;
    const btn=document.getElementById('sendBtn');
    btn.classList.toggle('disabled',count===0);
    btn.classList.toggle('active',count>0);
}
function filterCourses(){
    const q=document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.course-card').forEach(card=>{
        card.classList.toggle('hidden',q&&!card.dataset.search.includes(q));
    });
}
updateSummary();
</script>
</body>
</html>
