<?php
require_once __DIR__ . '/../classes/AppContext.php';
require_once '../classes/Schedule.php';
require_once '../classes/Cache.php';
require_once '../classes/ApiClientEdamam.php';

$app = AppContext::fromRootDir(__DIR__ . '/..');
$app->requireUser();

$config = $app->config();
$db = $app->db();
$userId = (int)$app->user()['id'];
$role = (string)($app->role() ?? '');
$schedule = new Schedule($db);
$edamam = new ApiClientEdamam($config['EDAMAM_APP_ID'] ?? '', $config['EDAMAM_APP_KEY'] ?? '', $config['EDAMAM_USER_ID'] ?? '');

$action = $_POST['action'] ?? ($_GET['action'] ?? 'create');

function rms_validateScheduleDateMax(string $ymd, int $maxFutureDays = 2): void {
    $dt = DateTime::createFromFormat('Y-m-d', $ymd);
    $errors = DateTime::getLastErrors();
    if ($dt === false || ($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0) {
        throw new Exception('Format tanggal tidak valid.');
    }
    $today = new DateTime('today');
    $maxFuture = (clone $today)->modify('+' . $maxFutureDays . ' days');
    if ($dt > $maxFuture) {
        throw new Exception('Tanggal maksimal ' . $maxFutureDays . ' hari ke depan.');
    }
}

try {
    if ($action === 'create_from_recipe') {
        $recipeId = (string)($_GET['recipe_id'] ?? $_POST['recipe_id'] ?? '');
        $recipeId = trim($recipeId);
        if ($recipeId === '') throw new Exception('Resep tidak valid.');
        if (!preg_match('/^[A-Za-z0-9]+$/', $recipeId)) {
            throw new Exception('Format resep tidak valid.');
        }

        $schedule_date = (string)($_GET['schedule_date'] ?? $_POST['schedule_date'] ?? date('Y-m-d'));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $schedule_date)) {
            throw new Exception('Format tanggal tidak valid.');
        }
        rms_validateScheduleDateMax($schedule_date, 2);

        $recipe = $edamam->getRecipeDetail($recipeId);
        if (isset($recipe['error'])) {
            throw new Exception((string)$recipe['error']);
        }
        if (empty($recipe['label'])) {
            throw new Exception('Data resep tidak lengkap.');
        }

        $food = [
            'label' => (string)$recipe['label'],
            'calories' => (float)($recipe['calories'] ?? 0),
            'yield' => (float)($recipe['yield'] ?? 1),
            'image' => (string)($recipe['image'] ?? ''),
            'source' => (string)($recipe['source'] ?? ''),
            'url' => (string)($recipe['url'] ?? ''),
        ];

        $tn = $recipe['totalNutrients'] ?? [];
        $food['protein'] = isset($tn['PROCNT']['quantity']) ? (float)$tn['PROCNT']['quantity'] : 0;
        $food['fat'] = isset($tn['FAT']['quantity']) ? (float)$tn['FAT']['quantity'] : 0;
        $food['carbs'] = isset($tn['CHOCDF']['quantity']) ? (float)$tn['CHOCDF']['quantity'] : 0;

        // Normalize totals to per-serving
        $servings = (float)($food['yield'] ?? 1);
        if ($servings <= 0) $servings = 1;
        $food['calories'] = (float)$food['calories'] / $servings;
        $food['protein'] = (float)($food['protein'] ?? 0) / $servings;
        $food['fat'] = (float)($food['fat'] ?? 0) / $servings;
        $food['carbs'] = (float)($food['carbs'] ?? 0) / $servings;

        // Upsert food
        $stmt = $db->prepare('SELECT id, image_url FROM foods WHERE name = ? AND calories = ?');
        $stmt->execute([$food['label'], $food['calories']]);
        $row = $stmt->fetch();
        if ($row) {
            $food_id = $row['id'];
            $incomingImage = (string)($food['image'] ?? '');
            if ($incomingImage !== '') {
                $incomingImage = (strlen($incomingImage) > 1024 ? substr($incomingImage, 0, 1024) : $incomingImage);
                $currentImage = (string)($row['image_url'] ?? '');
                if ($currentImage === '' || strlen($currentImage) < strlen($incomingImage)) {
                    $stmt = $db->prepare('UPDATE foods SET image_url=? WHERE id=?');
                    $stmt->execute([$incomingImage, $food_id]);
                }
            }
        } else {
            $imageUrl = $food['image'] ? (strlen($food['image']) > 1024 ? substr($food['image'], 0, 1024) : $food['image']) : null;
            $desc = $food['source'] ?: ($food['url'] ?: null);
            $stmt = $db->prepare('INSERT INTO foods (name, calories, image_url, description, protein, fat, carbs) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $food['label'],
                $food['calories'],
                $imageUrl,
                $desc,
                (float)($food['protein'] ?? 0),
                (float)($food['fat'] ?? 0),
                (float)($food['carbs'] ?? 0),
            ]);
            $food_id = $db->lastInsertId();
        }

        // Prevent duplicate schedule entry
        $stmt = $db->prepare('SELECT id FROM schedules WHERE user_id = ? AND food_id = ? AND schedule_date = ?');
        $stmt->execute([$userId, $food_id, $schedule_date]);
        if ($stmt->fetch()) {
            throw new Exception('Catatan makan untuk makanan ini pada tanggal tersebut sudah ada.');
        }

        $quantity = (float)($_POST['quantity'] ?? $_GET['quantity'] ?? 1);
        if ($quantity <= 0) {
            throw new Exception('Jumlah porsi tidak valid.');
        }
        if ($quantity > 100) {
            throw new Exception('Jumlah porsi terlalu besar.');
        }

        $schedule->create(
            $userId,
            $food_id,
            $schedule_date,
            null,
            $quantity,
            null
        );

        // Optional: mark notification read
        $notifId = (int)($_GET['notif_id'] ?? $_POST['notif_id'] ?? 0);
        if ($notifId > 0) {
            $stmt = $db->prepare("UPDATE notifications SET status='read' WHERE id=? AND user_id=?");
            $stmt->execute([$notifId, $userId]);
        }

        header('Location: ../schedules.php?success=schedule_created');
        exit;
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? null;
        if (!$id) throw new Exception('ID catatan tidak ditemukan.');
        // Jika admin, bisa hapus semua, jika user biasa hanya miliknya sendiri
        if ($role === 'admin') {
            $stmt = $db->prepare("DELETE FROM schedules WHERE id=?");
            $stmt->execute([$id]);
            header('Location: ../admin/schedules.php?success=schedule_deleted');
        } else {
            $stmt = $db->prepare("DELETE FROM schedules WHERE id=? AND user_id=?");
            $stmt->execute([$id, $userId]);
            header('Location: ../schedules.php?success=schedule_deleted');
        }
        exit;
    } elseif ($action === 'multi_delete') {
        $ids = $_POST['delete_ids'] ?? [];
        if (!is_array($ids) || count($ids) === 0) {
            throw new Exception('Tidak ada catatan yang dipilih.');
        }
        // Jika admin, hapus semua, jika user biasa hanya miliknya sendiri
        if ($role === 'admin') {
            $stmt = $db->prepare("DELETE FROM schedules WHERE id IN (" . implode(',', array_map('intval', $ids)) . ")");
            $stmt->execute();
            header('Location: ../admin/schedules.php?success=schedule_deleted');
        } else {
            // Query per id, hanya hapus catatan milik user
            $deleted = 0;
            foreach ($ids as $id) {
                $stmt = $db->prepare("DELETE FROM schedules WHERE id=? AND user_id=?");
                $stmt->execute([intval($id), $userId]);
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
    rms_validateScheduleDateMax((string)$schedule_date, 2);
    $food = json_decode($edamam_food, true);
    if (!$food || empty($food['label'])) throw new Exception('Data makanan Edamam tidak valid.');

    // Normalize Edamam recipe totals to per-serving (Edamam `calories` and nutrient totals are for the whole recipe)
    $servings = (float)($food['yield'] ?? 1);
    if ($servings <= 0) $servings = 1;
    $food['calories'] = (float)($food['calories'] ?? 0);
    $food['calories'] = $food['calories'] / $servings;
    if (isset($food['protein'])) $food['protein'] = ((float)$food['protein']) / $servings;
    if (isset($food['fat'])) $food['fat'] = ((float)$food['fat']) / $servings;
    if (isset($food['carbs'])) $food['carbs'] = ((float)$food['carbs']) / $servings;
    // Cek apakah sudah ada di DB (berdasarkan nama dan kalori per serving)
    $stmt = $db->prepare('SELECT id, image_url FROM foods WHERE name = ? AND calories = ?');
    $stmt->execute([$food['label'], $food['calories']]);
    $row = $stmt->fetch();
    if ($row) {
        $food_id = $row['id'];
        $incomingImage = isset($food['image']) ? (string)$food['image'] : '';
        if ($incomingImage !== '') {
            $incomingImage = (strlen($incomingImage) > 1024 ? substr($incomingImage, 0, 1024) : $incomingImage);
            $currentImage = (string)($row['image_url'] ?? '');
            if ($currentImage === '' || strlen($currentImage) < strlen($incomingImage)) {
                $stmt = $db->prepare('UPDATE foods SET image_url=? WHERE id=?');
                $stmt->execute([$incomingImage, $food_id]);
            }
        }
    } else {
        // Insert ke tabel foods (per serving): name, calories, image_url, description, protein, fat, carbs
        $imageUrl = isset($food['image']) ? (strlen($food['image']) > 1024 ? substr($food['image'], 0, 1024) : $food['image']) : null;
        $desc = $food['source'] ?? ($food['url'] ?? null);
        $protein = isset($food['protein']) ? (float)$food['protein'] : 0;
        $fat = isset($food['fat']) ? (float)$food['fat'] : 0;
        $carbs = isset($food['carbs']) ? (float)$food['carbs'] : 0;
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
    $quantity = (float)($_POST['quantity'] ?? 1);
    if ($quantity <= 0) {
        throw new Exception('Jumlah porsi tidak valid.');
    }
    if ($quantity > 100) {
        throw new Exception('Jumlah porsi terlalu besar.');
    }

    // Cek duplikat catatan hanya untuk create, bukan update
    if ($action === 'create') {
        $stmt = $db->prepare('SELECT id FROM schedules WHERE user_id = ? AND food_id = ? AND schedule_date = ?');
        $stmt->execute([$userId, $food_id, $schedule_date]);
        if ($stmt->fetch()) {
            throw new Exception('Catatan makan untuk makanan ini pada tanggal tersebut sudah ada.');
        }

        $schedule->create(
            $userId,
            $food_id,
            $schedule_date,
            null,     // meal_type_id
            $quantity, // quantity
            null      // notes
        );
        header('Location: ../schedules.php?success=schedule_created');
    } elseif ($action === 'update') {
        // Edit catatan makan milik user sendiri
        $id = $_POST['id'] ?? null;
        if (!$id) throw new Exception('ID catatan tidak ditemukan.');
        // Cek kepemilikan catatan
        $stmt = $db->prepare('SELECT * FROM schedules WHERE id=? AND user_id=?');
        $stmt->execute([$id, $userId]);
        $catatan = $stmt->fetch();
        if (!$catatan) throw new Exception('Catatan tidak ditemukan atau bukan milik Anda.');
        // Update
        $schedule->update($id, $userId, $food_id, $schedule_date, $_POST['notes'] ?? null);
        header('Location: ../schedules.php?success=schedule_updated');
    } elseif ($action === 'create_admin') {
        if ($role !== 'admin') {
            http_response_code(403);
            exit('Akses ditolak');
        }
        $schedule->create(
            $_POST['user_id'] ?? $userId,
            $food_id,
            $schedule_date,
            $_POST['meal_type_id'] ?? null,
            $_POST['quantity'] ?? 1,
            $_POST['notes'] ?? null
        );
        header('Location: ../admin/schedules.php?success=schedule_created');
    } else {
        header('Location: ../schedules.php?error=invalid_action');
    }
} catch (Exception $e) {
    $redirect_url = ($role === 'admin') ? '../admin/schedules.php' : '../schedules.php';
    header('Location: ' . $redirect_url . '?error=' . urlencode($e->getMessage()));
}
exit;
