<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Food.php';

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$food = new Food($db);

$action = $_POST['action'] ?? '';

if ($action === 'create') {
    $food->create([
        $_POST['name'],
        $_POST['calories'],
        $_POST['protein'],
        $_POST['fat'],
        $_POST['carbs']
    ]);
}

if ($action === 'update') {
    $food->update(
        (int)$_POST['id'],
        [
            $_POST['name'],
            $_POST['calories'],
            $_POST['protein'],
            $_POST['fat'],
            $_POST['carbs']
        ]
    );
}

if ($action === 'delete') {
    $food->delete((int)$_POST['id']);
}

header('Location: ../admin/foods.php');
exit;
