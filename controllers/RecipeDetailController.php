<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../classes/Cache.php';
require_once __DIR__ . '/../classes/EdamamService.php';

class RecipeDetailController {
    private EdamamService $edamam;
    public function __construct(EdamamService $edamam) {
        $this->edamam = $edamam;
    }
    public function handle(string $id): array {
        if (!$id) return ['error' => 'ID resep tidak ditemukan.'];
        $data = $this->edamam->getRecipeDetail($id);
        if (isset($data['error'])) return ['error' => $data['error']];
        return $data;
    }
}
