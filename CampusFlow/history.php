<?php
session_start();
if (!isset($_SESSION["id_user"])) { header("Location: index.php"); exit; }
require_once "Koneksi.php";

//ambil NPM user
$npm = $_SESSION["id_user"];

// Ambil semua semester yang pernah di-enroll oleh mahasiswa ini,
$stmt = $conn->prepare("
    SELECT DISTINCT s.Id_Sem, s.Periode, s.Tahun_Akademik
    FROM Enroll e
    JOIN Semester s ON e.Id_Sem = s.Id_Sem
    WHERE e.NPM = ?
    ORDER BY s.Tahun_Akademik DESC, s.Periode DESC
");
$stmt->execute([$npm]);
$semesters = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Bangun array $history: satu entry per semester
$history = [];
// Untuk tiap semester lakukan hal berikut
foreach ($semesters as $sem) {
    $sid = $sem['Id_Sem']; //ambil id semester
    //lakukan query untuk ambil mata kuliah dan jadwal, ubah formt tanggal SQL server jadi string
    $stmt2 = $conn->prepare("
        SELECT mk.Id_MK, mk.Nama AS NamaMK, mk.SKS, j.Hari, j.Jam_Mulai, j.Jam_Selesai
        FROM Enroll e
        JOIN MataKuliah mk ON e.Id_MK = mk.Id_MK
        JOIN Jadwal j ON j.Id_MK = mk.Id_MK AND j.Id_Sem = ?
        WHERE e.NPM = ? AND e.Id_Sem = ?
        ORDER BY mk.Nama
    ");
    $stmt2->execute([$sid, $npm, $sid]);
    $courses = $stmt2->fetchAll(PDO::FETCH_ASSOC);

    $totalSKS = array_sum(array_column($courses, 'SKS'));
    // $savedDate = !empty($courses) ? $courses[0]['Dibuat_pada'] : '-';
    $history[] = [
        'sem'      => $sem,
        'courses'  => $courses,
        'totalSKS' => $totalSKS,
        // 'savedDate'=> $savedDate,
    ];
}

function fmtTime($t){ return substr($t,0,5); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Riwayat FRS – CampusFlow</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Calibri',Calibri,sans-serif}
body{background:#f1f5f9;min-height:100vh;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px}
.app-name{font-size:20px;font-weight:800;color:#2563eb}
.nav-links{display:flex;gap:6px}
.nav-links a{padding:10px 20px;border-radius:8px;font-size:16px;font-weight:600;color:#64748b;text-decoration:none;transition:background .15s}
.nav-links a:hover{background:#f1f5f9;color:#0f172a}
.nav-links a.active{background:#eff6ff;color:#2563eb}
.main{flex:1;padding:40px;max-width:900px;width:100%;margin:0 auto}
.page-title{font-size:30px;font-weight:800;color:#0f172a;margin-bottom:6px}
.page-sub{font-size:17px;color:#64748b;margin-bottom:36px}
/* timeline */
.timeline{position:relative;padding-left:32px}
.timeline::before{content:'';position:absolute;left:8px;top:8px;bottom:8px;width:2px;background:#e2e8f0}
.sem-block{position:relative;margin-bottom:32px}
.sem-dot{position:absolute;left:-28px;top:16px;width:14px;height:14px;border-radius:50%;background:#cbd5e1;border:2px solid #fff;box-shadow:0 0 0 2px #cbd5e1}
.sem-dot.active{background:#2563eb;box-shadow:0 0 0 3px #bfdbfe}
.sem-dot.bentrok{background:#dc2626;box-shadow:0 0 0 3px #fecaca}
.sem-card{background:#fff;border-radius:14px;box-shadow:0 1px 6px rgba(0,0,0,.08);overflow:hidden}
.sem-header{padding:18px 22px;border-bottom:1px solid #f1f5f9;display:flex;align-items:center;justify-content:space-between}
.sem-title{font-size:20px;font-weight:800;color:#0f172a}
.sem-meta{font-size:15px;color:#64748b;margin-top:3px}
.sem-badge{display:inline-flex;align-items:center;gap:6px;padding:5px 12px;border-radius:20px;font-size:13px;font-weight:700}
.badge-ok{background:#dcfce7;color:#16a34a}
.badge-bentrok{background:#fee2e2;color:#dc2626}
.course-list{padding:8px 0}
.course-row{display:flex;align-items:center;padding:12px 22px;border-bottom:1px solid #f8fafc;gap:14px}
.course-row:last-child{border-bottom:none}
.conflict-bar{width:4px;height:40px;border-radius:2px;flex-shrink:0}
.bar-ok{background:#e2e8f0}
.bar-bentrok{background:#dc2626}
.course-name{font-size:16px;font-weight:700;color:#0f172a;flex:1}
.course-detail{font-size:14px;color:#64748b;margin-top:2px}
.sks-num{font-size:14px;font-weight:700;color:#475569;white-space:nowrap}
.bentrok-tag{display:inline-block;background:#fee2e2;color:#dc2626;border-radius:4px;padding:2px 8px;font-size:12px;font-weight:700;margin-left:8px}
.empty{text-align:center;padding:48px;color:#94a3b8;font-size:17px}
.edit-link{display:inline-flex;align-items:center;gap:6px;color:#2563eb;font-size:14px;font-weight:600;text-decoration:none}
.edit-link svg{width:14px;height:14px;stroke:#2563eb;fill:none;stroke-width:2.5;stroke-linecap:round;stroke-linejoin:round}
</style>
</head>
<body>
<div class="topbar">
  <div class="app-name">WombatStudent</div>
  <nav class="nav-links">
    <a href="dashboard.php">Beranda</a>
    <a href="jadwal.php">Jadwal</a>
    <a href="history.php" class="active">Riwayat</a>
  </nav>
</div>

<div class="main">
  <div class="page-title">Histori Akademik</div>
  <div class="page-sub">Riwayat FRS per semester</div>

  <?php if (empty($history)): ?>
  <div class="empty">Belum ada riwayat FRS. Isi FRS terlebih dahulu.</div>
  <?php else: ?>
  <div class="timeline">
    <?php foreach ($history as $i => $h):
      $s = $h['sem'];
      $label = trim($s['Periode']).' '.$s['Tahun_Akademik'].'/'.($s['Tahun_Akademik']+1);
      $dotClass = $i === 0 ? 'active' : '';
    ?>
    <div class="sem-block">
      <div class="sem-dot <?= $dotClass ?>"></div>
      <div class="sem-card">
        <div class="sem-header">
          <div>
            <div class="sem-title"><?= htmlspecialchars($label) ?></div>
            <div class="sem-meta"><?= $h['totalSKS'] ?> SKS &bull; <?= count($h['courses']) ?> mata kuliah &bull; Disimpan</div>
          </div>
          <div style="display:flex;align-items:center;gap:14px">
            <a href="frs.php" class="edit-link">
              <svg viewBox="0 0 24 24"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
              Edit FRS
            </a>
          </div>
        </div>
        <div class="course-list">
          <?php foreach ($h['courses'] as $c): ?>
          <div class="course-row">
            <div class="conflict-bar bar-ok"></div>
            <div style="flex:1">
              <div class="course-name"><?= htmlspecialchars($c['Id_MK']) ?> : <?= htmlspecialchars($c['NamaMK']) ?></div>
              <div class="course-detail"><?= htmlspecialchars($c['Hari']) ?>, <?= fmtTime($c['Jam_Mulai']) ?>–<?= fmtTime($c['Jam_Selesai']) ?></div>
            </div>
            <div class="sks-num"><?= $c['SKS'] ?> SKS</div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
</body>
</html>
