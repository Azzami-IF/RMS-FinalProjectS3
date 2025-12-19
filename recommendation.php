<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/Cache.php';
require_once __DIR__ . '/classes/ApiClientEdamam.php';

$config = require __DIR__ . '/config/env.php';

// Ambil parameter pencarian dari form
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$calories = 600; // default kalori
$diet = $_GET['diet'] ?? '';
$exclude = $_GET['exclude'] ?? '';

// Inisialisasi Edamam API
$api = new ApiClientEdamam($config['EDAMAM_APP_ID'], $config['EDAMAM_APP_KEY'], 'id.rms.for.us@gmail.com');
$data = null;
$error = '';
if ($search !== '') {
    // Kirim exclude ke health jika ada
    $health = '';
    if ($exclude) {
        // Edamam menerima health=alcohol-free, peanut-free, dsb, tapi untuk custom exclude gunakan 'excluded'
        $health = $exclude;
    }
    $data = $api->searchFood($search, $calories, $diet, '', '', $health);
    if (isset($data['error'])) {
        $error = $data['error'];
    }
} elseif (isset($_GET['q'])) {
    $error = 'Masukkan kata kunci pencarian makanan.';
}
?>

<h4 class="mb-4">Rekomendasi Makanan Sehat</h4>

<form class="mb-4 p-3 rounded shadow-sm bg-light" method="get" action="">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="searchQ" class="form-label fw-semibold">Cari Makanan</label>
            <input type="text" id="searchQ" name="q" class="form-control" placeholder="Contoh: salad, ayam, vegetarian..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-3">
            <label for="diet" class="form-label">Preferensi Diet</label>
            <select name="diet" id="diet" class="form-select">
                <option value="">-- Semua --</option>
                <option value="balanced" <?= (isset($_GET['diet']) && $_GET['diet']==='balanced')?'selected':'' ?>>Balanced</option>
                <option value="high-protein" <?= (isset($_GET['diet']) && $_GET['diet']==='high-protein')?'selected':'' ?>>High Protein</option>
                <option value="low-fat" <?= (isset($_GET['diet']) && $_GET['diet']==='low-fat')?'selected':'' ?>>Low Fat</option>
                <option value="low-carb" <?= (isset($_GET['diet']) && $_GET['diet']==='low-carb')?'selected':'' ?>>Low Carb</option>
            </select>
        </div>
        <div class="col-md-3">
            <label for="exclude" class="form-label">Alergi/Bahan yang Dihindari</label>
            <input type="text" id="exclude" name="exclude" class="form-control" placeholder="Contoh: kacang, telur" value="<?= htmlspecialchars($exclude) ?>">
        </div>
        <div class="col-md-1 d-grid">
            <button type="submit" class="btn btn-success fw-bold">
                <i class="bi bi-search"></i> Cari
            </button>
        </div>
    </div>
</form>

<?php if ($error): ?>
    <div class="alert alert-danger text-center mb-4"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row">
<?php if ($data && isset($data['hits']) && count($data['hits'])): ?>
    <?php foreach ($data['hits'] as $hit):
        $food = $hit['recipe']; ?>
        <div class="col-md-4 mb-3">
            <div class="card h-100 shadow-sm">
                <a href="recipe_detail.php?id=<?= urlencode(basename($food['uri'])) ?>">
                    <img src="<?= $food['image'] ?>" class="card-img-top" alt="<?= htmlspecialchars($food['label']) ?>">
                </a>
                <div class="card-body">
                    <h6>
                        <a href="recipe_detail.php?id=<?= urlencode(basename($food['uri'])) ?>" class="text-decoration-none">
                            <?= $food['label'] ?>
                        </a>
                    </h6>
                    <small class="text-muted">
                        <?= round($food['calories']) ?> kcal
                    </small>
                    <ul class="mt-2 small text-muted">
                        <?php foreach ($food['ingredientLines'] as $ing): ?>
                            <li><?= htmlspecialchars($ing) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
<?php elseif ($data && isset($data['hits']) && !count($data['hits'])): ?>
    <div class="col-12">
        <div class="alert alert-info text-center">Tidak ada data makanan ditemukan untuk pencarian ini.</div>
    </div>
<?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
