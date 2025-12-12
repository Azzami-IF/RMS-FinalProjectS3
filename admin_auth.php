<?php
session_start();

if (!isset($_SESSION['login'])) {
    $_SESSION['info'] = "Silakan login terlebih dahulu.";
    header("Location: login.php");
    exit;
}

if ($_SESSION['role'] !== "admin") {
    die("Akses ditolak. Halaman ini hanya untuk admin.");
}
?>
