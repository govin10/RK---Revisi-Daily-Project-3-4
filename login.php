<?php
session_start();
require_once 'config/database.php';

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = "Username dan password wajib diisi!";
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id, username, password, nama, role FROM users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $updateStmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->execute([$user['id']]);

                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_nama'] = $user['nama'];
                $_SESSION['user_role'] = $user['role'];

                // Log aktivitas
                $logStmt = $db->prepare("INSERT INTO activity_logs (user_id, aksi, detail, ip_address) VALUES (?, ?, ?, ?)");
                $logStmt->execute([$user['id'], 'login', 'User berhasil login', $_SERVER['REMOTE_ADDR']]);

                header("Location: dashboard.php");
                exit;
            } else {
                $error = "Username atau password salah!";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Pelacak Alumni UMM</title>
    <link rel="stylesheet" href="/system-pelacak-alumni/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-bg"></div>
    <div class="login-card">
        <div class="login-logo">
            <div class="logo-circle">🎓</div>
            <h1>Pelacak Alumni</h1>
            <p>Sistem Intelijen Target Tunggal UMM</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger fade-in">
            <span>⚠️</span> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group mb-16">
                <label class="form-label">Username <span class="req">*</span></label>
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autofocus autocomplete="username">
            </div>
            
            <div class="form-group mb-24" style="margin-bottom: 24px;">
                <label class="form-label">Password <span class="req">*</span></label>
                <input type="password" name="password" class="form-control" placeholder="Masukkan password" required autocomplete="current-password">
            </div>

            <button type="submit" class="btn btn-primary w-100" style="justify-content: center; padding: 12px; font-size: 15px;">
                Masuk ke Sistem 🚀
            </button>
        </form>
    </div>
</body>
</html>
