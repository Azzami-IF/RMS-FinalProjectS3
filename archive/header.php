<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link href="node_modules/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="node_modules/bootstrap-icons/font/bootstrap-icons.min.css" rel="stylesheet">
    <script src="node_modules/bootstrap/dist/js/bootstrap.bundle.min.js"></script>

    <title>My app</title>

    <style>
        .primarybg {
            background: linear-gradient(to right, #349250ff, #4cb292ff);
        }
    </style>
</head>
<body>
