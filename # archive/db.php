<?php
# Login localhost PhpMyAdmin
$user = "root";
$password = "";

$koneksi = new mysqli("localhost", $user, $password, "myapp");

if (!$koneksi) {
    die("Gagal tersambung ke database");
}
?>
