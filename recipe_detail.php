<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/Cache.php';
require_once __DIR__ . '/classes/EdamamService.php';
require_once __DIR__ . '/controllers/RecipeDetailController.php';

$config = require __DIR__ . '/config/env.php';
$cache = new Cache();
$edamam = new EdamamService($config, $cache);
$controller = new RecipeDetailController($edamam);

$id = $_GET['id'] ?? '';
$recipe = null;
$error = '';

function rms_localize_nutrient_label(string $label): string {
    static $map = [
        'Calories' => 'Kalori',
        'Energy' => 'Energi',
        'Protein' => 'Protein',
        'Fat' => 'Lemak',
        'Carbs' => 'Karbohidrat',
        'Carbohydrates' => 'Karbohidrat',
        'Fiber' => 'Serat',
        'Sugars' => 'Gula',
        'Sugar' => 'Gula',
        'Sodium' => 'Natrium',
        'Cholesterol' => 'Kolesterol',
        'Potassium' => 'Kalium',
        'Calcium' => 'Kalsium',
        'Iron' => 'Zat Besi',
        'Magnesium' => 'Magnesium',
        'Phosphorus' => 'Fosfor',
        'Zinc' => 'Seng',
        'Vitamin A' => 'Vitamin A',
        'Vitamin C' => 'Vitamin C',
        'Vitamin D' => 'Vitamin D',
        'Vitamin E' => 'Vitamin E',
        'Vitamin K' => 'Vitamin K',
        'Thiamin (B1)' => 'Tiamin (B1)',
        'Riboflavin (B2)' => 'Riboflavin (B2)',
        'Niacin (B3)' => 'Niasin (B3)',
        'Vitamin B6' => 'Vitamin B6',
        'Folate (total)' => 'Folat (total)',
        'Vitamin B12' => 'Vitamin B12',
    ];

    return $map[$label] ?? $label;
}

if ($id) {
    $recipe = $controller->handle($id);
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
                        <li><?= htmlspecialchars(rms_localize_nutrient_label((string)($nut['label'] ?? ''))) ?>: <?= round($nut['quantity']) ?> <?= htmlspecialchars((string)($nut['unit'] ?? '')) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
