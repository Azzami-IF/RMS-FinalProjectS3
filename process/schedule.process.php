<?php
session_start();

require_once '../config/database.php';
require_once '../classes/Schedule.php';

if (!isset($_SESSION['user'])) {
    header('Location: ../login.php');
    exit;
}

$db = (new Database(require '../config/env.php'))->getConnection();
$schedule = new Schedule($db);

$action = $_POST['action'] ?? 'create';

try {
    // Validasi input
    $food_id = $_POST['food_id'] ?? null;
    $schedule_date = $_POST['schedule_date'] ?? null;
    if (!$food_id || !$schedule_date) {
        throw new Exception('Makanan dan tanggal wajib diisi.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $schedule_date)) {
        throw new Exception('Format tanggal tidak valid.');
    }
    // Cek makanan ada di DB
    $stmt = $db->prepare('SELECT id FROM foods WHERE id = ?');
    $stmt->execute([$food_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Makanan tidak ditemukan.');
    }
    // Cek duplikat jadwal hanya untuk create, bukan update
    if ($action === 'create') {
        $stmt = $db->prepare('SELECT id FROM schedules WHERE user_id = ? AND food_id = ? AND schedule_date = ?');
        $stmt->execute([$_SESSION['user']['id'], $food_id, $schedule_date]);
        if ($stmt->fetch()) {
            throw new Exception('Jadwal makan untuk makanan ini pada tanggal tersebut sudah ada.');
        }

        $schedule->create(
            $_SESSION['user']['id'],
            $food_id,
            $schedule_date,
            $_POST['meal_type_id'] ?? null,
            $_POST['quantity'] ?? 1,
            $_POST['notes'] ?? null
        );
        header('Location: ../schedules.php?success=schedule_created');
    } elseif ($action === 'update') {
        // Edit jadwal makan milik user sendiri
        $id = $_POST['id'] ?? null;
        if (!$id) throw new Exception('ID jadwal tidak ditemukan.');
        // Cek kepemilikan jadwal
        $stmt = $db->prepare('SELECT * FROM schedules WHERE id=? AND user_id=?');
        $stmt->execute([$id, $_SESSION['user']['id']]);
        $jadwal = $stmt->fetch();
        if (!$jadwal) throw new Exception('Jadwal tidak ditemukan atau bukan milik Anda.');
        // Update
        $schedule->update($id, $_SESSION['user']['id'], $food_id, $schedule_date, $_POST['notes'] ?? null);
        header('Location: ../schedules.php?success=schedule_updated');
    } elseif ($action === 'create_admin') {
        $schedule->create(
            $_SESSION['user']['id'],
            $food_id,
            $schedule_date,
            $_POST['meal_type_id'] ?? null,
            $_POST['quantity'] ?? 1,
            $_POST['notes'] ?? null
        );
        header('Location: ../schedules.php?success=schedule_created');
    } elseif ($action === 'create_admin') {
        if ($_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            exit('Akses ditolak');
        }
        $schedule->create(
            $_POST['user_id'],
            $food_id,
            $schedule_date,
            $_POST['meal_type_id'] ?? null,
            $_POST['quantity'] ?? 1,
            $_POST['notes'] ?? null
        );
        header('Location: ../admin/schedules.php?success=schedule_created');
    } elseif ($action === 'delete') {
        if ($_SESSION['user']['role'] !== 'admin') {
            http_response_code(403);
            exit('Akses ditolak');
        }
        $stmt = $db->prepare("DELETE FROM schedules WHERE id=?");
        $stmt->execute([$_POST['id']]);
        header('Location: ../admin/schedules.php?success=schedule_deleted');
    } else {
        header('Location: ../schedules.php?error=invalid_action');
    }
} catch (Exception $e) {
    $redirect_url = ($_SESSION['user']['role'] === 'admin') ? '../admin/schedules.php' : '../schedules.php';
    header('Location: ' . $redirect_url . '?error=' . urlencode($e->getMessage()));
}
exit;
