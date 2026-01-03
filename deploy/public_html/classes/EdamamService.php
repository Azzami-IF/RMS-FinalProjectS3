<?php

class EdamamService
{
    private string $appId;
    private string $appKey;
    private string $userId;
    private Cache $cache;

    public function __construct(array $config, Cache $cache)
    {
        $this->appId = $config['EDAMAM_APP_ID'] ?? '';
        $this->appKey = $config['EDAMAM_APP_KEY'] ?? '';
        $this->userId = $config['EDAMAM_USER_ID'] ?? '';
        $this->cache = $cache;
    }

    public function searchRecipes(string $query, int $calories = 600, array $options = []): array
    {
        $cacheKey = 'edamam_search_' . md5($query . $calories . json_encode($options));
        $cached = $this->cache->get($cacheKey, 3600);
        if ($cached) return $cached;

        $params = [
            'type' => 'public',
            'q' => $query,
            'app_id' => $this->appId,
            'app_key' => $this->appKey,
            'calories' => "0-$calories",
            'imageSize' => 'REGULAR',
            'size' => 6
        ];
        foreach (['diet','mealType','dishType','health','cuisineType','excluded'] as $key) {
            if (!empty($options[$key])) $params[$key] = $options[$key];
        }
        $url = 'https://api.edamam.com/api/recipes/v2?' . http_build_query($params);
        $data = $this->request($url);
        if (isset($data['hits'])) $this->cache->set($cacheKey, $data);
        return $data;
    }

    public function getRecipeDetail(string $id): array
    {
        $params = [
            'type' => 'public',
            'app_id' => $this->appId,
            'app_key' => $this->appKey,
        ];
        $url = (strpos($id, 'http') === 0 ? $id : 'https://api.edamam.com/api/recipes/v2/' . $id) . '?' . http_build_query($params);
        return $this->request($url);
    }

    public function analyzeNutrition(array $ingredients): array
    {
        $url = 'https://api.edamam.com/api/nutrition-details?app_id=' . $this->appId . '&app_key=' . $this->appKey;
        $body = json_encode(['ingr' => $ingredients]);
        return $this->request($url, 'POST', $body);
    }

    private function request(string $url, string $method = 'GET', $body = null): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $headers = [];
        if (!empty($this->userId)) $headers[] = 'Edamam-Account-User: ' . $this->userId;
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $headers[] = 'Content-Type: application/json';
        }
        if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        $data = json_decode($response, true);
        if (!is_array($data)) return ['error' => 'Invalid response from Edamam API.'];
        if (isset($data['error']) || isset($data['message'])) return ['error' => ($data['error'] ?? $data['message'])];
        return $data;
    }
}
