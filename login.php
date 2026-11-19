<?php
session_start();
include 'config/koneksi.php';

// Jika sudah login, lempar ke dashboard
if (isset($_SESSION['status']) && $_SESSION['status'] == "login") {
    header("Location: admin/index.php");
    exit;
}

$error = "";

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);

    // Pastikan variabel $q di bawah ini didefinisikan dengan benar
    $q = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    
    // Baris 15: Sekarang $q sudah ada dan berisi hasil query
    if ($q && mysqli_num_rows($q) > 0) {
        $data = mysqli_fetch_assoc($q);
        
        // Simpan data ke Session
        $_SESSION['id_user']      = $data['id_user']; 
        $_SESSION['username']     = $data['username'];
        $_SESSION['nama_lengkap'] = $data['nama_lengkap'];
        $_SESSION['role']         = $data['role'];
        $_SESSION['status']       = "login";
        $_SESSION['jenjang_aktif'] = 'ALL';
        
        header("Location: admin/index.php");
        exit;
    } else {
        $error = "Username atau Password salah!";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login Administrator</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/@phosphor-icons/web"></script>
    <style>
        body {
            margin: 0; padding: 0;
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: #0f172a;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            position: relative;
        }
        .blob {
            position: absolute;
            width: 500px; height: 500px;
            background: linear-gradient(180deg, #4f46e5 0%, #a855f7 100%);
            border-radius: 50%;
            filter: blur(80px);
            animation: move 10s infinite alternate;
            z-index: 0;
            opacity: 0.6;
        }
        .blob:nth-child(1) { top: -100px; left: -100px; background: #4338ca; }
        .blob:nth-child(2) { bottom: -100px; right: -100px; background: #0ea5e9; }
        @keyframes move {
            from { transform: translate(0, 0) scale(1); }
            to { transform: translate(50px, 50px) scale(1.1); }
        }
        .login-container {
            position: relative;
            z-index: 10;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 50px;
            border-radius: 30px;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
            color: white;
        }
        .brand-icon { font-size: 50px; color: #818cf8; margin-bottom: 20px; }
        h2 { margin: 0; font-weight: 800; font-size: 24px; }
        p { color: #94a3b8; font-size: 14px; margin-bottom: 30px; }
        .form-group { text-align: left; margin-bottom: 20px; }
        label { display: block; font-size: 12px; font-weight: 600; color: #cbd5e1; margin-bottom: 8px; text-transform: uppercase; }
        input {
            width: 100%; padding: 16px; border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.2);
            color: white; font-size: 14px; box-sizing: border-box;
        }
        .btn-login {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
            color: white; font-weight: 700; cursor: pointer;
        }
        .alert {
            background: rgba(239, 68, 68, 0.2); color: #fca5a5;
            padding: 12px; border-radius: 10px; font-size: 13px; margin-bottom: 20px;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
    </style>
</head>
<body>
    <div class="blob"></div>
    <div class="blob"></div>
    <div class="login-container">
        <i class="ph-fill ph-polygon brand-icon"></i>
        <h2>Admin Portal</h2>
        <p>Koperasi Seragam Nusa Putera</p>

        <?php if($error != ""): ?>
            <div class="alert">🚫 <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" placeholder="Masukkan ID" required autofocus>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" placeholder="••••••••" required>
            </div>
            <button type="submit" name="login" class="btn-login">AKSES DASHBOARD</button>
        </form>
    </div>
</body>
</html>