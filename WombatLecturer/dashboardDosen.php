<?php
session_start();

if (!isset($_SESSION["id_user"])) {
    header("Location: ../index.php");
    exit;
}
require_once "Koneksi.php";

$nama = $_SESSION["nama"];
$role = $_SESSION["role"];
$nid = $_SESSION["id_user"];

$periode = $_GET["periode"] ?? ($_SESSION["periode"] ?? "1");
$semester = $_GET["semester"] ?? ($_SESSION["semester"] ?? "25");

$id_sem = $semester . "-" . $periode;

$stmtSem = $conn->prepare("
    SELECT Periode, Tahun_Akademik 
    FROM Semester 
    WHERE Id_Sem = ?
");
$stmtSem->execute([$id_sem]);
$sem = $stmtSem->fetch(PDO::FETCH_ASSOC);

$periode_text = "";
if($periode==1) {
    $periode_text = "Ganjil";
} else {
    $periode_text = "Genap";
}

//kalau bukan dosen, pindahkan ke mahasiswa
if ($role != "dosen") {
    header("Location: ../CampusFlow/dashboard.php");
    exit;
}


//jadwa keseluruhan semester ini
$stmt = $conn->prepare("
    SELECT mk.Id_MK, mk.Nama AS NamaMK, mk.SKS, j.Hari, j.Jam_Mulai, j.Jam_Selesai, d.Nama AS NamaDosen
    FROM Jadwal j
    JOIN MataKuliah mk ON j.Id_MK = mk.Id_MK
    JOIN Dosen d ON j.NID = d.NID
    WHERE j.Id_Sem = ?
    ORDER BY mk.Nama
");
$stmt->execute([$id_sem]);
$allCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

function fmtTime($t){
    return substr($t,0,5);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Dashboard Dosen</title>

<style>

body{
    font-family:Calibri;
    background:#f1f5f9;
    margin:0;
}

.topbar{
    height:64px;
    background:white;
    border-bottom:1px solid #ddd;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 40px;
}

.app-name{
    font-size:22px;
    font-weight:bold;
    color:#2563eb;
}

.nav{
    display:flex;
    gap:8px;
}

.nav a{
    text-decoration:none;
    color:#475569;
    font-weight:600;
    padding:10px 18px;
    border-radius:10px;
    transition:.2s;
}

.nav a:hover{
    background:#f1f5f9;
}

.nav a.active{
    background:#dbeafe;
    color:#2563eb;
}

.card{
    background:white;
    padding:30px;
    border-radius:14px;
    width:320px;
    box-shadow:0 2px 8px rgba(0,0,0,.08);
}

.sem-card {
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    color: white;
    padding: 25px;
    border-radius: 16px;
    margin: 20px 0;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

.sem-label {
    font-size: 14px;
    opacity: 0.9;
}

.sem-title {
    font-size: 26px;
    font-weight: bold;
    margin-top: 8px;
}

.card h2{
    margin-top:0;
}

.btn{
    display:inline-block;
    margin-top:20px;
    background:#2563eb;
    color:white;
    padding:12px 20px;
    border-radius:10px;
    text-decoration:none;
    font-weight:bold;
}

.table-wrap{
    background:white;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 2px 8px rgba(0,0,0,.08);
    margin-top:20px;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#eff6ff;
    color:#1e3a8a;
    text-align:left;
    padding:16px;
    font-size:15px;
}

td{
    padding:16px;
    border-top:1px solid #e2e8f0;
    color:#334155;
}

tr:hover{
    background:#f8fafc;
}

.section-title{
    font-size:24px;
    font-weight:700;
    color:#0f172a;
    margin-top:30px;
}

.jadwal-tag{
    background:#eff6ff;
    color:#2563eb;
    padding:6px 10px;
    border-radius:8px;
    font-size:14px;
    font-weight:600;
}
</style>

</head>

<body>

<div class="topbar">

    <div class="app-name">
        WombatLecturer
    </div>

    <div class="nav">
        <a href="dashboardDosen.php" class="active" >Beranda</a>
        <a href="jadwalDosen.php">Jadwal</a>
        <a href="kelola.php">Kelola</a>
    </div>

</div>

<div class="main">

    <h1>
        Halo, <?= htmlspecialchars($nama) ?>
    </h1>

    <p>
        Selamat datang di dashboard dosen.
    </p>

    <div class="sem-card">
        <div class="sem-label"><?= htmlspecialchars(trim($sem['Periode'] ?? 'Ganjil')) ?> <?= $sem['Tahun_Akademik'] ?? '2025' ?>/<?= ($sem['Tahun_Akademik'] ?? 2025) + 1 ?></div>
        <div class="sem-title">Tahun Akademik <?= $sem['Tahun_Akademik'] ?? '2025' ?>/<?= ($sem['Tahun_Akademik'] ?? 2025) + 1 ?></div>
    </div>
   
    <div class="table-header">
    <div class="section-title">Seluruh Mata Kuliah Semester Ini</div>
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
