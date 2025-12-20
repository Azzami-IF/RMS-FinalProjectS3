<?php
session_start();

// Jika sudah login → langsung ke dashboard
if (isset($_SESSION['login'])) {
    header("Location: dashboard.php");
    exit;
}

include "header.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - MyApp</title>

    <style>
        .primarybg {
            background: linear-gradient(to right, #349250ff, #4cb292ff);
            color: white;
        }
    </style>
</head>

<body>

    <!-- NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-dark primarybg">
        <div class="container">
            <a class="navbar-brand" href="index.php">MyApp</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">

                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>

                    <li class="nav-item">
                        <a class="nav-link" href="register.php">Register</a>
                    </li>

                </ul>
            </div>
        </div>
    </nav>

    <!-- HERO SECTION -->
    <section class="py-5">
        <div class="container">

            <h1 class="fw-bold mb-3 text-center">MyApp</h1>
            <div class="mt-4 text-center">
                <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcROdZluHvHcuLXMreCR66HV-d9leSczyVm4ug&s" 
                width="500" height="300" class="placeholder-wave">
            </div>
            <p class="lead text-muted text-center mt-3">Sample.</p>

            <div class="mt-4 text-center">
                <a href="login.php" class="btn btn-success btn-lg me-2">Login</a>
                <a href="register.php" class="btn btn-outline-success btn-lg">Register</a>
            </div>

        </div>
    </section>

    <!-- SECTION INFORMASI -->
    <section class="py-5 bg-white border-top">
        <div class="container">

            <h3 class="fw-bold text-center mb-4">Informasi</h3>

            <div class="row text-center">

                <div class="col-md-4">
                    <div class="card shadow-sm rounded-3 p-3">
                        <h5 class="fw-bold">Subheader Sample</h5>
                        <p class="text-muted">Sample.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm rounded-3 p-3">
                        <h5 class="fw-bold">Subheader Sample</h5>
                        <p class="text-muted">Sample.</p>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card shadow-sm rounded-3 p-3">
                        <h5 class="fw-bold">Subheader Sample</h5>
                        <p class="text-muted">Sample.</p>
                    </div>
                </div>

            </div>

        </div>
    </section>

    <!-- FOOTER -->
    <footer class="text-center text-muted py-3 border-top">
        <small>Sample Homepage</small>
    </footer>

</body>
</html>
