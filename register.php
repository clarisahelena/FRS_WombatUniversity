<?php
session_start();
require_once "Koneksi.php";

$error   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = trim($_POST['role']     ?? '');
    $nama     = trim($_POST['nama']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($role === 'mahasiswa') {
        $npm = trim($_POST['npm'] ?? '');
        if (!preg_match('/^\d{10}$/', $npm))       $error = 'NPM harus tepat 10 digit angka.';
        elseif (strlen($email) > 22)               $error = 'Email terlalu panjang (maks 22 karakter).';
        elseif (strlen($password) > 25)            $error = 'Password maksimal 25 karakter.';
        elseif (strlen($nama) > 75)                $error = 'Nama maksimal 75 karakter.';
        else {
            $stmt = $conn->prepare("SELECT NPM FROM Mahasiswa WHERE NPM = ? OR Email = ?");
            $stmt->execute([$npm, $email]);
            if ($stmt->fetch()) {
                $error = 'NPM atau email sudah terdaftar.';
            } else {
                try {
                    $conn->prepare("INSERT INTO Mahasiswa (NPM, Email, Password, Nama) VALUES (?, ?, ?, ?)")
                         ->execute([$npm, $email, $password, $nama]);
                    $success = true;
                } catch (Exception $e) {
                    $error = 'Gagal menyimpan: ' . $e->getMessage();
                }
            }
        }
    } elseif ($role === 'dosen') {
        $nid = trim($_POST['nid'] ?? '');
        if (strlen($nid) < 1 || strlen($nid) > 10) $error = 'NID tidak valid.';
        elseif (strlen($email) > 22)                $error = 'Email terlalu panjang (maks 22 karakter).';
        elseif (strlen($password) > 25)             $error = 'Password maksimal 25 karakter.';
        elseif (strlen($nama) > 75)                 $error = 'Nama maksimal 75 karakter.';
        else {
            $stmt = $conn->prepare("SELECT NID FROM Dosen WHERE NID = ? OR Email = ?");
            $stmt->execute([$nid, $email]);
            if ($stmt->fetch()) {
                $error = 'NID atau email sudah terdaftar.';
            } else {
                try {
                    $conn->prepare("INSERT INTO Dosen (NID, Email, Password, Nama) VALUES (?, ?, ?, ?)")
                         ->execute([$nid, $email, $password, $nama]);
                    $success = true;
                } catch (Exception $e) {
                    $error = 'Gagal menyimpan: ' . $e->getMessage();
                }
            }
        }
    } else {
        $error = 'Pilih role terlebih dahulu.';
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
.tabs{display:flex;background:#f1f5f9;border-radius:10px;padding:4px;margin-bottom:24px;gap:4px}
.tabs label{flex:1;text-align:center;padding:10px;border-radius:8px;font-size:15px;font-weight:600;color:#64748b;cursor:pointer;transition:all .15s}
input[name="role"]{display:none}
#role_mhs:checked ~ .tabs label[for="role_mhs"],
#role_dsn:checked ~ .tabs label[for="role_dsn"]{background:#fff;color:#2563eb;box-shadow:0 1px 3px rgba(0,0,0,.08)}
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
#field_npm,#field_nid{display:none}
#role_mhs:checked ~ form #field_npm{display:block}
#role_dsn:checked ~ form #field_nid{display:block}
</style>
</head>
<body>
<div class="card">
  <div class="brand">CampusFlow</div>
  <h1>Daftar Akun</h1>
  <div class="sub">Buat akun baru</div>

  <?php if ($success): ?>
    <div class="success">Akun berhasil dibuat! Silakan login.</div>
    <div class="login-link"><a href="index.php">Kembali ke Login</a></div>

  <?php else: ?>
    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php $selRole = $_POST['role'] ?? 'mahasiswa'; ?>
    <input type="radio" name="role" value="mahasiswa" id="role_mhs" form="regForm" <?= $selRole === 'mahasiswa' ? 'checked' : '' ?>>
    <input type="radio" name="role" value="dosen" id="role_dsn" form="regForm" <?= $selRole === 'dosen' ? 'checked' : '' ?>>
    <div class="tabs">
      <label for="role_mhs">Mahasiswa</label>
      <label for="role_dsn">Dosen</label>
    </div>

    <form method="POST" id="regForm">
      <div class="form-group" id="field_npm">
        <label>NPM</label>
        <input type="text" name="npm" placeholder="10 digit NPM" maxlength="10"
               value="<?= htmlspecialchars($_POST['npm'] ?? '') ?>">
        <div class="hint-text">Contoh: 6182829999</div>
      </div>

      <div class="form-group" id="field_nid">
        <label>NID</label>
        <input type="text" name="nid" placeholder="Nomor Induk Dosen" maxlength="10"
               value="<?= htmlspecialchars($_POST['nid'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Nama Lengkap</label>
        <input type="text" name="nama" placeholder="Nama lengkap" maxlength="75" required
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="email@kampus.ac.id" maxlength="22" required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
        <div class="hint-text">Maksimal 22 karakter</div>
      </div>

      <div class="form-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" maxlength="25" required>
      </div>

      <button type="submit" class="btn">Daftar</button>
    </form>

    <div class="login-link">Sudah punya akun? <a href="index.php">Masuk</a></div>
  <?php endif; ?>
</div>
</body>
</html>
