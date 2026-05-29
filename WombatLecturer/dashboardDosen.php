<?php
session_start();

if (!isset($_SESSION["id_user"])) {
    header("Location: ../index.php");
    exit;
}

$nama = $_SESSION["nama"];
$role = $_SESSION["role"];

if ($role != "dosen") {
    header("Location: ../CampusFlow/dashboard.php");
    exit;
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

.nav a{
    text-decoration:none;
    margin-left:20px;
    color:#475569;
    font-weight:600;
}

.main{
    padding:40px;
}

.card{
    background:white;
    padding:30px;
    border-radius:14px;
    width:320px;
    box-shadow:0 2px 8px rgba(0,0,0,.08);
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

</style>
</head>

<body>

<div class="topbar">

    <div class="app-name">
        WombatLecturer
    </div>

    <div class="nav">
        <a href="dashboardDosen.php">Beranda</a>
        <a href="jadwalDosen.php">Jadwal</a>
        <a href="../logout.php">Logout</a>
    </div>

</div>

<div class="main">

    <h1>
        Halo, <?= htmlspecialchars($nama) ?>
    </h1>

    <p>
        Selamat datang di dashboard dosen.
    </p>

    <div class="card">

        <h2>Jadwal Mengajar</h2>

        <p>
            Lihat jadwal mata kuliah yang Anda ajar semester ini.
        </p>

        <a href="jadwalDosen.php" class="btn">
            Lihat Jadwal
        </a>

    </div>

</div>

</body>
</html>