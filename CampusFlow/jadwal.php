<?php
session_start(); //untuk memulai session user
if (!isset($_SESSION["id_user"])) { header("Location: ../index.php"); exit; } //untuk cek apakah session punya id user, kalau ga ada balik ke halaman login dan hentikan program
require_once "../Koneksi.php"; //untuk menyambungkan ke koneksi database

$npm   = $_SESSION["id_user"];
$periode = $_GET["periode"] ?? ($_SESSION["periode"] ?? "1");
$semester = $_GET["semester"] ?? ($_SESSION["semester"] ?? "25");

$id_sem = $semester . "-" . $periode;
//query untuk ambil semester yang sedang berlangsung
$stmt = $conn->prepare("
      SELECT Periode, Tahun_Akademik 
      FROM Semester 
      WHERE Id_Sem = ? 
");
//untuk mengisi parameter ? dengan id_sem yang sedang berlangsung dan lakukan execute
$stmt->execute([$id_sem]);
//untuk mengambil 1 baris data
$sem = $stmt->fetch(PDO::FETCH_ASSOC);
//mengambil hari, jam, mata kuliah, ruangan, dosen, sks yang berlangsung untuk 1 mstkul dengan jadwal tertentu
//untuk keluarkan jadwal, ambil tabel jadwal, matakuliah, dan dosen
$stmt = $conn->prepare("
    SELECT j.Hari, j.Jam_Mulai, j.Jam_Selesai, mk.Id_MK, j.Ruangan, mk.Nama AS NamaMK, mk.SKS, d.Nama AS NamaDosen
    FROM Jadwal j
    JOIN MataKuliah mk ON j.Id_MK = mk.Id_MK
    JOIN Dosen d ON j.NID = d.NID
    JOIN Enroll e ON e.Id_MK = mk.Id_MK AND e.Id_Sem = j.Id_Sem
    WHERE j.Id_Sem = ? AND e.NPM = ?
    ORDER BY
        CASE j.Hari WHEN 'Senin' THEN 1 WHEN 'Selasa' THEN 2 WHEN 'Rabu' THEN 3
                    WHEN 'Kamis' THEN 4 WHEN 'Jumat' THEN 5 WHEN 'Sabtu' THEN 6 ELSE 7 END,
        j.Jam_Mulai
");
$stmt->execute([$id_sem, $npm]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC); //jadiin array of array

function fmtTime($t){ return substr($t,0,5); } //ambil ham dan menir saja untuk waktu mulai dan selesai
?>

<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Jadwal – CampusFlow</title>

<style>

/* reset semua elemen biar ga ada margin/padding bawaan browser */
*{
    box-sizing:border-box; /* ukuran termasuk padding+border */
    margin:0;
    padding:0;
    font-family:'Calibri',Calibri,sans-serif;
}

/* body: latar abu terang, tinggi minimal full layar */
body{
    background:#f1f5f9;
    min-height:100vh;
    display:flex;
    flex-direction:column; /* konten numpuk dari atas ke bawah */
}

/* TOPBAR - bar navigasi paling atas */

.topbar{
    background:#fff;
    border-bottom:1px solid #e2e8f0; /* garis pemisah bawah */
    padding:0 2.5rem;
    display:flex;
    align-items:center; /* rata tengah vertikal */
    justify-content:space-between; /* nama kiri, nav kanan */
    height:4rem;
}

/* nama app di kiri atas */
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

/* tiap link navigasi */
.nav-links a{
    padding:0.625rem 1.25rem;
    border-radius:0.5rem;
    font-size:1rem;
    font-weight:600;
    color:#64748b;
    text-decoration:none;
    transition:background .15s; /* animasi halus pas hover */
}

/* hover: warna berubah saat mouse lewat */
.nav-links a:hover{
    background:#f1f5f9;
    color:#0f172a;
}

/* link halaman yang lagi aktif */
.nav-links a.active{
    background:#eff6ff;
    color:#2563eb;
}

/* MAIN - area konten utama */

.main{
    flex:1;
    padding:2.5rem;
    max-width:75rem; /* batas lebar supaya ga melar */
    width:100%;
    margin:0 auto; /* tengahin horizontal */
}

/* judul halaman "Jadwal Kuliah" */
.page-title{
    font-size:1.875rem;
    font-weight:800;
    color:#0f172a;
    margin-bottom:0.375rem;
}

/* subtitle kecil di bawah judul (info semester) */
.page-sub{
    font-size:1.0625rem;
    color:#64748b;
    margin-bottom:1.75rem;
}

/* TABLE - tabel jadwal kuliah */

/* pembungkus tabel, dikasih background putih + rounded corner */
.table-wrap{
    background:#fff;
    border-radius:0.75rem;
    box-shadow:0 0.0625rem 0.375rem rgba(0,0,0,.08);
    overflow:hidden; /* biar rounded corner keliatan */
}

/* tabel full lebar, border digabung */
table{
    width:100%;
    border-collapse:collapse;
}

/* baris header tabel */
thead tr{
    background:#f8fafc;
    border-bottom:2px solid #e2e8f0;
}

/* sel header: judul kolom */
th{
    padding:0.875rem 1.125rem;
    text-align:left;
    font-weight:700;
    color:#475569;
    font-size:0.9375rem;
    white-space:nowrap; /* ga boleh wrap ke baris baru */
}

/* sel data biasa */
td{
    padding:0.875rem 1.125rem;
    border-bottom:1px solid #f1f5f9;
    color:#334155;
    font-size:0.9375rem;
    vertical-align:middle; /* teks rata tengah vertikal */
}

/* baris terakhir ga perlu garis bawah */
tr:last-child td{
    border-bottom:none;
}

/* efek hover: baris berubah warna saat mouse di atasnya */
tr:hover td{
    background:#fafbff;
}

/* kolom hari: teks tebal, ga wrap */
.hari-cell{
    font-weight:700;
    color:#0f172a;
    white-space:nowrap;
}

/* kolom waktu: warna biru, tebal */
.waktu-cell{
    white-space:nowrap;
    color:#2563eb;
    font-weight:600;
}

/* kolom kode MK: warna ungu, tebal */
.kode-cell{
    font-weight:700;
    color:#7c3aed;
    white-space:nowrap;
}

/* kolom SKS: rata tengah, tebal */
.sks-cell{
    text-align:center;
    font-weight:700;
}

/* EDIT BUTTON - tombol "Edit FRS" di kanan atas */

.edit-btn{
    display:inline-flex;
    align-items:center;
    gap:0.5rem;
    background:#2563eb;
    color:#fff;
    border-radius:0.625rem;
    padding:0.75rem 1.5rem;
    font-size:1rem;
    font-weight:700;
    text-decoration:none;
    transition:opacity .15s;
    box-shadow:0 0.25rem 0.875rem rgba(37,99,235,.3);
}

/* hover tombol: agak transparan */
.edit-btn:hover{
    opacity:.9;
}

/* ikon SVG di dalam tombol edit */
.edit-btn svg{
    width:1.0625rem;
    height:1.0625rem;
    stroke:#fff;
    fill:none;
    stroke-width:2.5;
    stroke-linecap:round;
    stroke-linejoin:round;
}

</style>

</head>

<body>

<!-- TOPBAR: navigasi atas dengan nama app dan menu -->

<div class="topbar">

    <div class="app-name">WombatStudent</div>

    <!-- menu navigasi -->
    <nav class="nav-links">
        <a href="dashboard.php">Beranda</a>
        <a href="jadwal.php" class="active">Jadwal</a>
        <a href="history.php">Riwayat</a>
    </nav>

</div>

<!-- MAIN: konten utama halaman jadwal -->

<div class="main">

    <!-- HEADER: judul halaman + tombol edit FRS sejajar -->

    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.375rem">

        <div class="page-title">Jadwal Kuliah</div>

        <!-- tombol buat edit FRS, ada ikon pensil -->
        <a href="frs.php" class="edit-btn">
            <svg viewBox="0 0 24 24">
                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
            </svg>
            Edit FRS
        </a>

    </div>

    <!-- info semester yang aktif -->
    <div class="page-sub">
        Semester <?= htmlspecialchars(trim($sem['Periode'] ?? 'Ganjil')) ?>
        <?= $sem['Tahun_Akademik'] ?? '2025' ?>/<?= ($sem['Tahun_Akademik'] ?? 2025)+1 ?>
    </div>

    <!-- TABLE: tabel jadwal kuliah mahasiswa semester ini -->

    <div class="table-wrap">
        <table>
            <!-- header kolom -->
            <thead>
                <tr>
                    <th>Hari</th>
                    <th>Waktu</th>
                    <th>Kode</th>
                    <th>Ruangan</th>
                    <th>Nama Mata Kuliah</th>
                    <th>SKS</th>
                    <th>Nama Dosen</th>
                </tr>
            </thead>
            <!-- isi tabel: loop tiap jadwal -->
            <tbody>
                <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="hari-cell"><?= htmlspecialchars($r['Hari']) ?></td>
                    <td class="waktu-cell"><?= fmtTime($r['Jam_Mulai']) ?>–<?= fmtTime($r['Jam_Selesai']) ?></td>
                    <td class="kode-cell"><?= htmlspecialchars($r['Id_MK']) ?></td>
                    <td><?= htmlspecialchars($r['Ruangan']) ?></td>
                    <td><?= htmlspecialchars($r['NamaMK']) ?></td>
                    <td class="sks-cell"><?= $r['SKS'] ?></td>
                    <td><?= htmlspecialchars($r['NamaDosen']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

</body>
</html>
