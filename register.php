<?php
// ============================================================
// register.php
// Halaman pendaftaran akun mahasiswa baru.
// Data yang diisi akan divalidasi lalu disimpan ke tabel
// Mahasiswa di database SQL Server.
// ============================================================

session_start();
require_once "Koneksi.php";

$error   = '';
$success = false;

// Proses form hanya jika request adalah POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Ambil dan bersihkan input dari form
    //kalo ada, pakai yang disitu kalo ga ada keluarkan string kosong
    $npm      = trim($_POST['npm']      ?? '');
    $nama     = trim($_POST['nama']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validasi panjang input sesuai constraint kolom di database:
    // NPM char(10), Email char(22), Password varchar(25), Nama varchar(75)
    if (!preg_match('/^\d{10}$/', $npm))  $error = 'NPM harus tepat 10 digit angka.';
    elseif (strlen($email) > 22)          $error = 'Email terlalu panjang (maks 22 karakter). Contoh: xx@student.unpar.ac.id';
    elseif (strlen($password) > 25)       $error = 'Password maksimal 25 karakter.';
    elseif (strlen($nama) > 75)           $error = 'Nama maksimal 75 karakter.';
    else {
        // Cek apakah NPM atau email sudah terdaftar di database
        $stmt = $conn->prepare("SELECT NPM FROM Mahasiswa WHERE NPM = ? OR Email = ?");
        $stmt->execute([$npm, $email]);
        if ($stmt->fetch()) {
            $error = 'NPM atau email sudah terdaftar.';
        } else {
            // Simpan data mahasiswa baru ke tabel Mahasiswa
            try {
                $conn->prepare("INSERT INTO Mahasiswa (NPM, Email, Password, Nama) VALUES (?, ?, ?, ?)")
                     ->execute([$npm, $email, $password, $nama]);
                $success = true;
            } catch (Exception $e) {
                // Tampilkan pesan error dari database jika insert gagal
                $error = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Daftar CampusFlow</title>
<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:'Calibri',Calibri,sans-serif}
body{background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center}
.card{background:#fff;border-radius:16px;padding:40px 44px;width:480px;box-shadow:0 4px 24px rgba(0,0,0,.1)}
.brand{font-size:22px;font-weight:800;color:#2563eb;margin-bottom:28px}
h1{font-size:28px;font-weight:800;color:#0f172a;margin-bottom:6px}
.sub{font-size:16px;color:#64748b;margin-bottom:28px}
.form-group{margin-bottom:18px}
.form-group label{display:block;font-size:15px;font-weight:600;color:#374151;margin-bottom:7px}
.form-group input{width:100%;padding:13px 16px;border:1.5px solid #e2e8f0;border-radius:10px;outline:none;font-size:15px;font-family:'Calibri',Calibri,sans-serif}
.form-group input:focus{border-color:#2563eb}
.hint-text{font-size:13px;color:#94a3b8;margin-top:4px}
.btn{width:100%;border:none;border-radius:10px;padding:15px;color:#fff;font-weight:700;font-size:17px;cursor:pointer;background:linear-gradient(135deg,#2563eb,#7c3aed);box-shadow:0 4px 14px rgba(37,99,235,.3);font-family:'Calibri',Calibri,sans-serif;margin-top:4px}
.btn:hover{opacity:.95}
.error{background:#fee2e2;color:#b91c1c;padding:12px;border-radius:8px;font-size:15px;margin-bottom:18px}
.success{background:#dcfce7;color:#16a34a;padding:16px;border-radius:8px;font-size:16px;margin-bottom:18px;font-weight:600}
.login-link{text-align:center;margin-top:20px;font-size:15px;color:#64748b}
.login-link a{color:#2563eb;font-weight:600;text-decoration:none}
</style>
</head>
<body>
<div class="card">
  <div class="brand">WombatStudent</div>
  <h1>Daftar Akun</h1>
  <div class="sub">Buat akun mahasiswa baru</div>

  <?php if ($success): ?>
    <!-- Tampil setelah pendaftaran berhasil -->
    <div class="success">Akun berhasil dibuat! Silakan login.</div>
    <div class="login-link"><a href="index.php">Kembali ke Login</a></div>

  <?php else: ?>
    <!-- Tampilkan pesan error jika validasi atau insert gagal -->
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <!-- Input NPM: harus 10 digit angka, sesuai char(10) di DB -->
      <div class="form-group">
        <label>NPM</label>
        <input type="text" name="npm" placeholder="10 digit NPM" maxlength="10" required
               value="<?= htmlspecialchars($_POST['npm'] ?? '') ?>">
        <div class="hint-text">Contoh: 6182829999</div>
      </div>

      <!-- Input nama lengkap, maks 75 karakter -->
      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" placeholder="Nama lengkap" maxlength="75" required
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
      </div>

      <!-- Input email, maks 22 karakter sesuai char(22) di DB -->
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="xx@student.unpar.ac.id" maxlength="22" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <div class="hint-text">Maksimal 22 karakter. Contoh: xx@student.unpar.ac.id</div>
      </div>

      <!-- Input password, maks 25 karakter -->
      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="••••••••" maxlength="25" required>
      </div>

      <button type="submit" class="btn">Daftar</button>
    </form>

    <div class="login-link">Sudah punya akun? <a href="index.php">Masuk</a></div>
  <?php endif; ?>
</div>
</body>
</html>
