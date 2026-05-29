<?php
session_start();

//memeriksa apakah user sudah login
if (!isset($_SESSION["id_user"])) {
    header("Location: index.php");
    exit;
}

require_once "Koneksi.php";

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
$id_sem = '26-1';

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
    JOIN MataKuliah mk
        ON j.Id_MK = mk.Id_MK
    WHERE j.Id_Sem = ?
    AND j.NID = ?
    ORDER BY 
        FIELD(j.Hari,
            'Senin',
            'Selasa',
            'Rabu',
            'Kamis',
            'Jumat',
            'Sabtu'
        ),
        j.Jam_Mulai
");

$stmt->execute([$id_sem, $nid]);

$jadwalDosen = $stmt->fetchAll(PDO::FETCH_ASSOC);

// hitung total
$totalMK = count($jadwalDosen);
$totalSKS = array_sum(array_column($jadwalDosen, 'SKS'));

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

*{
    box-sizing:border-box;
    margin:0;
    padding:0;
    font-family:'Calibri',sans-serif;
}

body{
    background:#f1f5f9;
    min-height:100vh;
}

/* TOPBAR */

.topbar{
    background:#fff;
    border-bottom:1px solid #e2e8f0;
    height:64px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:0 40px;
}

.app-name{
    font-size:22px;
    font-weight:800;
    color:#2563eb;
}

.nav-links{
    display:flex;
    gap:10px;
}

.nav-links a{
    text-decoration:none;
    color:#64748b;
    padding:10px 18px;
    border-radius:8px;
    font-weight:600;
    transition:.2s;
}

.nav-links a:hover{
    background:#f1f5f9;
}

.nav-links a.active{
    background:#dbeafe;
    color:#2563eb;
}

/* MAIN */

.main{
    max-width:1200px;
    margin:auto;
    padding:40px;
}

.greeting{
    font-size:32px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:8px;
}

.subtitle{
    color:#64748b;
    margin-bottom:28px;
    font-size:17px;
}

/* SEMESTER CARD */

.sem-card{
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    color:#fff;
    border-radius:18px;
    padding:30px;
    margin-bottom:30px;
    box-shadow:0 10px 25px rgba(37,99,235,.25);
}

.sem-label{
    font-size:15px;
    opacity:.9;
    margin-bottom:8px;
}

.sem-title{
    font-size:28px;
    font-weight:800;
}

/* STAT CARD */

.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;
    margin-bottom:30px;
}

.stat-card{
    background:#fff;
    border-radius:14px;
    padding:24px;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
}

.stat-title{
    color:#64748b;
    font-size:15px;
    margin-bottom:10px;
}

.stat-value{
    font-size:34px;
    font-weight:800;
    color:#0f172a;
}

/* TABLE */

.section-title{
    font-size:22px;
    font-weight:800;
    color:#0f172a;
    margin-bottom:16px;
}

.table-wrap{
    background:#fff;
    border-radius:14px;
    overflow:hidden;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
}

table{
    width:100%;
    border-collapse:collapse;
}

thead{
    background:#f8fafc;
}

th{
    text-align:left;
    padding:16px;
    color:#475569;
    font-size:15px;
    border-bottom:2px solid #e2e8f0;
}

td{
    padding:16px;
    border-bottom:1px solid #f1f5f9;
    color:#334155;
}

tr:last-child td{
    border-bottom:none;
}

tr:hover td{
    background:#fafbff;
}

.jadwal-tag{
    display:inline-block;
    background:#eff6ff;
    color:#2563eb;
    padding:5px 10px;
    border-radius:6px;
    font-size:14px;
    font-weight:600;
}

.empty{
    text-align:center;
    padding:40px;
    color:#64748b;
}

</style>
</head>

<body>

<!-- TOPBAR -->

<div class="topbar">

    <div class="app-name">
        WombatLecturer
    </div>

    <nav class="nav-links">
        <a href="dashboard_dosen.php">Beranda</a>
        <a href="jadwal_dosen.php" class="active">Jadwal Mengajar</a>
        <a href="logout.php">Logout</a>
    </nav>

</div>

<!-- MAIN -->

<div class="main">

    <!-- GREETING -->

    <div class="greeting">
        Halo, <?= htmlspecialchars(explode(' ', $nama)[0]) ?>
    </div>

    <div class="subtitle">
        Berikut jadwal mata kuliah yang Anda ajar semester ini.
    </div>

    <!-- SEMESTER -->

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