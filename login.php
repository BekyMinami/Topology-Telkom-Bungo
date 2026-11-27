<?php
session_start();
include 'koneksi.php';

if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

if (isset($_POST['login'])) {
    $stmt = mysqli_prepare($koneksi, "SELECT username, password, role FROM users WHERE username = ?");
    mysqli_stmt_bind_param($stmt, "s", $_POST['username']);
    mysqli_stmt_execute($stmt);
    $data = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    if ($data && password_verify($_POST['password'], $data['password'])) {
        session_regenerate_id(true);
        $_SESSION['username'] = $data['username'];
        $_SESSION['role'] = $data['role'];
        header("Location: index.php");
        exit;
    }
    $error = "Username atau Password salah!";
}
?>