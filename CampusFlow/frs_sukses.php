<?php
session_start();
if (!isset($_SESSION["id_user"]) || !isset($_SESSION['frs_result'])) {
    header("Location: dashboard.php"); exit;
}
$result   = $_SESSION['frs_result'];
$courses  = $result['courses'];
$totalSKS = array_sum(array_column($courses, 'SKS'));
function fmtTime($t){ return substr($t,0,5); }
?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FRS Berhasil – CampusFlow</title>

<style>

/* reset semua elemen */
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

/* TOPBAR - navigasi atas (simpel, cuma nama app) */

.topbar{
    background:#fff;
    border-bottom:1px solid #e2e8f0;
    padding:0 2.5rem;
    display:flex;
    align-items:center;
    height:4rem;
}

/* nama app */
.app-name{
    font-size:1.25rem;
    font-weight:800;
    color:#2563eb;
}

/* MAIN - area konten utama */

.main{
    flex:1;
    padding:2.5rem;
    max-width:50rem; /* lebih sempit, cocok buat halaman sukses */
    width:100%;
    margin:0 auto;
}

/* BANNER - kartu hijau besar tanda sukses */

.banner{
    background:linear-gradient(135deg,#16a34a,#15803d);
    border-radius:1rem;
    padding:2.25rem;
    color:#fff;
    text-align:center;
    margin-bottom:2rem;
    box-shadow:0 0.5rem 1.5rem rgba(22,163,74,.25);
}

/* lingkaran centang di banner */
.check-circle{
    width:4rem;
    height:4rem;
    background:rgba(255,255,255,.2);
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 1rem;
}

/* ikon centang SVG */
.check-circle svg{
    width:2rem;
    height:2rem;
    stroke:#fff;
    fill:none;
    stroke-width:2.5;
    stroke-linecap:round;
    stroke-linejoin:round;
}

/* judul "FRS Berhasil Disimpan" */
.banner-title{
    font-size:1.75rem;
    font-weight:800;
    margin-bottom:0.375rem;
}

/* subtitle semester */
.banner-sub{
    font-size:1.0625rem;
    opacity:.9;
}

/* pill kecil: total SKS terdaftar */
.sks-pill{
    display:inline-block;
    background:rgba(255,255,255,.2);
    border-radius:1.25rem;
    padding:0.375rem 1.125rem;
    font-size:1rem;
    font-weight:700;
    margin-top:0.75rem;
}

/* judul section "Mata Kuliah Dipilih" */
.section-title{
    font-size:1.25rem;
    font-weight:800;
    color:#0f172a;
    margin-bottom:1rem;
}

/* TABLE - tabel daftar matkul yang berhasil disimpan */

.table-wrap{
    background:#fff;
    border-radius:0.75rem;
    box-shadow:0 0.0625rem 0.375rem rgba(0,0,0,.08);
    overflow:hidden;
    margin-bottom:1.75rem;
}

table{
    width:100%;
    border-collapse:collapse;
}

/* header tabel */
th{
    padding:0.875rem 1.125rem;
    text-align:left;
    font-weight:700;
    color:#475569;
    font-size:0.9375rem;
    background:#f8fafc;
    border-bottom:2px solid #e2e8f0;
}

/* sel data */
td{
    padding:0.875rem 1.125rem;
    border-bottom:1px solid #f1f5f9;
    color:#334155;
    font-size:0.9375rem;
}

/* baris terakhir ga perlu border */
tr:last-child td{
    border-bottom:none;
}

/* BUTTONS - tombol navigasi bawah */

.btn-row{
    display:flex;
    gap:0.875rem;
}

/* base tombol */
.btn{
    display:inline-flex;
    align-items:center;
    gap:0.5rem;
    border-radius:0.625rem;
    padding:0.875rem 1.75rem;
    font-size:1rem;
    font-weight:700;
    text-decoration:none;
    cursor:pointer;
    border:none;
}

/* tombol outline (border biru, background putih) */
.btn-outline{
    background:#fff;
    color:#2563eb;
    border:2px solid #2563eb;
}

/* tombol primary (gradient biru-ungu) */
.btn-primary{
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    color:#fff;
    box-shadow:0 0.25rem 0.875rem rgba(37,99,235,.3);
}

</style>

</head>

<body>

<!-- TOPBAR: cuma nama app, ga ada nav karena ini halaman hasil -->

<div class="topbar">
    <div class="app-name">WombatStudent</div>
</div>

<!-- MAIN: konten halaman sukses -->

<div class="main">

    <!-- BANNER: kartu hijau tanda FRS berhasil -->

    <div class="banner">

        <div class="check-circle">
            <svg viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"/>
            </svg>
        </div>

        <div class="banner-title">FRS Berhasil Disimpan</div>
        <div class="banner-sub">Semester <?= htmlspecialchars($result['semester']) ?></div>
        <div class="sks-pill"><?= $totalSKS ?> SKS terdaftar</div>

    </div>

    <!-- TABLE: daftar matkul yang berhasil disimpan -->

    <div class="section-title">Mata Kuliah Dipilih</div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Mata Kuliah</th>
                    <th>SKS</th>
                    <th>Jadwal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($courses as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c['Id_MK']) ?></td>
                    <td><?= htmlspecialchars($c['NamaMK']) ?></td>
                    <td><?= $c['SKS'] ?></td>
                    <td><?= htmlspecialchars($c['Hari']) ?>, <?= fmtTime($c['Jam_Mulai']) ?>–<?= fmtTime($c['Jam_Selesai']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- BUTTONS: navigasi setelah sukses -->

    <div class="btn-row">
        <a href="history.php" class="btn btn-outline">Lihat Riwayat</a>
        <a href="dashboard.php" class="btn btn-primary">Kembali ke Beranda</a>
    </div>

</div>

</body>
</html>
