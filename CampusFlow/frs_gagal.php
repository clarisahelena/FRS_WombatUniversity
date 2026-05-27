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
<title>FRS Bentrok – CampusFlow</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Calibri',Calibri,sans-serif}
body{background:#f1f5f9;min-height:100vh;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 40px;display:flex;align-items:center;height:64px}
.app-name{font-size:20px;font-weight:800;color:#2563eb}
.main{flex:1;padding:40px;max-width:900px;width:100%;margin:0 auto}
.banner{background:linear-gradient(135deg,#dc2626,#b91c1c);border-radius:16px;padding:36px;color:#fff;text-align:center;margin-bottom:32px;box-shadow:0 8px 24px rgba(220,38,38,.25)}
.warn-circle{width:64px;height:64px;background:rgba(255,255,255,.2);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
.warn-circle svg{width:32px;height:32px;stroke:#fff;fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}
.banner-title{font-size:28px;font-weight:800;margin-bottom:6px}
.banner-sub{font-size:17px;opacity:.9}
.sks-pill{display:inline-block;background:rgba(255,255,255,.2);border-radius:20px;padding:6px 18px;font-size:16px;font-weight:700;margin-top:12px}
.two-col{display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:28px}
.section-title{font-size:20px;font-weight:800;color:#0f172a;margin-bottom:16px}
.conflict-card{background:#fff;border:1.5px solid #fca5a5;border-radius:12px;padding:18px;margin-bottom:12px}
.conflict-label{font-size:13px;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:.4px;margin-bottom:10px}
.conflict-mk{font-size:16px;font-weight:700;color:#0f172a;margin-bottom:3px}
.conflict-time{font-size:14px;color:#64748b;margin-bottom:8px}
.vs{font-size:13px;color:#dc2626;font-weight:700;text-align:center;margin:6px 0}
.table-wrap{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.08);overflow:hidden}
table{width:100%;border-collapse:collapse}
th{padding:14px 18px;text-align:left;font-weight:700;color:#475569;font-size:15px;background:#f8fafc;border-bottom:2px solid #e2e8f0}
td{padding:14px 18px;border-bottom:1px solid #f1f5f9;color:#334155;font-size:15px}
tr:last-child td{border-bottom:none}
.bentrok-row td{background:#fff5f5}
.bentrok-tag{display:inline-block;background:#fee2e2;color:#dc2626;border-radius:4px;padding:2px 8px;font-size:12px;font-weight:700;margin-left:8px}
.btn-row{display:flex;gap:14px;margin-top:28px}
.btn{display:inline-flex;align-items:center;gap:8px;border-radius:10px;padding:14px 28px;font-size:16px;font-weight:700;text-decoration:none;cursor:pointer;border:none}
.btn-outline{background:#fff;color:#2563eb;border:2px solid #2563eb}
.btn-danger{background:linear-gradient(135deg,#dc2626,#b91c1c);color:#fff;box-shadow:0 4px 14px rgba(220,38,38,.3)}
</style>
</head>
<body>
<div class="topbar"><div class="app-name">WombatStudent</div></div>
<div class="main">
  <div class="banner">
    <div class="warn-circle"><svg viewBox="0 0 24 24"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg></div>
    <div class="banner-title">Terdapat Bentrok Jadwal</div>
    <div class="banner-sub">Semester <?= htmlspecialchars($result['semester']) ?> &bull; FRS tetap tersimpan</div>
    <div class="sks-pill"><?= $totalSKS ?> SKS &bull; <?= count($conflicts) ?> bentrok</div>
  </div>

  <div class="two-col">
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

    <div>
      <div class="section-title">Semua Mata Kuliah</div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Nama</th><th>SKS</th><th>Jadwal</th></tr></thead>
          <tbody>
            <?php foreach ($courses as $c): $b = isset($conflictIds[$c['Id_MK']]); ?>
            <tr <?= $b ? 'class="bentrok-row"' : '' ?>>
              <td><?= htmlspecialchars($c['NamaMK']) ?><?php if($b): ?><span class="bentrok-tag">Bentrok</span><?php endif; ?></td>
              <td><?= $c['SKS'] ?></td>
              <td><?= htmlspecialchars($c['Hari']) ?>, <?= fmtTime($c['Jam_Mulai']) ?>–<?= fmtTime($c['Jam_Selesai']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="btn-row">
    <a href="frs.php" class="btn btn-outline">Ubah FRS</a>
    <a href="history.php" class="btn btn-danger">Lihat Riwayat</a>
  </div>
</div>
</body>
</html>
