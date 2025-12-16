<?php
session_start();
require_once '../classes/Database.php';
require_once '../classes/User.php';

 $db = new Database();
 $user = new User($db);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $loggedInUser = $user->login($username, $password);

    if ($loggedInUser) {
        $_SESSION['user_id'] = $loggedInUser['id'];
        $_SESSION['username'] = $loggedInUser['username'];
        $_SESSION['role'] = $loggedInUser['role'];

        // Log aktivitas login
        $user->logActivity("User $username logged in.");

        if ($loggedInUser['role'] == 'admin') {
            header('Location: ../admin/dashboard.php');
        } else {
            header('Location: ../mahasiswa/dashboard.php');
        }
        exit();
    } else {
        $_SESSION['error'] = 'Username atau password salah!';
        header('Location: ../login.php');
        exit();
    }
}
?>