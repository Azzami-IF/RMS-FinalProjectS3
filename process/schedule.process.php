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
    if ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if (!$id) throw new Exception('ID catatan tidak ditemukan.');
        // Jika admin, bisa hapus semua, jika user biasa hanya miliknya sendiri
        if ($_SESSION['user']['role'] === 'admin') {
            $stmt = $db->prepare("DELETE FROM schedules WHERE id=?");
            $stmt->execute([$id]);
            header('Location: ../admin/schedules.php?success=schedule_deleted');
        } else {
            $stmt = $db->prepare("DELETE FROM schedules WHERE id=? AND user_id=?");
            $stmt->execute([$id, $_SESSION['user']['id']]);
            header('Location: ../schedules.php?success=schedule_deleted');
        }
        exit;
    } elseif ($action === 'multi_delete') {
        $ids = $_POST['delete_ids'] ?? [];
        if (!is_array($ids) || count($ids) === 0) {
            throw new Exception('Tidak ada catatan yang dipilih.');
        }
        // Jika admin, hapus semua, jika user biasa hanya miliknya sendiri
        if ($_SESSION['user']['role'] === 'admin') {
            $stmt = $db->prepare("DELETE FROM schedules WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")");
            $stmt->execute();
            header('Location: ../admin/schedules.php?success=schedule_deleted');
        } else {
            // Query per id, hanya hapus catatan milik user
            $deleted = 0;
            foreach ($ids as $id) {
                $stmt = $db->prepare("DELETE FROM schedules WHERE id=? AND user_id=?");
                $stmt->execute([intval($id), $_SESSION['user']['id']]);
                $deleted += $stmt->rowCount();
            }
            header('Location: ../schedules.php?success=schedule_deleted');
        }
        exit;
    }
    // Validasi input tambah/ubah catatan
    $edamam_food = $_POST['edamam_food'] ?? null;
    $schedule_date = $_POST['schedule_date'] ?? null;
    if (!$edamam_food || !$schedule_date) {
        throw new Exception('Makanan dan tanggal wajib diisi.');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $schedule_date)) {
        throw new Exception('Format tanggal tidak valid.');
    }
    // No restriction on future dates. Allow any valid date.
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
        $imageUrl = isset($food['image']) ? (strlen($food['image']) > 255 ? substr($food['image'], 0, 255) : $food['image']) : null;
        $desc = $food['source'] ?? ($food['url'] ?? null);
        $protein = isset($food['protein']) ? $food['protein'] : 0;
        $fat = isset($food['fat']) ? $food['fat'] : 0;
        $carbs = isset($food['carbs']) ? $food['carbs'] : 0;
        $stmt = $db->prepare('INSERT INTO foods (name, calories, image_url, description, protein, fat, carbs) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $food['label'],
            $food['calories'],
            $imageUrl,
            $desc,
            $protein,
            $fat,
            $carbs
        ]);
        $food_id = $db->lastInsertId();
    }
    // Cek duplikat catatan hanya untuk create, bukan update
    if ($action === 'create') {
        $stmt = $db->prepare('SELECT id FROM schedules WHERE user_id = ? AND food_id = ? AND schedule_date = ?');
        $stmt->execute([$_SESSION['user']['id'], $food_id, $schedule_date]);
        if ($stmt->fetch()) {
            throw new Exception('Catatan makan untuk makanan ini pada tanggal tersebut sudah ada.');
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
        // Edit catatan makan milik user sendiri
        $id = $_POST['id'] ?? null;
        if (!$id) throw new Exception('ID catatan tidak ditemukan.');
        // Cek kepemilikan catatan
        $stmt = $db->prepare('SELECT * FROM schedules WHERE id=? AND user_id=?');
        $stmt->execute([$id, $_SESSION['user']['id']]);
        $catatan = $stmt->fetch();
        if (!$catatan) throw new Exception('Catatan tidak ditemukan atau bukan milik Anda.');
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
        $id = $_POST['id'] ?? null;
        if (!$id) throw new Exception('ID catatan tidak ditemukan.');
        // Jika admin, bisa hapus semua, jika user biasa hanya miliknya sendiri
        if ($_SESSION['user']['role'] === 'admin') {
            $stmt = $db->prepare("DELETE FROM schedules WHERE id=?");
            $stmt->execute([$id]);
            header('Location: ../admin/schedules.php?success=schedule_deleted');
        } else {
            $stmt = $db->prepare("DELETE FROM schedules WHERE id=? AND user_id=?");
            $stmt->execute([$id, $_SESSION['user']['id']]);
            header('Location: ../schedules.php?success=schedule_deleted');
        }
    } else {
        header('Location: ../schedules.php?error=invalid_action');
    }
} catch (Exception $e) {
    $redirect_url = ($_SESSION['user']['role'] === 'admin') ? '../admin/schedules.php' : '../schedules.php';
    header('Location: ' . $redirect_url . '?error=' . urlencode($e->getMessage()));
}
exit;
