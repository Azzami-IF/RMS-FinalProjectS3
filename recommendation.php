<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/Cache.php';
require_once __DIR__ . '/classes/ApiClientSpoonacular.php';

$config = require __DIR__ . '/config/env.php';

$api = new ApiClientSpoonacular($config['spoonacular_key']);
$data = $api->healthyFood(600);
?>

<h4 class="mb-4">Rekomendasi Makanan Sehat</h4>

<div class="row">
<?php foreach ($data['results'] as $food): ?>
    <div class="col-md-4 mb-3">
        <div class="card h-100 shadow-sm">
            <img src="<?= $food['image'] ?>" class="card-img-top">
            <div class="card-body">
                <h6><?= $food['title'] ?></h6>
                <small class="text-muted">
                    <?= $food['nutrition']['nutrients'][0]['amount'] ?? '?' ?>
                    kcal
                </small>
            </div>
        </div>
    </div>
<?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
