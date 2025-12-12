<?php
session_start();
include "header.php";
require "db.php";

if (isset($_POST['masuk'])) {
    $u = trim($_POST['user']);
    $email = trim($_POST['email']);
    $pass = $_POST['pass'];
    $confirm_pass = $_POST['confirm_pass'];

    // Validasi confirm password
    if ($pass !== $confirm_pass) {
        $pesan = "Password dan Confirm Password tidak cocok.";
    } else {

        // Hash password
        $p = password_hash($pass, PASSWORD_BCRYPT);

        // Cek apakah username atau email sudah dipakai
        $cek = $koneksi->query("SELECT id FROM users WHERE username='$u' OR email='$email'");

        if ($cek->num_rows > 0) {
            $pesan = "Username atau Email sudah dipakai.";
        } else {
            // Insert user
            $koneksi->query("INSERT INTO users(username, email, password, role)
                             VALUES('$u', '$email', '$p', 'user')");

            $_SESSION['info'] = "Akun berhasil dibuat, silakan masuk.";
            header("Location: login.php");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Buat Akun</title>
    <style>
        .primarybg {
            background: linear-gradient(to right, #349250ff, #4cb292ff);
            color: white;
        }
    </style>
</head>

<body class="primarybg d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow rounded-4 p-3">
        <div class="card-body">
            <h4>Register</h4>
            <hr>

            <?php 
                if (!empty($pesan)) echo "<p style='color:red'>$pesan</p>"; 
            ?>

            <form method="post">

                <div class="mb3">
                    <p class="form-label fw-semibold">Username:</p>
                    <input class="form-control" type="text" name="user" required>        
                </div>

                <div class="mb3">
                    <p class="form-label fw-semibold">Email:</p>
                    <input class="form-control" type="email" name="email" required>        
                </div>

                <div class="mb3">
                    <p class="form-label fw-semibold">Password:</p>
                    <div class="input-group">
                        <input class="form-control" type="password" id="pass" name="pass" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePass('pass', 'iconPass')">
                            <a><i id="iconPass" class="bi bi-eye"></i></a>
                        </button>
                    </div>
                </div>

                <div class="mb3">
                    <p class="form-label fw-semibold">Confirm Password:</p>
                    <div class="input-group">
                        <input class="form-control" type="password" id="confirm_pass" name="confirm_pass" required>
                        <button class="btn btn-outline-secondary" type="button" onclick="togglePass('confirm_pass', 'iconConfirm')">
                            <a><i id="iconConfirm" class="bi bi-eye"></i></a>
                        </button>
                    </div>
                </div>

                <br>
                <button class="btn btn-success w-100 rounded-3" type="submit" name="masuk">Register</button>
                
                <p>Sudah punya akun? <a href="login.php">Login</a></p>
            </form>
        </div>
    </div>

<script>
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    if (input.type === "password") {
        input.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        input.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
}
</script>

</body>
</html>
