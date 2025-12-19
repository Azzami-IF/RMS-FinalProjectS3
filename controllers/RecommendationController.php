<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../classes/Cache.php';
require_once __DIR__ . '/../classes/EdamamService.php';

class RecommendationController {
    private EdamamService $edamam;
    public function __construct(EdamamService $edamam) {
        $this->edamam = $edamam;
    }
    public function handle(array $request): array {
        $search = trim($request['q'] ?? '');
        $calories = (int)($request['calories'] ?? 600);
        // Support multi exclude (comma or array)
        $excluded = $request['excluded'] ?? '';
        if (is_array($excluded)) {
            $excluded = implode(',', $excluded);
        }
        $options = [
            'diet' => $request['diet'] ?? '',
            'mealType' => $request['mealType'] ?? '',
            'dishType' => $request['dishType'] ?? '',
            'health' => $request['health'] ?? '',
            'cuisineType' => $request['cuisineType'] ?? '',
            'excluded' => $excluded
        ];
        // If search is empty, use a default keyword (e.g. 'food') to always show results
        $query = $search !== '' ? $search : 'food';
        $data = $this->edamam->searchRecipes($query, $calories, $options);
        if (isset($data['error'])) return ['error' => $data['error']];
        return $data;
    }
}
