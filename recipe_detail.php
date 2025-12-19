<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/ApiClientEdamam.php';

$config = require __DIR__ . '/config/env.php';
$api = new ApiClientEdamam($config['EDAMAM_APP_ID'], $config['EDAMAM_APP_KEY']);

$id = $_GET['id'] ?? '';
$recipe = null;
$error = '';
if ($id) {
    $recipe = $api->getRecipeDetail($id);
    if (isset($recipe['error'])) {
        $error = $recipe['error'];
    }
} else {
    $error = 'ID resep tidak ditemukan.';
}
?>

<div class="container py-4">
    <h4 class="mb-4">Detail Resep Makanan</h4>
    <?php if ($error): ?>
        <div class="alert alert-danger text-center mb-4"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($recipe): ?>
        <div class="row">
            <div class="col-md-5">
                <img src="<?= $recipe['image'] ?>" class="img-fluid rounded mb-3" alt="<?= htmlspecialchars($recipe['label']) ?>">
            </div>
            <div class="col-md-7">
                <h5><?= $recipe['label'] ?></h5>
                <p><b>Kalori:</b> <?= round($recipe['calories']) ?> kcal</p>
                <p><b>Bahan:</b></p>
                <ul>
                    <?php foreach ($recipe['ingredientLines'] as $ing): ?>
                        <li><?= htmlspecialchars($ing) ?></li>
                    <?php endforeach; ?>
                </ul>
                <p><b>Nutrisi utama:</b></p>
                <ul>
                    <?php foreach ($recipe['totalNutrients'] as $nut): ?>
                        <li><?= $nut['label'] ?>: <?= round($nut['quantity']) ?> <?= $nut['unit'] ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
