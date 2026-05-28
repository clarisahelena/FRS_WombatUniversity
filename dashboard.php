<?php
session_start(); //membuka session user saat ini
if (!isset($_SESSION["id_user"])) {//kalo belom ada id, berarti user belom login
    header("Location: index.php");
    exit;
}
require_once "Koneksi.php";
//mengambil data dari halaman login (session)
$nama  = $_SESSION["nama"];
$role  = $_SESSION["role"];
$npm   = $_SESSION["id_user"];

// pilih semester
$id_sem = '26-1';
// $id_sem menyimpan kode semester aktif.
// '26-1' adalah teks/string.
$stmt = $conn->prepare("SELECT Periode, Tahun_Akademik FROM Semester WHERE Id_Sem = ?"); //ambil semester yang sedang berlangsung
$stmt->execute([$id_sem]);//execute = menjalankan query
$sem = $stmt->fetch(PDO::FETCH_ASSOC);

//-> memanggil fungsi/method dari object
//? adalah placeholder yang nantinya nilainya akan di isi
// query untuk mengambil database matakuliah yang dibuka pada frs sekarang
// menggunakan subquery di SELECT untuk mengambil nama dosen
$stmt = $conn->prepare("
    SELECT mk.Id_MK, mk.Nama AS NamaMK, mk.SKS, j.Hari, j.Jam_Mulai, j.Jam_Selesai,
           (SELECT Nama FROM Dosen WHERE NID = j.NID) AS NamaDosen
    FROM Jadwal j, MataKuliah mk
    WHERE j.Id_MK = mk.Id_MK
      AND j.Id_Sem = ?
    ORDER BY mk.Nama
");
$stmt->execute([$id_sem]);
$allCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);//hasilnya dibuat array berdasarkan nama kolom, mengambil semua baris hasil query
$totalMK = count($allCourses);
$totalSKS = array_sum(array_column($allCourses, 'SKS'));

function fmtTime($t) {
    return substr($t, 0, 5);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard CampusFlow</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Calibri',Calibri,sans-serif}
body{background:#f1f5f9;min-height:100vh;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px}
.app-name{font-size:20px;font-weight:800;color:#2563eb}
.nav-links{display:flex;gap:6px}
.nav-links a{padding:10px 20px;border-radius:8px;font-size:16px;font-weight:600;color:#64748b;text-decoration:none;transition:background .15s}
.nav-links a:hover{background:#f1f5f9;color:#0f172a}
.nav-links a.active{background:#eff6ff;color:#2563eb}
.main{flex:1;padding:40px;max-width:1200px;width:100%;margin:0 auto}
.greeting{font-size:30px;font-weight:800;color:#0f172a;margin-bottom:6px}
.subtitle{font-size:17px;color:#64748b;margin-bottom:28px}
.sem-card{background:linear-gradient(135deg,#2563eb,#7c3aed);border-radius:16px;padding:28px 32px;color:#fff;margin-bottom:28px;box-shadow:0 8px 24px rgba(79,70,229,.25)}
.sem-label{font-size:15px;opacity:.85;margin-bottom:6px}
.sem-title{font-size:28px;font-weight:800;line-height:1.3}
.table-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.section-title{font-size:20px;font-weight:800;color:#0f172a}
.frs-btn{display:flex;align-items:center;justify-content:center;gap:10px;background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;border-radius:12px;padding:16px;font-weight:800;font-size:20px;text-decoration:none;box-shadow:0 6px 20px rgba(37,99,235,.3);width:100%;margin-bottom:24px}
.frs-btn svg{width:22px;height:22px;stroke:#fff;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}
.table-wrap{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.08);overflow:hidden}
table{width:100%;border-collapse:collapse}
thead tr{background:#f8fafc;border-bottom:2px solid #e2e8f0}
th{padding:14px 18px;text-align:left;font-weight:700;color:#475569;font-size:15px}
td{padding:14px 18px;border-bottom:1px solid #f1f5f9;color:#334155;font-size:15px;vertical-align:top}
tr:last-child td{border-bottom:none}
tr:hover td{background:#fafbff}
.jadwal-tag{display:inline-block;background:#eff6ff;color:#2563eb;border-radius:4px;padding:3px 9px;font-size:14px;font-weight:600}
</style>
</head>
<body>
<div class="topbar">
  <div class="app-name">WombatStudent</div>
  <nav class="nav-links">
    <a href="dashboard.php" class="active">Beranda</a>
    <a href="jadwal.php">Jadwal</a>
    <a href="history.php">Riwayat</a>
  </nav>
</div>

<div class="main">
  <div class="greeting">Halo, <?= htmlspecialchars(explode(' ', $nama)[0]) ?></div>
  <div class="subtitle">Selamat datang. Ini mata kuliah yang dibuka semester ini.</div>

  <div class="sem-card">
    <div class="sem-label"><?= htmlspecialchars(trim($sem['Periode'] ?? 'Ganjil')) ?> <?= $sem['Tahun_Akademik'] ?? '2025' ?>/<?= ($sem['Tahun_Akademik'] ?? 2025) + 1 ?></div>
    <div class="sem-title">Tahun Akademik <?= $sem['Tahun_Akademik'] ?? '2025' ?>/<?= ($sem['Tahun_Akademik'] ?? 2025) + 1 ?></div>
  </div>

  <a href="frs.php" class="frs-btn">
    <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><line x1="9" y1="15" x2="15" y2="15"/></svg>
    Isi FRS
  </a>

  <div class="table-header">
    <div class="section-title">Mata Kuliah Dibuka</div>
  </div>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Kode</th>
          <th>Nama Mata Kuliah</th>
          <th>SKS</th>
          <th>Jadwal</th>
          <th>Dosen</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($allCourses as $mk): ?>
        <tr>
          <td><?= htmlspecialchars($mk['Id_MK']) ?></td>
          <td><?= htmlspecialchars($mk['NamaMK']) ?></td>
          <td><?= $mk['SKS'] ?></td>
          <td><span class="jadwal-tag"><?= htmlspecialchars($mk['Hari']) ?>, <?= fmtTime($mk['Jam_Mulai']) ?>–<?= fmtTime($mk['Jam_Selesai']) ?></span></td>
          <td><?= htmlspecialchars($mk['NamaDosen']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
