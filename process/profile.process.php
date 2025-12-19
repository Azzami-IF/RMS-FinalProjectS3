<?php
session_start();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Auth.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$user = new User($db);
$auth = new Auth($db);

$action = $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'update_personal':
            // Validate required fields
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($name)) {
                header('Location: ../profile_edit.php?error=Nama tidak boleh kosong');
                exit;
            }

            if (empty($email)) {
                header('Location: ../profile_edit.php?error=Email tidak boleh kosong');
                exit;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                header('Location: ../profile_edit.php?error=Format email tidak valid');
                exit;
            }

            // Check if email is already used by another user
            $existingUser = $user->findByEmail($email);
            if ($existingUser && $existingUser['id'] != $_SESSION['user']['id']) {
                header('Location: ../profile_edit.php?error=Email sudah digunakan oleh user lain');
                exit;
            }

            // Get current user data
            $currentUser = $user->find($_SESSION['user']['id']);
            if (!$currentUser) {
                header('Location: ../profile_edit.php?error=User tidak ditemukan');
                exit;
            }

            // Update personal information (merge with existing physical data)
            $user->update($_SESSION['user']['id'], [
                'name' => $name,
                'email' => $email,
                'phone' => !empty(trim($_POST['phone'] ?? '')) ? trim($_POST['phone']) : $currentUser['phone'],
                'date_of_birth' => !empty(trim($_POST['date_of_birth'] ?? '')) ? trim($_POST['date_of_birth']) : $currentUser['date_of_birth'],
                'gender' => !empty(trim($_POST['gender'] ?? '')) ? trim($_POST['gender']) : $currentUser['gender'],
                'height_cm' => $currentUser['height_cm'],
                'weight_kg' => $currentUser['weight_kg'],
                'activity_level' => $currentUser['activity_level'],
                'daily_calorie_goal' => $currentUser['daily_calorie_goal'],
                'role' => $currentUser['role'],
                'is_active' => $currentUser['is_active']
            ]);

            // Update session data
            $_SESSION['user']['name'] = $name;
            $_SESSION['user']['email'] = $email;

            header('Location: ../profile_edit.php?success=1');
            break;

        case 'update_physical':
            // Validate numeric fields if provided
            $height = trim($_POST['height_cm'] ?? '');
            $weight = trim($_POST['weight_kg'] ?? '');
            $calorieGoal = trim($_POST['daily_calorie_goal'] ?? '');

            $errors = [];

            if (!empty($height) && (!is_numeric($height) || $height < 50 || $height > 250)) {
                $errors[] = 'Tinggi badan harus antara 50-250 cm';
            }

            if (!empty($weight) && (!is_numeric($weight) || $weight < 20 || $weight > 300)) {
                $errors[] = 'Berat badan harus antara 20-300 kg';
            }

            if (!empty($calorieGoal) && (!is_numeric($calorieGoal) || $calorieGoal < 1000 || $calorieGoal > 5000)) {
                $errors[] = 'Target kalori harus antara 1000-5000 kcal';
            }

            if (!empty($errors)) {
                header('Location: ../profile_edit.php?error=' . urlencode(implode(', ', $errors)) . '#physical');
                exit;
            }

            // Get current user data
            $currentUser = $user->find($_SESSION['user']['id']);
            if (!$currentUser) {
                header('Location: ../profile_edit.php?error=User tidak ditemukan');
                exit;
            }

            // Update physical information (merge with existing personal data)
            $user->update($_SESSION['user']['id'], [
                'name' => $currentUser['name'],
                'email' => $currentUser['email'],
                'phone' => $currentUser['phone'],
                'date_of_birth' => $currentUser['date_of_birth'],
                'gender' => $currentUser['gender'],
                'height_cm' => !empty($height) ? (float)$height : $currentUser['height_cm'],
                'weight_kg' => !empty($weight) ? (float)$weight : $currentUser['weight_kg'],
                'activity_level' => !empty(trim($_POST['activity_level'] ?? '')) ? trim($_POST['activity_level']) : $currentUser['activity_level'],
                'daily_calorie_goal' => !empty($calorieGoal) ? (int)$calorieGoal : $currentUser['daily_calorie_goal'],
                'role' => $currentUser['role'],
                'is_active' => $currentUser['is_active']
            ]);

            header('Location: ../profile_edit.php?success=1#physical');
            break;

        case 'change_password':
            // Validate input fields
            $currentPassword = trim($_POST['current_password'] ?? '');
            $newPassword = trim($_POST['new_password'] ?? '');
            $confirmPassword = trim($_POST['confirm_password'] ?? '');

            if (empty($currentPassword)) {
                header('Location: ../profile_edit.php?error=Password lama tidak boleh kosong#password');
                exit;
            }

            if (empty($newPassword)) {
                header('Location: ../profile_edit.php?error=Password baru tidak boleh kosong#password');
                exit;
            }

            if (strlen($newPassword) < 6) {
                header('Location: ../profile_edit.php?error=Password baru minimal 6 karakter#password');
                exit;
            }

            if (empty($confirmPassword)) {
                header('Location: ../profile_edit.php?error=Konfirmasi password tidak boleh kosong#password');
                exit;
            }

            // Verify current password
            $currentUser = $user->find($_SESSION['user']['id']);
            if (!$currentUser || !password_verify($currentPassword, $currentUser['password'])) {
                header('Location: ../profile_edit.php?error=password_incorrect#password');
                exit;
            }

            // Check if new passwords match
            if ($newPassword !== $confirmPassword) {
                header('Location: ../profile_edit.php?error=password_mismatch#password');
                exit;
            }

            // Update password
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$newHash, $_SESSION['user']['id']]);

            // Log out user to force re-login
            session_destroy();
            header('Location: ../login.php?message=password_changed');
            break;

        case 'delete_account':
            // Validate password input
            $password = trim($_POST['password'] ?? '');

            if (empty($password)) {
                header('Location: ../profile.php?error=Password tidak boleh kosong');
                exit;
            }

            // Verify password before deletion
            $currentUser = $user->find($_SESSION['user']['id']);
            if (!$currentUser || !password_verify($password, $currentUser['password'])) {
                header('Location: ../profile.php?error=password_incorrect');
                exit;
            }

            // Start transaction for safe deletion
            $db->beginTransaction();

            try {
                // Delete related data first
                $db->prepare("DELETE FROM schedules WHERE user_id = ?")->execute([$_SESSION['user']['id']]);
                // $db->prepare("DELETE FROM nutrition_logs WHERE user_id = ?")->execute([$_SESSION['user']['id']]); // tabel tidak ada
                $db->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$_SESSION['user']['id']]);
                $db->prepare("DELETE FROM user_goals WHERE user_id = ?")->execute([$_SESSION['user']['id']]);

                // Delete user account
                $db->prepare("DELETE FROM users WHERE id = ?")->execute([$_SESSION['user']['id']]);

                $db->commit();

                // Destroy session and redirect
                session_destroy();
                header('Location: ../index.php?message=account_deleted');
                exit;

            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }

            break;

        default:
            header('Location: ../profile.php?error=invalid_action');
            break;
    }
} catch (Exception $e) {
    header('Location: ../profile.php?error=' . urlencode($e->getMessage()));
}
exit;