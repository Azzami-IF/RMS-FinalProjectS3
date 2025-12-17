<?php

class ApiClientSpoonacular
{
    private string $apiKey;
    private Cache $cache;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->cache = new Cache();
    }

    public function healthyFood(int $calories = 500): array
    {
        $cacheKey = "spoonacular_healthy_$calories";

        // TTL 1 jam (3600 detik)
        $cached = $this->cache->get($cacheKey, 3600);
        if ($cached) {
            return $cached;
        }

        $url = "https://api.spoonacular.com/recipes/complexSearch?"
             . http_build_query([
                 'maxCalories' => $calories,
                 'number' => 6,
                 'addRecipeNutrition' => true,
                 'apiKey' => $this->apiKey
             ]);

        $response = file_get_contents($url);
        $data = json_decode($response, true);

        $this->cache->set($cacheKey, $data);

        return $data;
    }
}
