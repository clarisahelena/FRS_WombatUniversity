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
*{box-sizing:border-box;margin:0;padding:0;font-family:'Calibri',Calibri,sans-serif}
body{background:#f1f5f9;min-height:100vh;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 40px;display:flex;align-items:center;height:64px}
.app-name{font-size:20px;font-weight:800;color:#2563eb}
.main{flex:1;padding:40px;max-width:800px;width:100%;margin:0 auto}
.banner{background:linear-gradient(135deg,#16a34a,#15803d);border-radius:16px;padding:36px;color:#fff;text-align:center;margin-bottom:32px;box-shadow:0 8px 24px rgba(22,163,74,.25)}
.check-circle{width:64px;height:64px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
.check-circle svg{width:32px;height:32px;stroke:#fff;fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}
.banner-title{font-size:28px;font-weight:800;margin-bottom:6px}
.banner-sub{font-size:17px;opacity:.9}
.sks-pill{display:inline-block;background:rgba(255,255,255,.2);border-radius:20px;padding:6px 18px;font-size:16px;font-weight:700;margin-top:12px}
.section-title{font-size:20px;font-weight:800;color:#0f172a;margin-bottom:16px}
.table-wrap{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.08);overflow:hidden;margin-bottom:28px}
table{width:100%;border-collapse:collapse}
th{padding:14px 18px;text-align:left;font-weight:700;color:#475569;font-size:15px;background:#f8fafc;border-bottom:2px solid #e2e8f0}
td{padding:14px 18px;border-bottom:1px solid #f1f5f9;color:#334155;font-size:15px}
tr:last-child td{border-bottom:none}
.btn-row{display:flex;gap:14px}
.btn{display:inline-flex;align-items:center;gap:8px;border-radius:10px;padding:14px 28px;font-size:16px;font-weight:700;text-decoration:none;cursor:pointer;border:none}
.btn-outline{background:#fff;color:#2563eb;border:2px solid #2563eb}
.btn-primary{background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.3)}
</style>
</head>
<body>
<div class="topbar"><div class="app-name">WombatStudent</div></div>
<div class="main">
  <div class="banner">
    <div class="check-circle"><svg viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg></div>
    <div class="banner-title">FRS Berhasil Disimpan</div>
    <div class="banner-sub">Semester <?= htmlspecialchars($result['semester']) ?></div>
    <div class="sks-pill"><?= $totalSKS ?> SKS terdaftar</div>
  </div>

  <div class="section-title">Mata Kuliah Dipilih</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Kode</th><th>Nama Mata Kuliah</th><th>SKS</th><th>Jadwal</th></tr></thead>
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

  <div class="btn-row">
    <a href="history.php" class="btn btn-outline">Lihat Riwayat</a>
    <a href="dashboard.php" class="btn btn-primary">Kembali ke Beranda</a>
  </div>
</div>
</body>
</html>
