<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/Cache.php';
require_once __DIR__ . '/classes/EdamamService.php';
require_once __DIR__ . '/controllers/NutritionAnalysisController.php';
$cache = new Cache();
$edamam = new EdamamService($config, $cache);
$controller = new NutritionAnalysisController($edamam);

$ingredients = [];
$result = null;
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['ingredients'] ?? '');
    $ingredients = array_filter(array_map('trim', explode("\n", $input)));
    if ($ingredients) {
        $result = $controller->handle($ingredients);
        if (isset($result['error'])) $error = $result['error'];
    } else {
        $error = 'Masukkan minimal satu bahan makanan.';
    }
}
?>
<div class="container py-4">
    <h4 class="mb-4">Analisis Nutrisi Makanan (Edamam)</h4>
    <form method="post" class="mb-4 p-3 rounded shadow-sm rms-card-adaptive">
        <label for="ingredients" class="form-label fw-semibold">Daftar Bahan (satu per baris):</label>
        <textarea name="ingredients" id="ingredients" class="form-control mb-2" rows="5" placeholder="Contoh:\n2 telur\n100g ayam\n1 sdm minyak zaitun" required><?= htmlspecialchars(implode("\n", $ingredients)) ?></textarea>
        <button type="submit" class="btn btn-primary">Analisis Nutrisi</button>
    </form>
    <?php if ($error): ?>
        <div class="alert alert-danger text-center mb-4"><?= htmlspecialchars($error) ?></div>
    <?php elseif ($result): ?>
        <div class="card mb-4">
            <div class="card-body">
                <h5>Hasil Analisis Nutrisi</h5>
                <ul>
                    <li><b>Kalori:</b> <?= round($result['calories']) ?> kcal</li>
                    <?php foreach ($result['totalNutrients'] as $nut): ?>
                        <li><?= htmlspecialchars(rms_localize_nutrient_label((string)($nut['label'] ?? ''))) ?>: <?= round($nut['quantity']) ?> <?= htmlspecialchars((string)($nut['unit'] ?? '')) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
