<?php
session_start();

if (!isset($_SESSION["id_user"])) {
    header("Location: ../index.php");
    exit;
}
require_once "../Koneksi.php";

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

/* body: font default, latar abu terang */
body{
    font-family:Calibri;
    background:#f1f5f9;
    margin:0;
}

/* TOPBAR - bar navigasi paling atas */

.topbar{
    height:4rem;
    background:white;
    border-bottom:1px solid #ddd; /* garis pemisah bawah */
    display:flex;
    align-items:center; /* rata tengah vertikal */
    justify-content:space-between; /* nama kiri, nav kanan */
    padding:0 2.5rem;
}

/* nama app di pojok kiri */
.app-name{
    font-size:1.375rem;
    font-weight:bold;
    color:#2563eb;
}

/* container link navigasi */
.nav{
    display:flex;
    gap:0.5rem;
}

/* tiap link navigasi */
.nav a{
    text-decoration:none;
    color:#475569;
    font-weight:600;
    padding:0.625rem 1.125rem;
    border-radius:0.625rem;
    transition:.2s; /* animasi halus saat hover */
}

/* hover: background berubah saat mouse lewat */
.nav a:hover{
    background:#f1f5f9;
}

/* link halaman yang lagi aktif */
.nav a.active{
    background:#dbeafe;
    color:#2563eb;
}

/* MAIN - area konten utama */

.main{
    max-width:68.75rem; /* batas lebar */
    margin:auto; /* tengahin */
    padding:2rem 2.5rem;
}

/* heading sapaan "Halo, nama" */
.main h1{
    font-size:1.625rem;
    color:#0f172a;
    margin-bottom:0.25rem;
}

/* subtitle di bawah sapaan */
.main p{
    color:#64748b;
    font-size:0.9375rem;
    margin-bottom:1.5rem;
}

/* SEMESTER CARD - kartu info semester gradient biru-ungu */

.sem-card {
    background: linear-gradient(135deg, #2563eb, #7c3aed);
    color: white;
    padding: 1.375rem 1.75rem;
    border-radius: 1rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 0.625rem 1.5rem rgba(0,0,0,0.15); /* bayangan ngambang */
}

/* label kecil semester */
.sem-label {
    font-size: 0.875rem;
    opacity: 0.9;
}

/* judul besar semester */
.sem-title {
    font-size: 1.375rem;
    font-weight: bold;
    margin-top: 0.25rem;
}

/* TABLE - tabel daftar mata kuliah */

/* pembungkus tabel: background putih + rounded */
.table-wrap{
    background:white;
    border-radius:0.875rem;
    overflow:hidden; /* biar rounded corner keliatan */
    box-shadow:0 0.125rem 0.5rem rgba(0,0,0,.08);
}

/* tabel full lebar */
table{
    width:100%;
    border-collapse:collapse;
}

/* header tabel: background biru muda */
th{
    background:#eff6ff;
    color:#1e3a8a;
    text-align:left;
    padding:1rem;
    font-size:0.9375rem;
}

/* sel data */
td{
    padding:1rem;
    border-top:1px solid #e2e8f0; /* garis tipis pemisah */
    color:#334155;
}

/* efek hover: baris berubah warna saat mouse lewat */
tr:hover{
    background:#f8fafc;
}

/* judul section "Seluruh Jadwal Semester Ini" */
.section-title{
    font-size:1.375rem;
    font-weight:700;
    color:#0f172a;
    margin-bottom:1rem;
}

/* badge/tag jadwal (misal: "Senin, 10:00-12:00") */
.jadwal-tag{
    background:#eff6ff;
    color:#2563eb;
    padding:0.3125rem 0.625rem;
    border-radius:0.375rem;
    font-size:0.875rem;
    font-weight:600;
}

</style>

</head>

<body>

<!-- TOPBAR: navigasi atas, nama app + link menu -->

<div class="topbar">

    <div class="app-name">
        WombatLecturer
    </div>

    <!-- navigasi: Beranda, Jadwal, Kelola -->
    <div class="nav">
        <a href="/CampusFlow/WombatLecturer/dashboardDosen.php" class="active">Beranda</a>
        <a href="/CampusFlow/WombatLecturer/jadwalDosen.php">Jadwal</a>
        <a href="/CampusFlow/WombatLecturer/kelola.php">Kelola</a>
    </div>

</div>

<!-- MAIN: konten utama halaman dashboard dosen -->

<div class="main">

    <!-- GREETING: sapaan ke dosen -->

    <h1>
        Halo, <?= htmlspecialchars($nama) ?>
    </h1>

    <p>
        Selamat datang di dashboard dosen.
    </p>

    <!-- SEMESTER: kartu info semester aktif -->

    <div class="sem-card">
        <div class="sem-label">
            <?= htmlspecialchars(trim($sem['Periode'] ?? 'Ganjil')) ?>
            <?= $sem['Tahun_Akademik'] ?? '2025' ?>/<?= ($sem['Tahun_Akademik'] ?? 2025) + 1 ?>
        </div>
        <div class="sem-title">
            Tahun Akademik <?= $sem['Tahun_Akademik'] ?? '2025' ?>/<?= ($sem['Tahun_Akademik'] ?? 2025) + 1 ?>
        </div>
    </div>

    <!-- TABLE: tabel semua jadwal semester ini -->

    <div class="section-title">Seluruh Jadwal Semester Ini</div>

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
                    <td>
                        <span class="jadwal-tag">
                            <?= htmlspecialchars($mk['Hari']) ?>, <?= fmtTime($mk['Jam_Mulai']) ?>–<?= fmtTime($mk['Jam_Selesai']) ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($mk['NamaDosen']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
