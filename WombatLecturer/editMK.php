<?php
session_start();
if (!isset($_SESSION["id_user"])) {
    header("Location: ../index.php");
    exit;
}
require_once "Koneksi.php";

$nid = $_SESSION["id_user"];

$semester = $_POST["semester"] ?? $_SESSION["semester"] ?? "25";
$periode  = $_POST["periode"] ?? $_SESSION["periode"] ?? "1";

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST["tambah_mk"])) {

    $_SESSION["semester"] = $semester;
    $_SESSION["periode"] = $periode;

    header("Location: kelola.php");
    exit;
}

$id_sem = ($_SESSION["semester"] ?? "25") . '-' . ($_SESSION["periode"] ?? "1");

//query semester
$stmt = $conn->prepare("
    SELECT Periode, 
    Tahun_Akademik 
    FROM Semester 
    WHERE Id_Sem = ?");
$stmt->execute([$id_sem]);
$sem = $stmt->fetch(PDO::FETCH_ASSOC);
$semLabel = trim($sem['Periode'] ?? 'Ganjil') . ' ' . ($sem['Tahun_Akademik'] ?? '2025') . '/' . (($sem['Tahun_Akademik'] ?? 2025) + 1);

//ambil semua mata kuliah yang tersedia pada semester tertentu
$stmt = $conn->prepare("
    SELECT j.Id_Jadwal, mk.Id_MK, mk.Nama AS NamaMK, mk.SKS, j.Hari, j.Jam_Mulai, j.Jam_Selesai, j.NID, d.Nama AS NamaDosen
    FROM Jadwal j
    JOIN MataKuliah mk ON j.Id_MK = mk.Id_MK
    JOIN Dosen d ON j.NID = d.NID
    WHERE j.Id_Sem = ?
    ORDER BY mk.Nama
");
$stmt->execute([$id_sem]);
$courses = $stmt->fetchAll(PDO::FETCH_ASSOC);

//untuk menambah mata kuliah ke data base
if(isset($_POST["tambah_mk"])) {
    $kode = $_POST["kode"];
    $nama = $_POST["nama"];
    $sks = $_POST["sks"];
    $hari = $_POST["hari"];
    $mulai = $_POST["mulai"];
    $selesai = $_POST["selesai"];
    $ruangan = $_POST["ruangan"];

    //UNTUK MENANGANI KALAU MATA KULIAH SUDA ADS
    try {
        $conn->beginTransaction();
        $tambahMK = $conn->prepare("
            INSERT INTO MataKuliah (Id_MK, Nama, SKS)
            VALUES (?, ?, ?)
        ");

        $tambahMK->execute([$kode, $nama, $sks]);

        //tambah juga di jadwal
        $tambahJadwal = $conn->prepare("
            INSERT INTO Jadwal
            VALUES(?, ?, ?, ?, ?, ?, ?, ?)
        ");

        //buat id jadwal dengan ambil 3 digit terakhir MK dan gabungkan dengan semester
        $id_jadwal = substr($kode,-3).$semester.$periode;
        
        $tambahJadwal->execute([$id_jadwal, $kode, $id_sem, $nid, $hari, $mulai, $selesai, $ruangan]);

        //tambah juga ke detail akademik
        $tambahDetailAkademik = $conn->prepare("
            INSERT INTO detail_akademik (nid, id_jadwal, id_mk)
            VALUES (?, ?, ?)
        ");
        $tambahDetailAkademik -> execute([$nid, $id_jadwal, $kode]);

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
.nav-links{display:flex;gap:6px}
.nav-links a{padding:10px 20px;border-radius:8px;font-size:16px;font-weight:600;color:#64748b;text-decoration:none}
.nav-links a:hover{background:#f1f5f9;color:#0f172a}
.nav-links a.active{background:#eff6ff;color:#2563eb}
.app-name{font-size:20px;font-weight:800;color:#2563eb}
.main{flex:1;display:flex;gap:28px;padding:40px;max-width:1200px;width:100%;margin:0 auto;align-items:flex-start}
.left-col{flex:1;min-width:0}
.right-col{width:340px;flex-shrink:0}
.page-title{font-size:30px;font-weight:800;color:#0f172a;margin-bottom:6px}
.page-sub{font-size:17px;color:#64748b;margin-bottom:24px}
.course-card{background:#fff;border-radius:12px;padding:16px 18px;margin-bottom:12px;display:flex;gap:14px;align-items:flex-start;box-shadow:0 1px 4px rgba(0,0,0,.06);cursor:pointer;border:2px solid transparent;transition:border-color .15s}

.course-card.hidden{display:none}

.course-info{flex:1;min-width:0}
.course-top{display:flex;justify-content:space-between;align-items:center;margin-bottom:5px}
.course-code{font-size:13px;font-weight:700;color:#64748b}
.sks-badge{font-size:12px;font-weight:700;color:#fff;padding:3px 10px;border-radius:20px}
.edit-btn{border:none;border-radius:10px;padding:16px;font-size:15px;font-weight:700;cursor:pointer}
.edit-btn.disabled{background:#e2e8f0;color:#94a3b8;cursor:not-allowed}
.edit-btn.active{background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.3)}
.course-name{font-size:17px;font-weight:700;color:#0f172a;margin-bottom:7px;line-height:1.3}
.course-meta{display:flex;gap:16px;font-size:14px;color:#64748b}
.meta-item{display:flex;align-items:center;gap:5px}
.meta-icon svg{width:14px;height:14px;stroke:#94a3b8;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}

.semester-card{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.08);overflow:hidden;top:24px}
.semesterSaatIni-banner{background:linear-gradient(135deg,#2563eb,#7c3aed);padding:24px;color:#fff}
.semesterSaatIni-lbl{font-weight:700;font-size:20px;opacity:.85;margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px}
.atur-body{padding:20px}
.atur-row{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.atur-row span:last-child{font-weight:700;color:#0f172a}
.tetapkan-btn{width:100%;border:none;border-radius:10px;padding:16px;font-size:17px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:10px;margin-top:20px;}
.tetapkan-btn.disabled{background:#e2e8f0;color:#94a3b8;cursor:not-allowed}
.tetapkan-btn.active{background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.3)}
.error-msg{background:#fee2e2;color:#b91c1c;padding:12px 16px;border-radius:8px;font-size:15px;margin-bottom:18px}

.tambah-card{background:#fff;border-radius:12px;box-shadow:0 1px 6px rgba(0,0,0,.08);overflow:hidden;top:24px; margin-top:20px}
.tambah-banner{background:linear-gradient(135deg,#2563eb,#7c3aed);padding:24px;color:#fff}
.tambah-lbl{font-weight:700;font-size:20px;opacity:.85;margin-bottom:6px;text-transform:uppercase;letter-spacing:.3px}
.tambah-body{padding:20px}
.tambah-row{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.tambah-row span:last-child{font-weight:700;color:#0f172a}
.tambah-btn{width:100%;border:none;border-radius:10px;padding:16px;font-size:17px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;justify-content:center;gap:10px;margin-top:20px;}
.tambah-btn.disabled{background:#e2e8f0;color:#94a3b8;cursor:not-allowed}
.tambah-btn.active{background:linear-gradient(135deg,#2563eb,#7c3aed);color:#fff;box-shadow:0 4px 14px rgba(37,99,235,.3)}
.nama-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.nama-group span:last-child{font-weight:700;color:#0f172a}
.kode-group{display:flex;justify-content:space-between;font-size:16px;color:#475569;margin-bottom:14px}
.kode-group span:last-child{font-weight:700;color:#0f172a}
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
    <div class="editMK-card">
    <div class="editMK-banner">
        <div class="semesterSaatIni-lbl">Semester yang sedang berlangsung</div>
        <div class="semesterSaatIni-label"><?= htmlspecialchars(trim($sem['Periode'] ?? 'Ganjil')) ?> <?= $sem['Tahun_Akademik'] ?? '2025' ?>/<?= ($sem['Tahun_Akademik'] ?? 2025) + 1 ?></div>
    </div>
    <div class="atur-body">
        <div class="atur-row">
        <span>Atur semester yang akan berlangsung</span>
        </div>
            <form action="" method="POST">
            <input type="hidden" name="id_sem" value="<?= htmlspecialchars($id_sem) ?>">
            
            <select name="semester" id="semester">
                    <option value="20">2020/2021</option>
                    <option value="21">2021/2022</option>
                    <option value="22">2022/2023</option>
                    <option value="23">2023/2024</option>
                    <option value="24">2024/2025</option>
                    <option value="25">2025/2026</option>
                    <option value="26">2026/2027</option>
                </select>

                <select name="periode" id="periode">
                    <option value="1">Ganjil</option>
                    <option value="2">Genap</option>
                </select>
                <button type="submit" class="tetapkan-btn active">
                    Tetapkan
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

    <div class="tambah-card">
    <div class="tambah-banner">
        <div class="tambah-lbl">Tambah Mata Kuliah</div>
    </div>
    <div class="tambah-body">
        <div class="tambah-row">
                <span>Tambah mata kuliah baru untuk semester ini dengan mengisi form dibawah</span>
        </div>
            <form method="POST">
                <div class="kode-group">
                    <span>Kode Mata Kuliah</span>

                    <input type="text" name="kode" maxlength="7" placeholder="Isi kode dengan panjang 7" required>
                </div>
                <div class="nama-group">
                    <span>Nama Mata Kuliah</span>

                    <input type="text" name="nama" placeholder="Isi nama Mata Kuliah" required>
                </div>
                <div class="sks-group">
                    <span>SKS Mata Kuliah</span>

                    <input type="text" name="sks" placeholder="Isi SKS Mata Kuliah" required>
                </div>

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
                
                    <span>Anda akan tercatat otomatis sebagai dosen mata kuliah ini</span>

                    <button type="submit" name="tambah_mk" class="tambah-btn active">
                        Tambah Mata Kuliah
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
