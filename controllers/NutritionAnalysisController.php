<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../classes/Cache.php';
require_once __DIR__ . '/../classes/EdamamService.php';

class NutritionAnalysisController {
    private EdamamService $edamam;
    public function __construct(EdamamService $edamam) {
        $this->edamam = $edamam;
    }
    public function handle(array $ingredients): array {
        if (empty($ingredients)) return ['error' => 'Masukkan minimal satu bahan makanan.'];
        $data = $this->edamam->analyzeNutrition($ingredients);
        if (isset($data['error'])) return ['error' => $data['error']];
        return $data;
    }
}
