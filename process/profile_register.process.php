<?php
session_start();
require_once '../config/database.php';
require_once '../classes/User.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

if (!isset($_POST['date_of_birth'], $_POST['gender'], $_POST['height_cm'], $_POST['weight_kg'], $_POST['activity_level'])) {
    header('Location: ../profile_register.php?error=1');
    exit;
}

$config = require '../config/env.php';
$db = (new Database($config))->getConnection();
$userClass = new User($db);
$user = $_SESSION['user'];

// Perbarui profil pengguna
$userClass->update($user['id'], [
    'name' => $user['name'],
    'email' => $user['email'],
    'phone' => $user['phone'] ?? null,
    'date_of_birth' => $_POST['date_of_birth'],
    'gender' => $_POST['gender'],
    'height_cm' => $_POST['height_cm'],
    'weight_kg' => $_POST['weight_kg'],
    'activity_level' => $_POST['activity_level'],
    'daily_calorie_goal' => $user['daily_calorie_goal'] ?? 2000,
    'role' => $user['role'] ?? 'user',
    'is_active' => 1
]);

// Insert weight log pertama
$stmt = $db->prepare("INSERT INTO weight_logs (user_id, weight_kg, logged_at) VALUES (?, ?, CURDATE()) ON DUPLICATE KEY UPDATE weight_kg=VALUES(weight_kg)");
$stmt->execute([$user['id'], $_POST['weight_kg']]);

// Refresh session user
$newUser = $userClass->find($user['id']);
$_SESSION['user'] = $newUser;
unset($_SESSION['wajib_profil']);
header('Location: ../dashboard.php?success=profile_completed');
exit;
