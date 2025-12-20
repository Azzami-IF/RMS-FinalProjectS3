<?php
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth_guard.php';
require_admin();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Food.php';
require_once __DIR__ . '/../classes/Admin/FoodsController.php';

use Admin\FoodsController;

$config = require __DIR__ . '/../config/env.php';
$db = (new Database($config))->getConnection();
$controller = new FoodsController($db);
$data = $controller->getData();
$message = $controller->getMessage();
$messageType = $controller->getMessageType();
?>


<div class="alert alert-info mt-4">Manajemen makanan lokal telah dinonaktifkan. Semua data makanan kini diambil dari Edamam API.</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
