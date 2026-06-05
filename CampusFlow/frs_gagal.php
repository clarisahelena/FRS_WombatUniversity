<?php
session_start(); //memulai sesi
if (!isset($_SESSION["id_user"]) || !isset($_SESSION['frs_result'])) {
    header("Location: dashboard.php"); exit;
}
$result    = $_SESSION['frs_result'];
$courses   = $result['courses'];
$conflicts = $result['conflicts'];
$totalSKS  = array_sum(array_column($courses, 'SKS'));
$conflictIds = [];
foreach ($conflicts as $pair) {
    $conflictIds[$pair[0]['Id_MK']] = true;
    $conflictIds[$pair[1]['Id_MK']] = true;
}
function fmtTime($t){ return substr($t,0,5); }
?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>FRS Bentrok CampusFlow</title>

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

/* TOPBAR - navigasi atas simpel */

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
    max-width:56.25rem;
    width:100%;
    margin:0 auto;
}

/* BANNER - kartu merah tanda ada bentrok */

.banner{
    background:linear-gradient(135deg,#dc2626,#b91c1c);
    border-radius:1rem;
    padding:2.25rem;
    color:#fff;
    text-align:center;
    margin-bottom:2rem;
    box-shadow:0 0.5rem 1.5rem rgba(220,38,38,.25);
}

/* lingkaran warning di banner */
.warn-circle{
    width:4rem;
    height:4rem;
    background:rgba(255,255,255,.2);
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    margin:0 auto 1rem;
}

/* ikon warning SVG */
.warn-circle svg{
    width:2rem;
    height:2rem;
    stroke:#fff;
    fill:none;
    stroke-width:2.5;
    stroke-linecap:round;
    stroke-linejoin:round;
}

/* judul "Terdapat Bentrok Jadwal" */
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

/* pill info SKS + jumlah bentrok */
.sks-pill{
    display:inline-block;
    background:rgba(255,255,255,.2);
    border-radius:1.25rem;
    padding:0.375rem 1.125rem;
    font-size:1rem;
    font-weight:700;
    margin-top:0.75rem;
}

/* TWO-COL - layout 2 kolom: detail bentrok kiri, tabel kanan */

.two-col{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:1.5rem;
    margin-bottom:1.75rem;
}

/* judul section */
.section-title{
    font-size:1.25rem;
    font-weight:800;
    color:#0f172a;
    margin-bottom:1rem;
}

/* CONFLICT CARD - kartu detail bentrok tiap pasangan MK */

.conflict-card{
    background:#fff;
    border:1.5px solid #fca5a5; /* border merah muda */
    border-radius:0.75rem;
    padding:1.125rem;
    margin-bottom:0.75rem;
}

/* label "Bentrok - Senin" */
.conflict-label{
    font-size:0.8125rem;
    font-weight:700;
    color:#dc2626;
    text-transform:uppercase;
    letter-spacing:.4px;
    margin-bottom:0.625rem;
}

/* nama MK yang bentrok */
.conflict-mk{
    font-size:1rem;
    font-weight:700;
    color:#0f172a;
    margin-bottom:0.1875rem;
}

/* waktu MK yang bentrok */
.conflict-time{
    font-size:0.875rem;
    color:#64748b;
    margin-bottom:0.5rem;
}

/* teks "VS" pemisah antar MK bentrok */
.vs{
    font-size:0.8125rem;
    color:#dc2626;
    font-weight:700;
    text-align:center;
    margin:0.375rem 0;
}

/* TABLE - tabel semua MK yang dipilih */

.table-wrap{
    background:#fff;
    border-radius:0.75rem;
    box-shadow:0 0.0625rem 0.375rem rgba(0,0,0,.08);
    overflow:hidden;
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

/* baris yang bentrok: background merah muda */
.bentrok-row td{
    background:#fff5f5;
}

/* tag kecil "Bentrok" merah */
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

/* BUTTONS - tombol navigasi bawah */

.btn-row{
    display:flex;
    gap:0.875rem;
    margin-top:1.75rem;
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

/* tombol outline: border biru */
.btn-outline{
    background:#fff;
    color:#2563eb;
    border:2px solid #2563eb;
}

/* tombol danger: gradient merah */
.btn-danger{
    background:linear-gradient(135deg,#dc2626,#b91c1c);
    color:#fff;
    box-shadow:0 0.25rem 0.875rem rgba(220,38,38,.3);
}

</style>

</head>

<body>

<!-- TOPBAR: nama app aja -->

<div class="topbar">
    <div class="app-name">WombatStudent</div>
</div>

<!-- MAIN: konten halaman bentrok -->

<div class="main">

    <!-- BANNER: kartu merah peringatan bentrok -->

    <div class="banner">

        <div class="warn-circle">
            <svg viewBox="0 0 24 24">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
        </div>

        <div class="banner-title">Terdapat Bentrok Jadwal</div>
        <div class="banner-sub">Semester <?= htmlspecialchars($result['semester']) ?> &bull; FRS tetap tersimpan</div>
        <div class="sks-pill"><?= $totalSKS ?> SKS &bull; <?= count($conflicts) ?> bentrok</div>

    </div>

    <!-- TWO-COL: 2 kolom (detail bentrok + tabel semua MK) -->

    <div class="two-col">

        <!-- kolom kiri: detail tiap pasangan bentrok -->
        <div>

            <div class="section-title">Detail Bentrok</div>

            <?php foreach ($conflicts as $pair): ?>
            <div class="conflict-card">
                <div class="conflict-label">Bentrok – <?= htmlspecialchars($pair[0]['Hari']) ?></div>
                <div class="conflict-mk"><?= htmlspecialchars($pair[0]['NamaMK']) ?></div>
                <div class="conflict-time"><?= fmtTime($pair[0]['Jam_Mulai']) ?>–<?= fmtTime($pair[0]['Jam_Selesai']) ?></div>
                <div class="vs">VS</div>
                <div class="conflict-mk"><?= htmlspecialchars($pair[1]['NamaMK']) ?></div>
                <div class="conflict-time"><?= fmtTime($pair[1]['Jam_Mulai']) ?>–<?= fmtTime($pair[1]['Jam_Selesai']) ?></div>
            </div>
            <?php endforeach; ?>

        </div>

        <!-- kolom kanan: tabel semua matkul + tanda mana yang bentrok -->
        <div>

            <div class="section-title">Semua Mata Kuliah</div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>SKS</th>
                            <th>Jadwal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($courses as $c): $b = isset($conflictIds[$c['Id_MK']]); ?>
                        <tr <?= $b ? 'class="bentrok-row"' : '' ?>>
                            <td>
                                <?= htmlspecialchars($c['NamaMK']) ?>
                                <?php if($b): ?><span class="bentrok-tag">Bentrok</span><?php endif; ?>
                            </td>
                            <td><?= $c['SKS'] ?></td>
                            <td><?= htmlspecialchars($c['Hari']) ?>, <?= fmtTime($c['Jam_Mulai']) ?>–<?= fmtTime($c['Jam_Selesai']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </div>

    <!-- BUTTONS: navigasi -->

    <div class="btn-row">
        <a href="frs.php" class="btn btn-outline">Ubah FRS</a>
        <a href="history.php" class="btn btn-danger">Lihat Riwayat</a>
    </div>

</div>

</body>
</html>
