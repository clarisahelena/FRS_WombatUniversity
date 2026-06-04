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
    JOIN Enroll e ON mk.Id_MK = e.Id_MK
    WHERE j.Id_Sem = ? AND e.npm = ?
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
*{box-sizing:border-box;margin:0;padding:0;font-family:'Calibri',Calibri,sans-serif}
body{background:#f1f5f9;min-height:100vh;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px}
.app-name{font-size:20px;font-weight:800;color:#2563eb}
.nav-links{display:flex;gap:6px}
.nav-links a{padding:10px 20px;border-radius:8px;font-size:16px;font-weight:600;color:#64748b;text-decoration:none;transition:background .15s}
.nav-links a:hover{background:#f1f5f9;color:#0f172a}
.nav-links a.active{background:#eff6ff;color:#2563eb}
.main{flex:1;padding:40px}
.page-title{font-size:30px;font-weight:800;color:#0f172a;margin-bottom:6px}
.page-sub{font-size:17px;color:#64748b;margin-bottom:28px}
.table-wrap{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.08);overflow:hidden}
table{width:100%;border-collapse:collapse}
thead tr{background:#f8fafc;border-bottom:2px solid #e2e8f0}
th{padding:14px 18px;text-align:left;font-weight:700;color:#475569;font-size:15px;white-space:nowrap}
td{padding:14px 18px;border-bottom:1px solid #f1f5f9;color:#334155;font-size:15px;vertical-align:middle}
tr:last-child td{border-bottom:none}
tr:hover td{background:#fafbff}
.hari-cell{font-weight:700;color:#0f172a;white-space:nowrap}
.waktu-cell{white-space:nowrap;color:#2563eb;font-weight:600}
.kode-cell{font-weight:700;color:#7c3aed;white-space:nowrap}
.sks-cell{text-align:center;font-weight:700}
.edit-btn{display:inline-flex;align-items:center;gap:8px;background:#2563eb;color:#fff;border-radius:10px;padding:12px 24px;font-size:16px;font-weight:700;text-decoration:none;transition:opacity .15s;box-shadow:0 4px 14px rgba(37,99,235,.3)}
.edit-btn:hover{opacity:.9}
.edit-btn svg{width:17px;height:17px;stroke:#fff;fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}
</style>
</head>
<body>
<div class="topbar">
  <div class="app-name">WombatStudent</div>
  <nav class="nav-links">
    <a href="dashboard.php">Beranda</a>
    <a href="jadwal.php" class="active">Jadwal</a>
    <a href="history.php">Riwayat</a>
  </nav>
</div>

<div class="main">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
    <div class="page-title">Jadwal Kuliah</div>
    <a href="frs.php" class="edit-btn">
      <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
      Edit FRS
    </a>
  </div>
  <div class="page-sub">Semester <?= htmlspecialchars(trim($sem['Periode'] ?? 'Ganjil')) ?> <?= $sem['Tahun_Akademik'] ?? '2025' ?>/<?= ($sem['Tahun_Akademik'] ?? 2025)+1 ?></div>

  <div class="table-wrap" style="margin-top:20px">
    <table>
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
