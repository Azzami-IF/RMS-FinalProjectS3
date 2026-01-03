<?php
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/classes/Cache.php';
require_once __DIR__ . '/classes/EdamamService.php';
require_once __DIR__ . '/controllers/RecommendationController.php';

// Setup OOP controller
$cache = new Cache();
$edamam = new EdamamService($config, $cache);
$controller = new RecommendationController($edamam);

$search = $_GET['q'] ?? '';
$focusLabel = $_GET['focus_label'] ?? '';

// Default calories: follow user's active goal if available, else user's profile daily calorie goal.
$defaultCalories = 600;
try {
    $userId = (int)($user['id'] ?? 0);
    if ($userId > 0 && isset($db) && $db instanceof PDO) {
        require_once __DIR__ . '/classes/UserGoal.php';
        $userGoal = new UserGoal($db);
        $activeGoal = $userGoal->findActive($userId);
        if ($activeGoal && !empty($activeGoal['daily_calorie_target'])) {
            $defaultCalories = (int)$activeGoal['daily_calorie_target'];
        } else {
            require_once __DIR__ . '/classes/User.php';
            $userModel = new User($db);
            $freshUser = $userModel->find($userId);
            if ($freshUser && !empty($freshUser['daily_calorie_goal'])) {
                $defaultCalories = (int)$freshUser['daily_calorie_goal'];
            }
        }
    }
} catch (Throwable $e) {
    // Ignore and keep fallback.
}

$defaultCalories = max(200, min(5000, (int)$defaultCalories));
$calories = (isset($_GET['calories']) && is_numeric($_GET['calories'])) ? (int)$_GET['calories'] : $defaultCalories;
$diet = $_GET['diet'] ?? '';
$mealType = $_GET['mealType'] ?? '';
$dishType = $_GET['dishType'] ?? '';
$health = $_GET['health'] ?? '';
$cuisineType = $_GET['cuisineType'] ?? '';
$exclude = $_GET['exclude'] ?? '';

// AJAX endpoint for Edamam search (for schedules.php autocomplete)
if (isset($_GET['ajax']) && $_GET['ajax'] == '1' && isset($_GET['q'])) {
    header('Content-Type: application/json');
    $params = [
        'q' => $_GET['q'],
        'calories' => 600
    ];
    $data = $controller->handle($params);
    echo json_encode($data);
    exit;
}

$data = null;
$error = '';

if (!function_exists('rms_translateDietLabel')) {
    function rms_translateDietLabel(string $label): string
    {
        $raw = trim($label);
        if ($raw === '') return '';

        // Canonical key: lowercase, words separated by hyphen.
        $key = strtolower(preg_replace('/[^a-z]+/i', '-', $raw) ?? $raw);
        $key = trim($key, '-');

        $map = [
            'balanced' => 'Seimbang',
            'high-protein' => 'Tinggi Protein',
            'high-fiber' => 'Tinggi Serat',
            'low-fat' => 'Rendah Lemak',
            'low-carb' => 'Rendah Karbohidrat',
            'low-sodium' => 'Rendah Natrium',
            'low-sugar' => 'Rendah Gula',
        ];

        return $map[$key] ?? $raw;
    }
}

$params = [
    'q' => $search,
    'calories' => $calories,
    'diet' => $diet,
    'mealType' => $mealType,
    'dishType' => $dishType,
    'health' => $health,
    'cuisineType' => $cuisineType
];
if ($exclude) $params['excluded'] = $exclude;
$data = $controller->handle($params);
if (isset($data['error'])) $error = $data['error'];
?>


<h4 class="mb-4">Rekomendasi Makanan Sehat</h4>

<form class="mb-4 p-3 rounded shadow-sm rms-card-adaptive" method="get" action="">
    <div class="row g-2 align-items-end">
        <div class="col-md-5">
            <label for="searchQ" class="form-label fw-semibold">Cari Makanan</label>
            <input type="text" id="searchQ" name="q" class="form-control" placeholder="Contoh: salad, ayam, vegetarian..." value="<?= htmlspecialchars($search) ?>">
        </div>
        <div class="col-md-2">
            <label for="diet" class="form-label">Preferensi Diet</label>
            <select name="diet" id="diet" class="form-select">
                <option value="">-- Semua --</option>
                <option value="balanced" <?= (isset($_GET['diet']) && $_GET['diet']==='balanced')?'selected':'' ?>>Seimbang</option>
                <option value="high-protein" <?= (isset($_GET['diet']) && $_GET['diet']==='high-protein')?'selected':'' ?>>Tinggi Protein</option>
                <option value="high-fiber" <?= (isset($_GET['diet']) && $_GET['diet']==='high-fiber')?'selected':'' ?>>Tinggi Serat</option>
                <option value="low-fat" <?= (isset($_GET['diet']) && $_GET['diet']==='low-fat')?'selected':'' ?>>Rendah Lemak</option>
                <option value="low-carb" <?= (isset($_GET['diet']) && $_GET['diet']==='low-carb')?'selected':'' ?>>Rendah Karbohidrat</option>
                <option value="low-sodium" <?= (isset($_GET['diet']) && $_GET['diet']==='low-sodium')?'selected':'' ?>>Rendah Natrium</option>
                <option value="low-sugar" <?= (isset($_GET['diet']) && $_GET['diet']==='low-sugar')?'selected':'' ?>>Rendah Gula</option>
                <option value="keto-friendly" <?= (isset($_GET['diet']) && $_GET['diet']==='keto-friendly')?'selected':'' ?>>Ramah Keto</option>
                <option value="gluten-free" <?= (isset($_GET['diet']) && $_GET['diet']==='gluten-free')?'selected':'' ?>>Bebas Gluten</option>
                <option value="wheat-free" <?= (isset($_GET['diet']) && $_GET['diet']==='wheat-free')?'selected':'' ?>>Bebas Gandum</option>
                <option value="vegetarian" <?= (isset($_GET['diet']) && $_GET['diet']==='vegetarian')?'selected':'' ?>>Vegetarian</option>
                <option value="vegan" <?= (isset($_GET['diet']) && $_GET['diet']==='vegan')?'selected':'' ?>>Vegan</option>
            </select>
        </div>
        <div class="col-md-4">
            <label for="exclude" class="form-label">Alergi/Bahan yang Dihindari</label>
            <div class="form-control d-flex flex-wrap align-items-center gap-1 px-2 py-1 rms-input-adaptive" id="exclude-tags-container" style="min-height:40px; box-shadow:none;">
                <span id="exclude-tags" class="d-flex flex-wrap align-items-center gap-1"></span>
                <input type="text" id="exclude" class="border-0 flex-grow-1" style="outline:none;box-shadow:none;min-width:180px;max-width:100%;padding:0.25rem 0.5rem;background:transparent;" placeholder="Contoh: kacang, telur, udang" autocomplete="off">
            </div>
            <input type="hidden" name="exclude" id="exclude-hidden" value="<?= htmlspecialchars($exclude ?? '') ?>">
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

<div class="row flex-nowrap" style="min-height:500px;">
    <div class="col-md-7 d-flex flex-column" id="food-list-section">
        <div class="card shadow-sm rounded-4 h-100 d-flex flex-column rms-card-adaptive" style="min-height:540px;">
            <div class="d-flex justify-content-between align-items-center mb-1 px-3 pt-3" style="gap:0.5rem;">
                <span class="fw-semibold flex-shrink-0">Daftar Makanan</span>
            </div>
            <div id="food-list" class="list-group list-group-flush flex-grow-1" style="overflow-y:auto;overflow-x:hidden;padding:0.5rem 0.5rem 0 0.5rem;max-height:calc(100vh - 140px);">
            <?php if ($data && isset($data['hits']) && count($data['hits'])): ?>
                <?php foreach ($data['hits'] as $i => $hit):
                    $food = $hit['recipe']; ?>
                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-center gap-3 food-item" data-index="<?= $i ?>">
                        <img src="<?= $food['image'] ?>" alt="img" class="rounded" style="width:56px;height:56px;object-fit:cover;">
                        <div class="flex-grow-1">
                            <div class="fw-semibold mb-1"><?= $food['label'] ?></div>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                <?php
                                // Edamam `calories` is for the whole recipe; schedules store per-serving.
                                $yield = isset($food['yield']) ? (float)$food['yield'] : 1;
                                if ($yield <= 0) $yield = 1;
                                $calPerServing = ((float)($food['calories'] ?? 0)) / $yield;
                                ?>
                                <div class="small text-muted">Kalori/porsi: <?= round($calPerServing) ?> kcal</div>
                                <?php if (!empty($food['dietLabels'])): ?>
                                    <?php foreach ($food['dietLabels'] as $diet): ?>
                                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis small" style="font-size:0.85em;font-weight:500;"> <?= htmlspecialchars(rms_translateDietLabel((string)$diet)) ?> </span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-success btn-sm ms-auto tambah-catatan-btn" data-food='<?= htmlspecialchars(json_encode($food)) ?>' title="Tambah ke Catatan"><i class="bi bi-plus-circle"></i></button>
                    </a>
                <?php endforeach; ?>
            <?php elseif ($data && isset($data['hits']) && !count($data['hits'])): ?>
                <div class="alert alert-info text-center mt-4">Tidak ada data makanan ditemukan untuk pencarian ini.</div>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-5 d-flex flex-column" id="food-detail-section">
        <div class="card shadow-sm rounded-4 h-100 d-flex flex-column rms-card-adaptive" style="min-height:540px;">
            <div class="d-flex justify-content-between align-items-center mb-1 px-3 pt-3" style="gap:0.5rem;">
                <span class="fw-semibold flex-shrink-0">Detail Makanan</span>
            </div>
            <div class="card shadow-sm h-100 d-flex flex-column rms-card-adaptive" style="min-height:540px;">
                <div id="food-detail-placeholder" class="text-center text-muted mt-5 flex-grow-1">
                    <i class="bi bi-egg-fried display-1"></i>
                    <p class="mt-3">Klik salah satu makanan untuk melihat detail.</p>
                </div>
                <div id="food-detail" style="display:none;overflow-y:auto;max-height:calc(100vh - 140px);"></div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
// Multi-input for exclude (allergy/avoid ingredients)
const excludeInput = document.getElementById('exclude');
const excludeTags = document.getElementById('exclude-tags');
const excludeHidden = document.getElementById('exclude-hidden');
let excludeList = (excludeHidden.value ? excludeHidden.value.split(',').map(x=>x.trim()).filter(Boolean) : []);

function renderExcludeTags() {
    excludeTags.innerHTML = excludeList.map((item, idx) =>
        `<span class='badge rounded-pill me-1 mb-1 exclude-badge-adaptive' style='font-size:0.95em;font-weight:500;'>${item} <span style='cursor:pointer;font-size:1.1em;margin-left:2px;' onclick='removeExcludeTag(${idx})'>&times;</span></span>`
    ).join('');
    excludeHidden.value = excludeList.join(',');
    // Hide placeholder if tags exist
    if (excludeList.length > 0) {
        excludeInput.setAttribute('placeholder', '');
    } else {
        excludeInput.setAttribute('placeholder', 'Contoh: kacang, telur, udang');
    }
}
function removeExcludeTag(idx) {
    excludeList.splice(idx, 1);
    renderExcludeTags();
}
excludeInput.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault();
        let val = excludeInput.value.trim();
        if (val && !excludeList.includes(val)) {
            excludeList.push(val);
            renderExcludeTags();
        }
        excludeInput.value = '';
    }
});
excludeInput.addEventListener('blur', function() {
    let val = excludeInput.value.trim();
    if (val && !excludeList.includes(val)) {
        excludeList.push(val);
        renderExcludeTags();
    }
    excludeInput.value = '';
});
window.removeExcludeTag = removeExcludeTag;
// On page load, render tags if any
renderExcludeTags();

let foodData = <?php echo json_encode($data && isset($data['hits']) ? array_column($data['hits'], 'recipe') : []); ?>;
const focusLabel = <?php echo json_encode((string)$focusLabel); ?>;
document.querySelectorAll('.food-item').forEach(function(item){
    item.addEventListener('click', function(e){
        e.preventDefault();
        const idx = this.getAttribute('data-index');
        showFoodDetail(idx);
        document.querySelectorAll('.food-item').forEach(i=>i.classList.remove('active'));
        this.classList.add('active');
    });
});

// Auto-open detail from schedules deep-link
(function autoFocusFromQuery() {
    if (!focusLabel || !Array.isArray(foodData) || foodData.length === 0) return;
    const target = String(focusLabel).trim().toLowerCase();
    if (!target) return;
    let idx = -1;
    for (let i = 0; i < foodData.length; i++) {
        const label = (foodData[i] && foodData[i].label) ? String(foodData[i].label).trim().toLowerCase() : '';
        if (label && label === target) { idx = i; break; }
    }
    if (idx === -1) return;
    showFoodDetail(idx);
    document.querySelectorAll('.food-item').forEach(i=>i.classList.remove('active'));
    const el = document.querySelector('.food-item[data-index="' + idx + '"]');
    if (el) {
        el.classList.add('active');
        try { el.scrollIntoView({ block: 'nearest' }); } catch (e) {}
    }
})();
function showFoodDetail(idx) {
        const food = foodData[idx];
        if (!food) return;
        const servings = (food && food.yield) ? Number(food.yield) : 1;
        const safeServings = (servings && servings > 0) ? servings : 1;
        const calPerServing = (food && typeof food.calories !== 'undefined') ? (Number(food.calories) / safeServings) : 0;

        const html = `
<div class="p-0">
    <div style="width:100%;height:180px;overflow:hidden;border-top-left-radius:0.7rem;border-top-right-radius:0.7rem;">
        <img src="${food.image}" alt="${food.label}" style="width:100%;height:100%;object-fit:cover;object-position:center;">
    </div>
    <div class="card-body py-3 px-4">
        <h6 class="card-title mb-3" style="font-weight:600;">${food.label}</h6>
        <dl class="row mb-3 small" style="margin-bottom:1.1rem!important;row-gap:0.5rem;">
            <dt class="col-5" style="font-weight:500;padding-bottom:0.5rem;">Kalori/porsi</dt><dd class="col-7" style="padding-bottom:0.5rem;">${Math.round(calPerServing)} kcal</dd>
            <dt class="col-5" style="font-weight:500;padding-bottom:0.5rem;">Bahan utama</dt><dd class="col-7" style="padding-bottom:0.5rem;">${food.ingredientLines.slice(0,2).join(", ")}...</dd>
        </dl>
        <div class="mb-2 small" style="margin-bottom:1.1rem!important;"><b>Detail bahan:</b><ul style="margin-bottom:0;padding-left:1.2em;">${food.ingredientLines.map(ing => `<li style='margin-bottom:0.35em;'>${ing}</li>`).join('')}</ul></div>
        ${(food.url ? `<div class="d-flex gap-2 justify-content-end mt-4">
            <a href="${food.url}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm px-3 py-2 shadow-sm flex-fill" style="border-radius:0.6rem;font-weight:500;letter-spacing:0.01em;min-width:140px;">Lihat Resep Lengkap &rarr;</a>
            <button type="button" class="btn btn-success tambah-catatan-btn flex-fill" data-food='${JSON.stringify(food)}' style="min-width:140px;"><i class="bi bi-plus-circle"></i> Tambah ke Catatan</button>
        </div>` : `<div class="d-flex justify-content-end mt-4">
            <button type="button" class="btn btn-success tambah-catatan-btn flex-fill" data-food='${JSON.stringify(food)}' style="min-width:140px;"><i class="bi bi-plus-circle"></i> Tambah ke Catatan</button>
        </div>`)}
    </div>
</div>`;
    document.getElementById('food-detail').innerHTML = html;
    document.getElementById('food-detail').style.display = '';
    document.getElementById('food-detail-placeholder').style.display = 'none';
}

document.addEventListener('click', function(e) {
    if (e.target.closest('.tambah-catatan-btn')) {
        const btn = e.target.closest('.tambah-catatan-btn');
        const food = btn.getAttribute('data-food');
        // Tampilkan modal input tanggal dan submit ke schedules.php
        showTambahCatatanModal(food);
    }
});

function formatLocalYmd(d) {
    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    return `${yyyy}-${mm}-${dd}`;
}

// Modal tambah catatan
function showTambahCatatanModal(foodJson) {
    let food = typeof foodJson === 'string' ? JSON.parse(foodJson) : foodJson;
    // Patch: pastikan protein, fat, carbs diambil dari totalNutrients jika ada
    if (food.totalNutrients) {
        food.protein = food.totalNutrients.PROCNT ? Math.round(food.totalNutrients.PROCNT.quantity) : 0;
        food.fat = food.totalNutrients.FAT ? Math.round(food.totalNutrients.FAT.quantity) : 0;
        food.carbs = food.totalNutrients.CHOCDF ? Math.round(food.totalNutrients.CHOCDF.quantity) : 0;
    }
    let modal = document.getElementById('tambahCatatanModal');
        const today = new Date();
        const maxDateObj = new Date(today);
        maxDateObj.setDate(maxDateObj.getDate() + 2);
        const todayYmd = formatLocalYmd(today);
        const maxYmd = formatLocalYmd(maxDateObj);

        if (!modal) {
                modal = document.createElement('div');
                modal.id = 'tambahCatatanModal';
                modal.innerHTML = `
                <div class="modal fade" tabindex="-1" id="tambahCatatanModalInner">
                    <div class="modal-dialog">
                        <div class="modal-content rms-card-adaptive">
                            <form method="post" action="process/schedule.process.php" id="formTambahCatatan">
                                <div class="modal-header rms-card-adaptive">
                                    <h5 class="modal-title">Catat Makanan Harian</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body rms-card-adaptive">
                                    <div class="mb-3">
                                        <label class="form-label">Makanan</label>
                                        <input type="text" class="form-control" value="${food.label}" readonly>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Jumlah porsi dimakan</label>
                                        <input type="number" name="quantity" class="form-control" min="1" step="1" value="1" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Tanggal</label>
                                        <input type="date" name="schedule_date" class="form-control" max="${maxYmd}" value="${todayYmd}" required>
                                        <div class="form-text">Tanggal maksimal 2 hari ke depan (toleransi zona waktu).</div>
                                    </div>
                                    <input type="hidden" name="edamam_food" value='${JSON.stringify(food)}'>
                                </div>
                                <div class="modal-footer rms-card-adaptive">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-success">Tambah ke Catatan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>`;
                document.body.appendChild(modal);
        } else {
        modal.querySelector('input[readonly]').value = food.label;
        modal.querySelector('input[name=edamam_food]').value = JSON.stringify(food);
        const qtyInput = modal.querySelector('input[name=quantity]');
        if (qtyInput) qtyInput.value = qtyInput.value || '1';
        modal.querySelector('input[name=schedule_date]').max = maxYmd;
        modal.querySelector('input[name=schedule_date]').value = todayYmd;
    }
    var bsModal = new bootstrap.Modal(modal.querySelector('.modal'));
    bsModal.show();
}

</script>

<style>
#food-list .food-item img { width:56px;height:56px; }
#food-list .food-item { font-size:1em; padding:0.75rem 1rem; }
#food-list .food-item.active { background:#e9f7ef; border-left:4px solid #28a745; }
#food-list .food-item.active .fw-semibold { color: #218c5a !important; }
#food-list-section { padding-right:0.5rem; }
#food-list .food-item { margin-bottom:0.25rem; border-radius:0.5rem; border:1px solid #e0e0e0; transition:box-shadow 0.15s; }
#food-list .food-item:last-child { margin-bottom:0; }
#food-list .food-item:hover { box-shadow:0 2px 8px rgba(40,167,69,0.08); border-color:#b2dfdb; }
#food-detail-section { min-height:400px; }
#food-detail { scrollbar-width:thin; }
#food-list { scrollbar-width:thin; }

#exclude-tags-container { min-height:40px; background:linear-gradient(90deg,#f8fffa,#e6f9f2); box-shadow:none; border:1px solid #b2f2dd; }
#exclude-tags .badge.exclude-badge-adaptive { margin-bottom:2px; background:linear-gradient(90deg,#e0f7fa,#b2f2dd); color:#218c5a; border:1px solid #b2f2dd; font-weight:500; transition:background 0.3s,color 0.3s,border-color 0.3s; }
</style>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
