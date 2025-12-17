<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$config = require __DIR__ . '/config/env.php';
$db = (new Database($config))->getConnection();

$stmt = $db->prepare(
    "SELECT * FROM notifications
     WHERE user_id = ?
     ORDER BY created_at DESC"
);
$stmt->execute([$_SESSION['user']['id']]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<h4>Riwayat Notifikasi</h4>

<table class="table table-bordered">
    <tr>
        <th>Judul</th>
        <th>Status</th>
        <th>Tanggal</th>
    </tr>

<?php foreach ($data as $n): ?>
<tr>
    <td><?= $n['title'] ?></td>
    <td><?= $n['status'] ?></td>
    <td><?= $n['created_at'] ?></td>
</tr>
<?php endforeach; ?>
</table>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
