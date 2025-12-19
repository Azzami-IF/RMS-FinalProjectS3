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
    // Support Edamam food
    $edamam_food = $_POST['edamam_food'] ?? null;
    $schedule_date = $_POST['schedule_date'] ?? null;
    if (!$edamam_food || !$schedule_date) {
        throw new Exception('Makanan dan tanggal wajib diisi.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $schedule_date)) {
        throw new Exception('Format tanggal tidak valid.');
    }
    $food = json_decode($edamam_food, true);
    if (!$food || empty($food['label'])) throw new Exception('Data makanan Edamam tidak valid.');
    // Cek apakah sudah ada di DB (berdasarkan nama dan kalori)
    $stmt = $db->prepare('SELECT id FROM foods WHERE name = ? AND calories = ?');
    $stmt->execute([$food['label'], $food['calories']]);
    $row = $stmt->fetch();
    if ($row) {
        $food_id = $row['id'];
    } else {
        // Insert ke tabel foods (minimal kolom: name, calories, image_url, description, source_url)
        $stmt = $db->prepare('INSERT INTO foods (name, calories, image_url, description) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $food['label'],
            $food['calories'],
            $food['image'] ?? null,
            $food['source'] ?? ($food['url'] ?? null)
        ]);
        $food_id = $db->lastInsertId();
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
            null, // meal_type_id
            1,    // quantity
            null  // notes
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
