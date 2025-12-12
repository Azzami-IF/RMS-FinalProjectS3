<?php
session_start();
require "db.php";
include "header.php";

if (isset($_POST['masuk'])) {
    $u = $_POST['user'];
    $p = $_POST['pass'];

    // login pakai username / email
    $q = $koneksi->query("SELECT * FROM users WHERE username='$u' OR email='$u'");
    $d = $q->fetch_assoc();

    $login_status = "failed";
    $user_id = $d['id'] ?? 0;

    // Verifikasi hash password
    if ($d && password_verify($p, $d['password'])) {
        $_SESSION['login'] = $d['username'];
        $_SESSION['role'] = $d['role'];
        $login_status = "success";

        // insert log login
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $koneksi->query("INSERT INTO log_login(user_id, ip_address, user_agent, status)
                         VALUES('$user_id', '$ip', '$ua', 'success')");

        header("Location: dashboard.php");
        exit;
    } else {
        $err = "Login invalid.";

        // log gagal
        $ip = $_SERVER['REMOTE_ADDR'];
        $ua = $_SERVER['HTTP_USER_AGENT'];
        $koneksi->query("INSERT INTO log_login(user_id, ip_address, user_agent, status)
                         VALUES('$user_id', '$ip', '$ua', 'failed')");
    }
}

?>
<head>
    <style>
        .primarybg {
            background:  linear-gradient(to right, #349250ff, #4cb292ff);
            color: white;
        }
    </style>
</head>
<body class="primarybg d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow rounded-4 p-3">
        <div class="card-body">
            <h4>Login</h4>
            <hr>

            <?php 
            if (!empty($_SESSION['info'])) { 
                echo "<p style='color:green'>".$_SESSION['info']."</p>"; 
                unset($_SESSION['info']);
            }
            if (!empty($err)) echo "<p style='color:red'>$err</p>"; 
            ?>

            <form method="post">
                <div class="mb3">
                    <p class="form-label fw-semibold">Username / Email:</p>
                    <input class="form-control" type="text" name="user" required>        
                </div>

                <div class="mb3">
                    <p class="form-label fw-semibold">Password:</p>
                    <input class="form-control" type="password" name="pass" required>        
                </div>

                <br>
                <button class="btn btn-success w-100 rounded-3" type="submit" name="masuk">Login</button>
                <p>Tidak punya akun? <a href="register.php">Register</a></p>
            </form>
        </div>
    </div> 
