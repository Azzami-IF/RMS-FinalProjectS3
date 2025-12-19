<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/ApiClientEdamam.php';

$config = require __DIR__ . '/config/env.php';
$api = new ApiClientEdamam($config['EDAMAM_APP_ID'], $config['EDAMAM_APP_KEY']);

$ingredients = [];
$result = null;
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = trim($_POST['ingredients'] ?? '');
    $ingredients = array_filter(array_map('trim', explode("\n", $input)));
    if ($ingredients) {
        $result = $api->analyzeNutrition($ingredients);
        if (isset($result['error'])) {
            $error = $result['error'];
        }
    } else {
        $error = 'Masukkan minimal satu bahan makanan.';
    }
}
?>
<div class="container py-4">
    <h4 class="mb-4">Analisis Nutrisi Makanan (Edamam)</h4>
    <form method="post" class="mb-4 p-3 rounded shadow-sm bg-light">
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
                        <li><?= $nut['label'] ?>: <?= round($nut['quantity']) ?> <?= $nut['unit'] ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
