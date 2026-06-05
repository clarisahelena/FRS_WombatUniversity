<?php
session_start();
if (!isset($_SESSION["id_user"])) {
    header("Location: ../index.php");
    exit;
}
require_once "../Koneksi.php";

$nid = $_SESSION['id_user'];

$id_mk =$_GET['Id_MK'] ?? '';
$id_sem = $_GET['Id_Sem'] ?? '';
$semester = explode('-', $id_sem)[0];
$periode = explode('-', $id_sem)[1];

$conn -> beginTransaction();
$currentMK = $conn->prepare("
    SELECT Nama, Id_MK, SKS
    FROM MataKuliah
    WHERE Id_MK = ?
");
$currentMK -> execute([$id_mk]);
$dataMK = $currentMK->fetch(PDO::FETCH_ASSOC);

$jadwal = $conn->prepare("
    SELECT Hari, Jam_Mulai, Jam_Selesai, Ruangan
    FROM Jadwal 
    WHERE Id_MK = ? AND Id_Sem = ?
");
$jadwal->execute([$id_mk, $id_sem]);
$dataJadwal = $jadwal->fetchAll(PDO::FETCH_ASSOC);
$conn->commit();

// //delete jadwal, dan mk yang ingin diedit
// $conn->beginTransaction();

// $delDetail = $conn->prepare("
//     DELETE FROM Detail_Akademik
//     WHERE Id_MK = ?
// ");
// $delDetail->execute([$id_mk]);

// $delMK = $conn->prepare("
//     DELETE FROM MataKuliah
//     WHERE Id_MK = ?
// ");
// $delMK->execute([$id_mk]);

// $conn->commit();
//untuk menambah mata kuliah ke data base
if(isset($_POST["edit_mk"])) {
    $nama = $_POST["nama"];
    $sks = $_POST["sks"];
    $hari = $_POST["hari"];
    $mulai = $_POST["mulai"];
    $selesai = $_POST["selesai"];
    $ruangan = $_POST["ruangan"];
    $hari2 = $_POST["hari2"];
    $mulai2 = $_POST["mulai2"];
    $selesai2 = $_POST["selesai2"];
    $ruangan2 = $_POST["ruangan2"];
    $hari3 = $_POST["hari3"];
    $mulai3 = $_POST["mulai3"];
    $selesai3 = $_POST["selesai3"];
    $ruangan3 = $_POST["ruangan3"];

    //UNTUK MENANGANI KALAU MATA KULIAH BELOM ADA
    //kode gabisa diganti supaya bisa update
    try {
        $conn->beginTransaction();
        $editMK = $conn->prepare("
            UPDATE MataKuliah 
            SET Nama = ?, SKS = ?
            WHERE Id_MK = ? 
        ");

        $editMK->execute([$nama, $sks, $id_mk]);

        //Update jadwal pertama        
        $editJadwal1 = $conn->prepare("
            UPDATE Jadwal
            SET Hari = ?, Jam_Mulai = ?, Jam_Selesai = ?, Ruangan = ?
            WHERE jadwalKe = 1 AND Id_MK = ? AND Id_Sem = ?
        ");
            //buat id jadwal dengan ambil 3 digit terakhir MK dan gabungkan dengan semester
            $editJadwal1->execute([$hari, $mulai, $selesai, $ruangan, $id_mk, $id_sem]);

        //edit jadwal kedua
        //kalau jadwal kedua diisi, masukkan ke database
        if(!empty($hari2)) {
            $editJadwal2 = $conn->prepare("
                UPDATE Jadwal
                SET Hari = ?, Jam_Mulai = ?, Jam_Selesai = ?, Ruangan = ?
                WHERE jadwalKe = 2 AND Id_MK = ? AND Id_Sem = ?
            ");

            //buat id jadwal dengan ambil 3 digit terakhir MK dan gabungkan dengan semester
            $editJadwal2->execute([$hari2, $mulai2, $selesai2, $ruangan2, $id_mk, $id_sem]);
        } else {
            //kalau ga diisi lagi, hapus dari database
            $delJadwal2 = $conn->prepare("
                DELETE FROM Jadwal
                WHERE Id_MK = ? AND Id_Sem = ? AND jadwalKe = 2
            ");
            $delJadwal2->execute([$id_mk, $id_sem]);
        }
        
        //edit jadwal ketiga
        //kalau jadwal ketiga diisi, masukkan ke database
        if(!empty($hari3)) {
            $editJadwal3 = $conn->prepare("
                UPDATE Jadwal
                SET Hari = ?, Jam_Mulai = ?, Jam_Selesai = ?, Ruangan = ?
                WHERE jadwalKe = 3 AND Id_MK = ? AND Id_Sem = ?
            ");
            $editJadwal3->execute([$hari3, $mulai3, $selesai3, $ruangan3, $id_mk, $id_sem]);
        } else {
            $delJadwal3 = $conn->prepare("
                DELETE FROM Jadwal
                WHERE jadwalKe = 3 AND Id_MK = ? AND Id_Sem = ?
            ");
            $delJadwal3->execute([$id_mk, $id_sem]);
        }

        $conn->commit();
        header("Location: kelola.php");
        exit;   

    } catch (PDOException $e) {
        $conn->rollBack();
        echo $e->getMessage();
    }
}
function fmtTime($t) { return substr($t, 0, 5); }

// SKS colors for badges
$sksColors = [2=>'#ef4444', 3=>'#f97316', 4=>'#2563eb'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kelola - WombatLecturer</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Calibri',Calibri,sans-serif}
body{background:#f1f5f9;min-height:100vh;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid #e2e8f0;padding:0 40px;display:flex;align-items:center;justify-content:space-between;height:64px;flex-shrink:0}
.nav{
    display:flex;
    gap:8px;
}

.nav a{
    text-decoration:none;
    color:#475569;
    font-weight:600;
    padding:10px 18px;
    border-radius:10px;
    transition:.2s;
}

.nav a:hover{
    background:#f1f5f9;
}

.nav a.active{
    background:#dbeafe;
    color:#2563eb;
}
.nav-links{
    display:flex;
    gap:6px
}
.nav-links a{
    padding:10px 20px;border-radius:8px;font-size:16px;font-weight:600;color:#64748b;text-decoration:none}
.nav-links a:hover{background:#f1f5f9;color:#0f172a}
.nav-links a.active{background:#eff6ff;color:#2563eb}
.app-name{font-size:20px;font-weight:800;color:#2563eb}
.main{flex:1;display:flex;gap:28px;padding:40px;max-width:1200px;width:100%;margin:0 auto;align-items:flex-start}
.left-col{width:340px;flex-shrink:0}
.right-col{width:340px;flex-shrink:0}
.page-title{font-size:30px;font-weight:800;color:#0f172a;margin-bottom:6px}
.page-sub{font-size:17px;color:#64748b;margin-bottom:24px}

.detail-card{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.08);overflow:hidden;top:24px; margin-top:20px}
.detail-banner{background:linear-gradient(135deg,#2563eb,#7c3aed);padding:24px;color:#fff}
.detail-lbl{font-weight:700;font-size:20px;opacity:.85;margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px}
.detail-body{padding:20px}
.kodeMK-row{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.kodeMK-row span:last-child{font-weight:700;color:#0f172a}
.namaMK-row{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.namaMK-row span:last-child{font-weight:700;color:#0f172a}
.sksMK-row{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.sksMK-row span:last-child{font-weight:700;color:#0f172a}
.jadwalMK-row{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.jadwalMK-row span:last-child{font-weight:700;color:#0f172a}

.edit-card{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.08);overflow:hidden;top:24px; margin-top:20px}
.edit-banner{background:linear-gradient(135deg,#2563eb,#7c3aed);padding:24px;color:#fff}
.edit-lbl{font-weight:700;font-size:20px;opacity:.85;margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px}
.edit-body{padding:20px}
.edit-row{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.edit-row span:last-child{font-weight:700;color:#0f172a}
.edit-btn{width:100%;border:none;border-radius:10px;padding:16px;font-size:17px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:10px;margin-top:20px;}
.edit-btn.disabled{background:#e2e8f0;color:#94a3b8;cursor:not-allowed}
.edit-btn.active{background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.3)}

.nama-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.nama-group span:last-child{font-weight:700;color:#0f172a}
.sks-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.sks-group span:last-child{font-weight:700;color:#0f172a}
.hari-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.hari-group span:last-child{font-weight:700;color:#0f172a}
.mulai-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.mulai-group span:last-child{font-weight:700;color:#0f172a}
.ruangan-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.ruangan-group span:last-child{font-weight:700;color:#0f172a}
.selesai-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.selesai-group span:last-child{font-weight:700;color:#0f172a}

.hari2-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.hari2-group span:last-child{font-weight:700;color:#0f172a}
.mulai2-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.mulai2-group span:last-child{font-weight:700;color:#0f172a}
.ruangan2-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.ruangan2-group span:last-child{font-weight:700;color:#0f172a}
.selesai2-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.selesai2-group span:last-child{font-weight:700;color:#0f172a}

.hari3-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.hari3-group span:last-child{font-weight:700;color:#0f172a}
.mulai3-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.mulai3-group span:last-child{font-weight:700;color:#0f172a}
.ruangan3-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.ruangan3-group span:last-child{font-weight:700;color:#0f172a}
.selesai3-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.selesai3-group span:last-child{font-weight:700;color:#0f172a}

</style>
</head>
<body>
<div class="topbar">
  <div class="app-name">WombatLecturer</div>
  <nav class="nav-links">
    <a href="dashboardDosen.php">Beranda</a>
    <a href="jadwalDosen.php">Jadwal</a>
    <a href="kelola.php">Kelola</a>
  </nav>
</div>

<div class="main">

<div class="left-col">
    <div class="detail-card">
        <div class="detail-banner">
           <div class="detail-lbl">Detail Mata Kuliah saat ini</div>
        </div>
        <div class = "detail-body">
            <div class="kodeMK-row">
                 <span>Kode Mata Kuliah</span>
                 <?= htmlspecialchars($dataMK['Id_MK']) ?>
            </div>
            <div class="namaMK-row">
                 <span>Nama Mata Kuliah</span>
                 <?= htmlspecialchars($dataMK['Nama']) ?>
            </div>
            <div class="sksMK-row">
                 <span>SKS Mata Kuliah</span>
                 <?= htmlspecialchars($dataMK['SKS']) ?>
            </div>
            <div class="jadwalMK-row">
                 <span style="font-weight:700;">Jadwal Mata Kuliah</span>
                 <?php foreach($dataJadwal as $d): ?>
                    <?= htmlspecialchars($d['Hari']) ?>, <?= fmtTime($d['Jam_Mulai']) ?> <br>
                <?php endforeach; ?> 
                 
            </div>
        </div>
    </div>
</div>
    
    <div class="right-col">
        <div class="edit-card">
        <div class="edit-banner">
            <div class="edit-lbl">Edit Mata Kuliah</div>
        </div>
        <div class="edit-body">
            <div class="edit-row">
                 <span>Edit mata kuliah baru untuk semester ini dengan mengisi form dibawah</span>
            </div>
                <form method="POST">
                    <div class="nama-group">
                        <span>Nama Mata Kuliah</span>

                        <input type="text" name="nama" value="<?= htmlspecialchars($dataMK['Nama']) ?>" required>
                    </div>
                    <div class="sks-group">
                        <span>SKS Mata Kuliah</span>

                        <input type="text" name="sks" value="<?= htmlspecialchars($dataMK['SKS']) ?>" required>
                    </div>

                    <span style="font-weight:700;"> Edit jadwal 1 (wajib diisi)<br></span>

                    <div class="hari-group">
                        <span>Hari</span>

                        <input type="text" name="hari" value="<?= htmlspecialchars($dataJadwal[0]['Hari']) ?>" required>
                    </div>
                    
                    <div class="mulai-group">
                        <span>Jam mulai</span>

                        <input type="text" name="mulai" value="<?= htmlspecialchars(substr($dataJadwal[0]['Jam_Mulai'] ?? '', 0, 5)) ?>" required>
                    </div>

                    <div class="selesai-group">
                        <span>Jam selesai</span>

                        <input type="text" name="selesai" value="<?= htmlspecialchars(substr($dataJadwal[0]['Jam_Selesai'] ?? '', 0, 5)) ?>" required>
                    </div>

                    <div class="ruangan-group">
                        <span>Ruangan</span>

                        <input type="text" name="ruangan" value="<?= htmlspecialchars($dataJadwal[0]['Ruangan']) ?>" required>
                    </div>

                     <span style="font-weight:700;"> Edit jadwal 2</span>

                    <?php if (isset($dataJadwal[1])): ?>
                        <div class="hari2-group">
                            <span>Hari</span>

                            <input type="text" name="hari2" value="<?= htmlspecialchars($dataJadwal[1]['Hari']) ?>">
                        </div>

                        <div class="mulai2-group">
                            <span>Jam mulai</span>

                            <input type="text" name="mulai2" value="<?= htmlspecialchars(substr($dataJadwal[1]['Jam_Mulai'], 0, 5)) ?>">
                        </div>

                        <div class="selesai2-group">
                            <span>Jam selesai</span>

                            <input type="text" name="selesai2" value="<?= htmlspecialchars(substr($dataJadwal[1]['Jam_Selesai'], 0, 5)) ?>">
                        </div>

                        <div class="ruangan2-group">
                            <span>Ruangan</span>

                            <input type="text" name="ruangan2" value="<?= htmlspecialchars($dataJadwal[1]['Ruangan']) ?>">
                        </div>
                    <?php else: ?>
                        <div class="hari2-group">
                            <span>Hari</span>

                            <input type="text" name="hari2" placeholder="Isi hari">
                        </div>

                        <div class="mulai2-group">
                            <span>Jam mulai</span>

                            <input type="text" name="mulai2" placeholder="Isi jam mulai">
                        </div>

                        <div class="selesai2-group">
                            <span>Jam selesai</span>

                            <input type="text" name="selesai2" placeholder="Isi jam selesai">
                        </div>

                        <div class="ruangan2-group">
                            <span>Ruangan</span>

                            <input type="text" name="ruangan2" placeholder="Isi ruangan">
                        </div>
                    <?php endif; ?>

                    <span style="font-weight:700;"> Edit jadwal 3</span>

                     <?php if (isset($dataJadwal[2])): ?>
                        <div class="hari3-group">
                            <span>Hari</span>

                            <input type="text" name="hari3" value="<?= htmlspecialchars($dataJadwal[2]['Hari']) ?>">
                        </div>

                        <div class="mulai3-group">
                            <span>Jam mulai</span>

                            <input type="text" name="mulai3" value="<?= htmlspecialchars(substr($dataJadwal[2]['Jam_Mulai'], 0, 5)) ?>">
                        </div>

                        <div class="selesai3-group">
                            <span>Jam selesai</span>

                            <input type="text" name="selesai3" value="<?= htmlspecialchars(substr($dataJadwal[2]['Jam_Selesai'], 0, 5)) ?>">
                        </div>

                        <div class="ruangan3-group">
                            <span>Ruangan</span>

                            <input type="text" name="ruangan3" value="<?= htmlspecialchars($dataJadwal[2]['Ruangan']) ?>">
                        </div>
                    <?php else: ?>
                        <div class="hari3-group">
                            <span>Hari</span>

                            <input type="text" name="hari3" placeholder="Isi hari">
                        </div>

                        <div class="mulai3-group">
                            <span>Jam mulai</span>

                            <input type="text" name="mulai3" placeholder="Isi jam mulai">
                        </div>

                        <div class="selesai3-group">
                            <span>Jam selesai</span>

                            <input type="text" name="selesai3" placeholder="Isi jam selesai">
                        </div>

                        <div class="ruangan3-group">
                            <span>Ruangan</span>

                            <input type="text" name="ruangan3" placeholder="Isi ruangan">
                        </div>
                    <?php endif; ?>

                     <span>Anda akan tercatat otomatis sebagai dosen mata kuliah ini</span>

                        <button type="submit" name="edit_mk" class="edit-btn active">
                            Edit Mata Kuliah
                        </button>
                </form>
                <script>
                function ambilData() {
                    let pilihan = document.getElementById("semester").value;

                    console.log(pilihan);
                }
                </script>
        </div>
        </div>
  </div>
</div>

<script>
function filterCourses(){
    const q=document.getElementById('searchInput').value.toLowerCase();
    document.querySelectorAll('.course-card').forEach(card=>{
        card.classList.toggle('hidden',q&&!card.dataset.search.includes(q));
    });
}
</script>
</body>
</html>
