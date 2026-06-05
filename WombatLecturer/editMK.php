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
    SELECT Hari, Jam_Mulai 
    FROM Jadwal 
    WHERE Id_MK = ? AND Id_Sem = ?");
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
    try {
        $conn->beginTransaction();
        $editMK = $conn->prepare("
            UPDATE MataKuliah 
            SET Nama = ?, SKS = ?
            WHERE Id_MK = ?
        ");

        $editMK->execute([$nama, $sks, $id_mk]);

        //edit juga di jadwal
        $delJadwal = $conn->prepare("
            DELETE FROM Jadwal
            WHERE Id_MK = ?
        ");
        $delJadwal->execute([$id_mk]);
        
        $editJadwal1 = $conn->prepare("
                INSERT INTO Jadwal
                VALUES(?, ?, ?, ?, ?, ?, ?, ?)
            ");

            //buat id jadwal dengan ambil 3 digit terakhir MK dan gabungkan dengan semester
            $id_jadwal1 = substr($id_mk,-3).$semester.$periode.'1';
            
            $editJadwal1->execute([$id_jadwal1, $id_mk, $id_sem, $nid, $hari, $mulai, $selesai, $ruangan]);

        //edit jadwal kedua
        //kalau jadwal kedua diisi, masukkan ke database
        if(!empty($hari2)) {
            $editJadwal2 = $conn->prepare("
                INSERT INTO Jadwal
                VALUES(?, ?, ?, ?, ?, ?, ?, ?)
            ");

            //buat id jadwal dengan ambil 3 digit terakhir MK dan gabungkan dengan semester
            $id_jadwal2 = substr($id_mk,-3).$semester.$periode.'2';
            
            $editJadwal2->execute([$id_jadwal2, $id_mk, $id_sem, $nid, $hari2, $mulai2, $selesai2, $ruangan2]);
        }
        
        //edit jadwal ketiga
        //kalau jadwal ketiga diisi, masukkan ke database
        if(!empty($hari3)) {
            $editJadwal3 = $conn->prepare("
            INSERT INTO Jadwal
            VALUES(?, ?, ?, ?, ?, ?, ?, ?)
            ");

            //buat id jadwal dengan ambil 3 digit terakhir MK dan gabungkan dengan semester
            $id_jadwal3 = substr($id_mk,-3).$semester.$periode.'3';
            
            $editJadwal3->execute([$id_jadwal3, $id_mk, $id_sem, $nid, $hari3, $mulai3, $selesai3, $ruangan3]);
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

/* TOPBAR - navigasi atas */

.topbar{
    background:#fff;
    border-bottom:1px solid #e2e8f0;
    padding:0 2.5rem;
    display:flex;
    align-items:center;
    justify-content:space-between;
    height:4rem;
    flex-shrink:0;
}

/* nama app */
.app-name{
    font-size:1.25rem;
    font-weight:800;
    color:#2563eb;
}

/* container link navigasi */
.nav-links{
    display:flex;
    gap:0.375rem;
}

/* tiap link nav */
.nav-links a{
    padding:0.625rem 1.25rem;
    border-radius:0.5rem;
    font-size:1rem;
    font-weight:600;
    color:#64748b;
    text-decoration:none;
}

/* hover link */
.nav-links a:hover{
    background:#f1f5f9;
    color:#0f172a;
}

/* link aktif */
.nav-links a.active{
    background:#eff6ff;
    color:#2563eb;
}

/* MAIN LAYOUT - 2 kolom: kiri (detail MK), kanan (form edit) */

.main{
    flex:1;
    display:flex;
    gap:1.75rem;
    padding:2.5rem;
    max-width:75rem;
    width:100%;
    margin:0 auto;
    align-items:flex-start;
}

/* kolom kiri: detail mata kuliah saat ini */
.left-col{
    width:21.25rem;
    flex-shrink:0;
}

/* kolom kanan: form edit */
.right-col{
    width:21.25rem;
    flex-shrink:0;
}

/* judul halaman */
.page-title{
    font-size:1.875rem;
    font-weight:800;
    color:#0f172a;
    margin-bottom:0.375rem;
}

.page-sub{
    font-size:1.0625rem;
    color:#64748b;
    margin-bottom:1.5rem;
}

/* DETAIL CARD - kartu info MK yang sedang di-edit */

.detail-card{
    background:#fff;
    border-radius:0.75rem;
    box-shadow:0 0.0625rem 0.375rem rgba(0,0,0,.08);
    overflow:hidden;
    margin-top:1.25rem;
}

/* banner gradient: judul "Detail Mata Kuliah" */
.detail-banner{
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    padding:1.5rem;
    color:#fff;
}

.detail-lbl{
    font-weight:700;
    font-size:1.25rem;
    opacity:.85;
}

/* body detail MK */
.detail-body{
    padding:1.25rem;
}

/* baris info (kode, nama, sks, jadwal) */
.kodeMK-row, .namaMK-row, .sksMK-row, .jadwalMK-row{
    display:flex;
    justify-content:space-between;
    font-size:1rem;
    color:#475569;
    margin-bottom:0.875rem;
}

.kodeMK-row span:last-child,
.namaMK-row span:last-child,
.sksMK-row span:last-child,
.jadwalMK-row span:last-child{
    font-weight:700;
    color:#0f172a;
}

/* EDIT CARD - kartu form edit mata kuliah */

.edit-card{
    background:#fff;
    border-radius:0.75rem;
    box-shadow:0 0.0625rem 0.375rem rgba(0,0,0,.08);
    overflow:hidden;
    margin-top:1.25rem;
}

/* banner "Edit Mata Kuliah" */
.edit-banner{
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    padding:1.5rem;
    color:#fff;
}

.edit-lbl{
    font-weight:700;
    font-size:1.25rem;
    opacity:.85;
}

/* body form edit */
.edit-body{
    padding:1.25rem;
}

.edit-row{
    font-size:1rem;
    color:#475569;
    margin-bottom:0.875rem;
}

/* tombol submit edit */
.edit-btn{
    width:100%;
    border:none;
    border-radius:0.625rem;
    padding:1rem;
    font-size:1.0625rem;
    font-weight:700;
    cursor:pointer;
    display:flex;
    align-items:center;
    justify-content:center;
    margin-top:1.25rem;
}

.edit-btn.active{
    background:linear-gradient(135deg,#2563eb,#7c3aed);
    color:#fff;
    box-shadow:0 0.25rem 0.875rem rgba(37,99,235,.3);
}

/* form groups: layout label + input sejajar */
.nama-group, .sks-group,
.hari-group, .mulai-group, .selesai-group, .ruangan-group,
.hari2-group, .mulai2-group, .selesai2-group, .ruangan2-group,
.hari3-group, .mulai3-group, .selesai3-group, .ruangan3-group{
    display:flex;
    justify-content:space-between;
    font-size:1rem;
    color:#475569;
    margin-bottom:0.875rem;
}

</style>
</head>
<body>

<!-- TOPBAR: navigasi atas -->

<div class="topbar">
    <div class="app-name">WombatLecturer</div>
    <!-- menu navigasi -->
    <nav class="nav-links">
        <a href="/CampusFlow/WombatLecturer/dashboardDosen.php">Beranda</a>
        <a href="/CampusFlow/WombatLecturer/jadwalDosen.php">Jadwal</a>
        <a href="/CampusFlow/WombatLecturer/kelola.php">Kelola</a>
    </nav>
</div>

<!-- MAIN: layout 2 kolom (detail MK kiri, form edit kanan) -->

<div class="main">

    <!-- LEFT COLUMN: info detail mata kuliah yang sedang di-edit -->

    <div class="left-col">
        <div class="detail-card">
            <div class="detail-banner">
                <div class="detail-lbl">Detail Mata Kuliah saat ini</div>
            </div>
            <div class="detail-body">
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

    <!-- RIGHT COLUMN: form edit mata kuliah -->

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

                        <input type="text" name="nama" placeholder="Isi nama Mata Kuliah" required>
                    </div>
                    <div class="sks-group">
                        <span>SKS Mata Kuliah</span>

                        <input type="text" name="sks" placeholder="Isi SKS Mata Kuliah" required>
                    </div>

                    <span style="font-weight:700;"> Edit jadwal 1 (wajib diisi)<br></span>

                    <div class="hari-group">
                        <span>Hari</span>

                        <input type="text" name="hari" placeholder="Isi hari" required>
                    </div>

                    <div class="mulai-group">
                        <span>Jam mulai</span>

                        <input type="text" name="mulai" placeholder="Isi jam mulai" required>
                    </div>

                    <div class="selesai-group">
                        <span>Jam selesai</span>

                        <input type="text" name="selesai" placeholder="Isi jam selesai" required>
                    </div>

                    <div class="ruangan-group">
                        <span>Ruangan</span>

                        <input type="text" name="ruangan" placeholder="Isi kode ruangan" required>
                    </div>

                     <span style="font-weight:700;"> Edit jadwal 2</span>

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

                        <input type="text" name="ruangan2" placeholder="Isi kode ruangan">
                    </div>

                     <span style="font-weight:700;"> Edit jadwal 3</span>

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

                        <input type="text" name="ruangan3" placeholder="Isi kode ruangan">
                    </div>
                    
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
