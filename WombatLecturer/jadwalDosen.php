<?php
session_start();

//memeriksa apakah user sudah login
if (!isset($_SESSION["id_user"])) {
    header("Location: ../index.php");
    exit;
}

require_once "../Koneksi.php";

// ambil data session
$nama = $_SESSION["nama"];
$role = $_SESSION["role"];
$nid  = $_SESSION["id_user"];

// keamanan: hanya dosen yang boleh akses
if ($role != "dosen") {
    header("Location: dashboard.php");
    exit;
}

// semester aktif
$periode = $_GET["periode"] ?? ($_SESSION["periode"] ?? "1");
$semester = $_GET["semester"] ?? ($_SESSION["semester"] ?? "25");

$id_sem = $semester . "-" . $periode;


// ambil info semester
$stmt = $conn->prepare("
    SELECT Periode, Tahun_Akademik
    FROM Semester
    WHERE Id_Sem = ?
");

$stmt->execute([$id_sem]);

$sem = $stmt->fetch(PDO::FETCH_ASSOC);

// ambil jadwal dosen
$stmt = $conn->prepare("
    SELECT 
        mk.Id_MK,
        mk.Nama AS NamaMK,
        mk.SKS,
        j.Hari,
        j.Jam_Mulai,
        j.Jam_Selesai,
        j.Ruangan
    FROM Jadwal j
    JOIN MataKuliah mk ON j.Id_MK = mk.Id_MK
    WHERE j.Id_Sem = ?
    AND j.NID = ?
    AND mk.Status_Aktif = 1
    ORDER BY 
        CASE j.Hari
            WHEN 'Senin' THEN 1
            WHEN 'Selasa' THEN 2
            WHEN 'Rabu' THEN 3
            WHEN 'Kamis' THEN 4
            WHEN 'Jumat' THEN 5
            WHEN 'Sabtu' THEN 6
        END,
        j.Jam_Mulai
");

$stmt->execute([$id_sem, $nid]);

$jadwalDosen = $stmt->fetchAll(PDO::FETCH_ASSOC);

$MKDosen = $conn->prepare("
    SELECT SUM(mk.SKS) AS JumlahSKS, COUNT(mk.Id_MK) AS JumlahMK
    FROM Detail_Akademik AS DA
    JOIN Matakuliah AS MK ON MK.Id_MK = DA.Id_MK
    WHERE DA.NID = ? AND DA.Id_Sem = ? AND MK.Status_Aktif = 1
");

$MKDosen->execute([$nid, $id_sem]);
$dataMK = $MKDosen->fetch(PDO::FETCH_ASSOC);

// hitung total
$totalMK = $dataMK['JumlahMK'];
$totalSKS = $dataMK['JumlahSKS'];

// format jam
function fmtTime($t) {
    return substr($t, 0, 5);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Jadwal Dosen</title>

<style>

/* reset semua elemen biar konsisten di semua browser */
*{
    box-sizing:border-box;
    margin:0;
    padding:0;
    font-family:'Calibri',sans-serif;
}

/* base font size untuk perhitungan rem */
html{
    font-size:16px;
}

/* body: latar abu terang, minimal full layar */
body{
    background:#f1f5f9;
    min-height:100vh;
}

/* TOPBAR - bar navigasi paling atas */

.topbar{
    background:#fff;
    border-bottom:1px solid #e2e8f0; /* garis pemisah bawah */
    height:4rem;
    display:flex;
    align-items:center; /* rata tengah vertikal */
    justify-content:space-between; /* nama kiri, nav kanan */
    padding:0 2.5rem;
}

/* nama app di pojok kiri */
.app-name{
    font-size:1.375rem;
    font-weight:800;
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
    max-width:68.75rem; /* batas lebar supaya ga melar */
    margin:auto; /* tengahin horizontal */
    padding:2rem 2.5rem;
}

/* teks sapaan "Halo, nama" */
.greeting{
    font-size:1.625rem;
    font-weight:800;
    color:#0f172a;
    margin-bottom:0.375rem;
}

/* subtitle kecil di bawah sapaan */
.subtitle{
    color:#64748b;
    margin-bottom:1.5rem;
    font-size:0.9375rem;
}

/* SEMESTER CARD - kartu info semester dengan gradient biru-ungu */

.sem-card{
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    color:#fff;
    border-radius:1rem;
    padding:1.375rem 1.75rem;
    margin-bottom:1.5rem;
    box-shadow:0 0.625rem 1.5rem rgba(37,99,235,.25); /* bayangan biar ngambang */
}

/* label kecil semester (misal: "Ganjil 2025/2026") */
.sem-label{
    font-size:0.875rem;
    opacity:.9;
    margin-bottom:0.25rem;
}

/* judul besar semester */
.sem-title{
    font-size:1.375rem;
    font-weight:800;
}

/* STAT CARD - kartu statistik (total MK, total SKS) */

.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(12.5rem,1fr)); /* responsive grid */
    gap:1rem;
    margin-bottom:1.5rem;
}

/* satu kartu stat */
.stat-card{
    background:#fff;
    border-radius:0.75rem;
    padding:1.25rem;
    box-shadow:0 0.125rem 0.5rem rgba(0,0,0,.06);
}

/* judul stat (misal: "Total Mata Kuliah") */
.stat-title{
    color:#64748b;
    font-size:0.875rem;
    margin-bottom:0.5rem;
}

/* angka besar stat */
.stat-value{
    font-size:1.75rem;
    font-weight:800;
    color:#0f172a;
}

/* TABLE - tabel jadwal mengajar */

/* judul section "Jadwal Mengajar" */
.section-title{
    font-size:1.375rem;
    font-weight:800;
    color:#0f172a;
    margin-bottom:1rem;
}

/* pembungkus tabel: background putih + rounded */
.table-wrap{
    background:#fff;
    border-radius:0.875rem;
    overflow:hidden; /* biar border-radius keliatan */
    box-shadow:0 0.125rem 0.5rem rgba(0,0,0,.06);
}

/* tabel full lebar */
table{
    width:100%;
    border-collapse:collapse;
}

/* background header tabel */
thead{
    background:#f8fafc;
}

/* sel header */
th{
    text-align:left;
    padding:1rem;
    color:#475569;
    font-size:0.9375rem;
    border-bottom:2px solid #e2e8f0;
}

/* sel data */
td{
    padding:1rem;
    border-bottom:1px solid #f1f5f9;
    color:#334155;
}

/* baris terakhir ga perlu border bawah */
tr:last-child td{
    border-bottom:none;
}

/* efek hover: baris berubah warna saat mouse di atasnya */
tr:hover td{
    background:#fafbff;
}

/* badge/tag jadwal (misal: "Senin, 10:00-12:00") */
.jadwal-tag{
    display:inline-block;
    background:#eff6ff;
    color:#2563eb;
    padding:0.3125rem 0.625rem;
    border-radius:0.375rem;
    font-size:0.875rem;
    font-weight:600;
}

/* pesan kosong kalau belum ada jadwal */
.empty{
    text-align:center;
    padding:2.5rem;
    color:#64748b;
}

</style>
</head>

<body>

<!-- TOPBAR: bar navigasi atas, nama app di kiri, link menu di kanan -->

<div class="topbar">

    <div class="app-name">
        WombatLecturer
    </div>

    <!-- navigasi: Beranda, Jadwal, Kelola -->
    <nav class="nav">
        <a href="dashboardDosen.php">Beranda</a>
        <a href="jadwalDosen.php" class="active">Jadwal</a>
        <a href="kelola.php">Kelola</a>
    </nav>

</div>

<!-- MAIN: area konten utama halaman jadwal dosen -->

<div class="main">

    <!-- GREETING: sapaan ke dosen pake nama depan -->

    <div class="greeting">
        Halo, <?= htmlspecialchars(explode(' ', $nama)[0]) ?>
    </div>

    <div class="subtitle">
        Berikut jadwal mata kuliah yang Anda ajar semester ini.
    </div>

    <!-- SEMESTER: kartu info semester yang lagi aktif -->

    <div class="sem-card">

        <div class="sem-label">
            <?= htmlspecialchars($sem['Periode'] ?? 'Ganjil') ?>
            <?= $sem['Tahun_Akademik'] ?? '2025' ?>
            /
            <?= ($sem['Tahun_Akademik'] ?? 2025) + 1 ?>
        </div>

        <div class="sem-title">
            Tahun Akademik
            <?= $sem['Tahun_Akademik'] ?? '2025' ?>
            /
            <?= ($sem['Tahun_Akademik'] ?? 2025) + 1 ?>
        </div>

    </div>

    <!-- STATS -->

    <div class="stats">

        <div class="stat-card">
            <div class="stat-title">
                Total Mata Kuliah
            </div>

            <div class="stat-value">
                <?= $totalMK ?>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-title">
                Total SKS Mengajar
            </div>

            <div class="stat-value">
                <?= $totalSKS ?>
            </div>
        </div>

    </div>

    <!-- TABLE -->

    <div class="section-title">
        Jadwal Mengajar
    </div>

    <div class="table-wrap">

        <table>

            <thead>
                <tr>
                    <th>Kode MK</th>
                    <th>Mata Kuliah</th>
                    <th>SKS</th>
                    <th>Hari</th>
                    <th>Jam</th>
                    <th>Ruangan</th>
                </tr>
            </thead>

            <tbody>

            <?php if(count($jadwalDosen) > 0): ?>

                <?php foreach($jadwalDosen as $j): ?>

                <tr>

                    <td>
                        <?= htmlspecialchars($j['Id_MK']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($j['NamaMK']) ?>
                    </td>

                    <td>
                        <?= $j['SKS'] ?>
                    </td>

                    <td>
                        <span class="jadwal-tag">
                            <?= htmlspecialchars($j['Hari']) ?>
                        </span>
                    </td>

                    <td>
                        <?= fmtTime($j['Jam_Mulai']) ?>
                        -
                        <?= fmtTime($j['Jam_Selesai']) ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($j['Ruangan']) ?>
                    </td>

                </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="6" class="empty">
                        Tidak ada jadwal mengajar pada semester ini.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

</div>

</body>
</html>