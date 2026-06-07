<?php
session_start();
require_once "Koneksi.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email    = trim($_POST["email"] ?? "");
    $password = trim($_POST["password"] ?? "");
    $role     = trim($_POST["role"] ?? "");

    if ($role === 'mahasiswa') {
        $stmt = $conn->prepare("SELECT NPM AS id_user, Email, Password, Nama FROM Mahasiswa WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password === trim($user["Password"])) {
            $_SESSION["id_user"] = $user["id_user"];
            $_SESSION["nama"]    = $user["Nama"];
            $_SESSION["email"]   = $user["Email"];
            $_SESSION["role"]    = "mahasiswa";
            header("Location: CampusFlow/dashboard.php");
            exit;
        } else {
            $error = "Email atau password salah.";
        }
    } elseif ($role === 'dosen') {
        $stmt = $conn->prepare("SELECT NID AS id_user, Email, Password, Nama FROM Dosen WHERE Email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $password === trim($user["Password"])) {
            $_SESSION["id_user"] = $user["id_user"];
            $_SESSION["nama"]    = $user["Nama"];
            $_SESSION["email"]   = $user["Email"];
            $_SESSION["role"]    = "dosen";
            header("Location: WombatLecturer/dashboardDosen.php");
            exit;
        } else {
            $error = "Email atau password salah.";
        }
    } else {
        $error = "Pilih role terlebih dahulu.";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login CampusFlow</title>
    <style>
        *{box-sizing:border-box;margin:0;padding:0;font-family:'Calibri',Calibri,sans-serif}
        body{background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center}
        .card{background:#fff;border-radius:16px;padding:40px 44px;width:440px;box-shadow:0 4px 24px rgba(0,0,0,.1)}
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
        .form-group input[type="email"],
        .form-group input[type="password"]{width:100%;padding:13px 16px;border:1.5px solid #e2e8f0;border-radius:10px;outline:none;font-size:15px;font-family:'Calibri',Calibri,sans-serif}
        .form-group input:focus{border-color:#2563eb}
        .btn{width:100%;border:none;border-radius:10px;padding:15px;color:#fff;font-weight:700;font-size:17px;cursor:pointer;background:linear-gradient(135deg,#2563eb,#7c3aed);box-shadow:0 4px 14px rgba(37,99,235,.3);font-family:'Calibri',Calibri,sans-serif}
        .btn:hover{opacity:.95}
        .error{background:#fee2e2;color:#b91c1c;padding:12px;border-radius:8px;font-size:14px;margin-bottom:18px;text-align:center}
        .hint{margin-top:20px;font-size:13px;color:#94a3b8;background:#f8fafc;padding:12px;border-radius:8px;line-height:1.6}
    </style>
</head>
<body>
<div class="card">
    <div class="brand">CampusFlow</div>
    <h1>Selamat Datang</h1>
    <div class="sub">Masuk untuk melanjutkan</div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="radio" name="role" value="mahasiswa" id="role_mhs" checked>
        <input type="radio" name="role" value="dosen" id="role_dsn">
        <div class="tabs">
            <label for="role_mhs">Mahasiswa</label>
            <label for="role_dsn">Dosen</label>
        </div>

        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" id="emailInput" placeholder="contoh@student.unpar.ac.id" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Masukkan password" required>
        </div>

        <button type="submit" class="btn">Masuk</button>
    </form>

    <div class="hint">
        Mahasiswa: bt@student.unpar.ac.id<br>
        Dosen: xx@lecture.unpar.ac.id
    </div>

    <script>
    document.querySelectorAll('input[name="role"]').forEach(r => {
        r.addEventListener('change', function() {
            document.getElementById('emailInput').placeholder = 
                this.value === 'dosen' ? 'contoh@lecture.unpar.ac.id' : 'contoh@student.unpar.ac.id';
        });
    });
    </script>
    <div style="text-align:center;margin-top:20px;font-size:15px;color:#64748b">
        Belum punya akun? <a href="register.php" style="color:#2563eb;font-weight:600;text-decoration:none">Daftar Sekarang</a>
    </div>
</div>
</body>
</html>
