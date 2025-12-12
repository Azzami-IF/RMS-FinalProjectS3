<?php
session_start();

// Jika belum login → arahkan ke homepage
if (!isset($_SESSION['user_id'])) {
    header("Location: homepage.php");
    exit;
}
?>
