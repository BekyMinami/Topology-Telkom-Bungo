<?php
session_start();
include 'koneksi.php';

if (isset($_POST['login'])) {
    $username = mysqli_real_escape_string($koneksi, $_POST['username']);
    $password = md5($_POST['password']);

    $query = mysqli_query($koneksi, "SELECT * FROM users WHERE username='$username' AND password='$password'");
    if (mysqli_num_rows($query) > 0) {
        $data = mysqli_fetch_assoc($query);
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        header("Location: index.php");
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
    <title>Login - Topologi Telkom Bungo</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); width: 350px; }
        h3 { color: #b30000; margin-top: 0; text-align: center; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
        button { width: 100%; background: #b30000; color: white; padding: 10px; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; }
        button:hover { background: #800000; }
        .error { color: red; font-size: 13px; text-align: center; }
        .info { font-size: 12px; color: #666; margin-top: 15px; background: #f8f9fa; padding: 8px; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="login-card">
        <h3>Login Sistem Telkom</h3>
        <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
        <form action="" method="POST">
            <label>Username:</label>
            <input type="text" name="username" required placeholder="Masukkan username">
            
            <label>Password:</label>
            <input type="password" name="password" required placeholder="Masukkan password">
            
            <button type="submit" name="login">Masuk</button>
        </form>
        <div class="info">
            <b>Demo Akun:</b><br>
            - Admin: <code>admin_telkom</code> / <code>123456</code><br>
            - User: <code>teknisi_lapangan</code> / <code>123456</code>
        </div>
    </div>
</body>
</html>