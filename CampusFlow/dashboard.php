<?php
session_start(); //membuka session user saat ini
if (!isset($_SESSION["id_user"])) {//kalo belom ada id, berarti user belom login
    header("Location: ../index.php");
    exit;
}
require_once "../Koneksi.php";
//mengambil data dari halaman login (session)
$nama  = $_SESSION["nama"];
$role  = $_SESSION["role"];
$npm   = $_SESSION["id_user"];


$periode = $_GET["periode"] ?? ($_SESSION["periode"] ?? "1");
$semester = $_GET["semester"] ?? ($_SESSION["semester"] ?? "25");

$id_sem = $semester . "-" . $periode;
// $id_sem menyimpan kode semester aktif.
// '26-1' adalah teks/string.
$stmt = $conn->prepare("SELECT Periode, Tahun_Akademik 
                        FROM Semester 
                        WHERE Id_Sem = ?"); //ambil semester yang sedang berlangsung
$stmt->execute([$id_sem]);//execute = menjalankan query
$sem = $stmt->fetch(PDO::FETCH_ASSOC);

//-> memanggil fungsi/method dari object
//? adalah placeholder yang nantinya nilainya akan di isi
// query untuk mengambil database matakuliah yang dibuka pada frs sekaranhg
$stmt = $conn->prepare("
    SELECT mk.Id_MK, mk.Nama AS NamaMK, mk.SKS,
           d.Nama AS NamaDosen,
           j.Hari, j.Jam_Mulai, j.Jam_Selesai, j.Ruangan, j.Jadwal_Ke
    FROM Detail_Akademik da
    JOIN MataKuliah mk ON da.Id_MK = mk.Id_MK
    JOIN Dosen d ON da.NID = d.NID
    LEFT JOIN Jadwal j ON j.Id_MK = da.Id_MK 
                       AND j.Id_Sem = da.Id_Sem 
                       AND j.NID = da.NID
    WHERE da.Id_Sem = ? AND mk.Status_Aktif = 1
    ORDER BY mk.Nama, j.Jadwal_Ke
");
$stmt->execute([$id_sem]);
$allCourses = $stmt->fetchAll(PDO::FETCH_ASSOC);

function fmtTime($t) {
    return substr($t, 0, 5);
}

// Group by Id_MK supaya 1 matkul = 1 baris meskipun punya banyak jadwal
$grouped = [];
foreach ($allCourses as $row) {
    $id = $row['Id_MK'];
    if (!isset($grouped[$id])) {
        $grouped[$id] = $row;
        $grouped[$id]['jadwal'] = [];
    }
    $grouped[$id]['jadwal'][] = $row['Hari'] . ', ' . fmtTime($row['Jam_Mulai']) . '–' . fmtTime($row['Jam_Selesai']);
}
$totalMK = count($grouped);
$totalSKS = array_sum(array_column($grouped, 'SKS'));
?>
<!DOCTYPE html>
<html lang="id">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Dashboard CampusFlow</title>

<style>

/* reset semua elemen biar ga ada margin/padding bawaan browser */
*{
    box-sizing:border-box; /* ukuran elemen udah termasuk padding dan border */
    margin:0;
    padding:0;
    font-family:'Calibri',Calibri,sans-serif;
}

/* body: latar belakang abu terang, tinggi minimal sehalaman penuh */
body{
    background:#f1f5f9;
    min-height:100vh;
    display:flex; /* pakai flexbox supaya layout numpuk vertikal */
    flex-direction:column;
}

/* TOPBAR - bar navigasi paling atas */

.topbar{
    background:#fff;
    border-bottom:1px solid #e2e8f0; /* garis tipis bawah sebagai pemisah */
    padding:0 2.5rem;
    display:flex; /* isi topbar sejajar horizontal */
    align-items:center; /* rata tengah vertikal */
    justify-content:space-between; /* nama app di kiri, nav di kanan */
    height:4rem;
}

/* nama aplikasi di pojok kiri topbar */
.app-name{
    font-size:1.25rem;
    font-weight:800;
    color:#2563eb;
}

/* container link-link navigasi */
.nav-links{
    display:flex;
    gap:0.375rem; /* jarak antar link */
}

/* styling tiap link navigasi */
.nav-links a{
    padding:0.625rem 1.25rem;
    border-radius:0.5rem;
    font-size:1rem;
    font-weight:600;
    color:#64748b;
    text-decoration:none; /* hilangin garis bawah link */
    transition:background .15s; /* animasi halus saat hover */
}

/* efek hover: background berubah saat mouse di atas link */
.nav-links a:hover{
    background:#f1f5f9;
    color:#0f172a;
}

/* link yang sedang aktif (halaman sekarang) */
.nav-links a.active{
    background:#eff6ff;
    color:#2563eb;
}

/* MAIN - area konten utama */

.main{
    flex:1; /* ambil sisa ruang yang tersedia */
    padding:2.5rem;
    max-width:75rem; /* batas lebar maksimal biar ga kelewat lebar */
    width:100%;
    margin:0 auto; /* posisi tengah horizontal */
}

/* teks sapaan "Halo, nama" */
.greeting{
    font-size:1.875rem;
    font-weight:800;
    color:#0f172a;
    margin-bottom:0.375rem;
}

/* teks kecil di bawah sapaan */
.subtitle{
    font-size:1.0625rem;
    color:#64748b;
    margin-bottom:1.75rem;
}

/* SEMESTER CARD - kartu info semester dengan gradient ungu-biru */

.sem-card{
    background:linear-gradient(135deg,#2563eb,#7c3aed); /* warna gradien dari biru ke ungu */
    border-radius:1rem;
    padding:1.75rem 2rem;
    color:#fff;
    margin-bottom:1.75rem;
    box-shadow:0 0.5rem 1.5rem rgba(79,70,229,.25); /* bayangan biar keliatan ngambang */
}

/* label kecil di atas judul semester (misal: "Ganjil 2025/2026") */
.sem-label{
    font-size:0.9375rem;
    opacity:.85; /* agak transparan biar ga terlalu nonjol */
    margin-bottom:0.375rem;
}

/* judul besar semester */
.sem-title{
    font-size:1.75rem;
    font-weight:800;
    line-height:1.3;
}

/* FRS BUTTON - tombol besar buat isi FRS */

.frs-btn{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:0.625rem; /* jarak antara ikon dan teks */
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    color:#fff;
    border-radius:0.75rem;
    padding:1rem;
    font-weight:800;
    font-size:1.25rem;
    text-decoration:none;
    box-shadow:0 0.375rem 1.25rem rgba(37,99,235,.3);
    width:100%; /* selebar container */
    margin-bottom:1.5rem;
}

/* ikon SVG di dalam tombol FRS */
.frs-btn svg{
    width:1.375rem;
    height:1.375rem;
    stroke:#fff;
    fill:none;
    stroke-width:2;
    stroke-linecap:round;
    stroke-linejoin:round;
}

/* TABLE - bagian tabel daftar mata kuliah */

/* header di atas tabel (judul + tombol kalau ada) */
.table-header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    margin-bottom:1rem;
}

/* judul section "Mata Kuliah Dibuka" */
.section-title{
    font-size:1.25rem;
    font-weight:800;
    color:#0f172a;
}

/* pembungkus tabel, dikasih background putih dan rounded */
.table-wrap{
    background:#fff;
    border-radius:0.75rem;
    box-shadow:0 0.0625rem 0.375rem rgba(0,0,0,.08);
    overflow:hidden; /* supaya border-radius keliatan di tabel */
}

/* tabel full width, border antar sel dihilangin */
table{
    width:100%;
    border-collapse:collapse;
}

/* baris header tabel */
thead tr{
    background:#f8fafc;
    border-bottom:2px solid #e2e8f0;
}

/* sel header tabel */
th{
    padding:0.875rem 1.125rem;
    text-align:left;
    font-weight:700;
    color:#475569;
    font-size:0.9375rem;
}

/* sel data tabel */
td{
    padding:0.875rem 1.125rem;
    border-bottom:1px solid #f1f5f9; /* garis tipis pemisah antar baris */
    color:#334155;
    font-size:0.9375rem;
    vertical-align:top;
}

/* baris terakhir ga perlu border bawah */
tr:last-child td{
    border-bottom:none;
}

/* efek hover: baris tabel berubah warna saat mouse lewat */
tr:hover td{
    background:#fafbff;
}

/* badge/tag jadwal (misal: "Senin, 10:00-12:00") */
.jadwal-tag{
    display:inline-block;
    background:#eff6ff;
    color:#2563eb;
    border-radius:0.25rem;
    padding:0.1875rem 0.5625rem;
    font-size:0.875rem;
    font-weight:600;
}

</style>

</head>

<body>

<!-- TOPBAR: bar navigasi atas, ada nama app dan link-link menu -->

<div class="topbar">

    <div class="app-name">WombatStudent</div>

    <!-- navigasi: Beranda, Jadwal, Riwayat -->
    <nav class="nav-links">
        <a href="dashboard.php" class="active">Beranda</a>
        <a href="jadwal.php">Jadwal</a>
        <a href="history.php">Riwayat</a>
    </nav>

</div>

<!-- MAIN: area konten utama halaman -->

<div class="main">

    <!-- GREETING: sapaan ke user pake nama depan -->

    <div class="greeting">
        Halo, <?= htmlspecialchars(explode(' ', $nama)[0]) ?>
    </div>

    <div class="subtitle">
        Selamat datang. Ini mata kuliah yang dibuka semester ini.
    </div>

    <!-- SEMESTER: kartu info semester yang lagi aktif -->

    <div class="sem-card">

        <div class="sem-label">
            <?= htmlspecialchars(trim($sem['Periode'] ?? 'Ganjil')) ?>
            <?= $sem['Tahun_Akademik'] ?? '2025' ?>/<?= ($sem['Tahun_Akademik'] ?? 2025) + 1 ?>
        </div>

        <div class="sem-title">
            Tahun Akademik <?= $sem['Tahun_Akademik'] ?? '2025' ?>/<?= ($sem['Tahun_Akademik'] ?? 2025) + 1 ?>
        </div>

    </div>

    <!-- FRS BUTTON: tombol gede buat buka halaman isi FRS -->

    <a href="frs.php" class="frs-btn">
        <svg viewBox="0 0 24 24">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
            <line x1="12" y1="18" x2="12" y2="12"/>
            <line x1="9" y1="15" x2="15" y2="15"/>
        </svg>
        Isi FRS
    </a>

    <!-- TABLE: tabel daftar semua mata kuliah yang dibuka semester ini -->

    <div class="table-header">
        <div class="section-title">Mata Kuliah Dibuka</div>
    </div>

    <div class="table-wrap">
        <table>
            <!-- header kolom tabel -->
            <thead>
                <tr>
                    <th>Kode</th>
                    <th>Nama Mata Kuliah</th>
                    <th>SKS</th>
                    <th>Jadwal</th>
                    <th>Dosen</th>
                </tr>
            </thead>
            <!-- isi tabel: loop tiap mata kuliah -->
            <tbody>
                <?php foreach ($grouped as $mk): ?>
                <tr>
                    <td><?= htmlspecialchars($mk['Id_MK']) ?></td>
                    <td><?= htmlspecialchars($mk['NamaMK']) ?></td>
                    <td><?= $mk['SKS'] ?></td>
                    <td>
                        <?php foreach ($mk['jadwal'] as $j): ?>
                        <span class="jadwal-tag"><?= htmlspecialchars($j) ?></span>
                        <?php endforeach; ?>
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