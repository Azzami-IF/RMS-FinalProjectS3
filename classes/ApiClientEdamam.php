<?php

class ApiClientEdamam
{
    private string $appId;
    private string $appKey;
    private Cache $cache;
    private string $user;

    public function __construct(string $appId, string $appKey, string $user = '')
    {
        $this->appId = $appId;
        $this->appKey = $appKey;
        $this->cache = new Cache();
        $this->user = $user;
    }

    /**
     * Ambil rekomendasi makanan sehat dari Edamam API berdasarkan kalori maksimal
     * @param int $calories
     * @return array
     */
    public function healthyFood(int $calories = 500): array
    {
        $cacheKey = "edamam_healthy_$calories";
        $cached = $this->cache->get($cacheKey, 3600);
        if ($cached) {
            return $cached;
        }

        // Gunakan parameter minimal agar hasil selalu ada
        $params = [
            'type' => 'public',
            'q' => 'healthy', // pencarian menu sehat
            'app_id' => $this->appId,
            'app_key' => $this->appKey,
            'calories' => "0-$calories",
            'imageSize' => 'REGULAR',
            'field' => ['label','image','calories','totalNutrients','ingredientLines'],
            'size' => 6
        ];
        $url = "https://api.edamam.com/api/recipes/v2?" . http_build_query($params);

        $response = $this->curlRequest($url);
        if ($response === false) {
            error_log('Edamam API request failed: ' . $url);
            return [];
        }
        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['hits'])) {
            if (isset($data['error'])) {
                error_log('Edamam API error: ' . $data['error']);
            } else {
                error_log('Edamam API response invalid: ' . $response);
            }
            return [];
        }
        $this->cache->set($cacheKey, $data);
        return $data;
    }

    /**
     * Cari makanan sehat dari Edamam API berdasarkan kata kunci dan kalori
     */
    public function searchFood(string $query = 'healthy', int $calories = 600, string $diet = '', string $mealType = '', string $dishType = '', string $health = '', string $cuisineType = ''): array
    {
        $cacheKey = "edamam_search_{$query}_{$calories}_{$diet}_{$mealType}_{$dishType}_{$health}_{$cuisineType}";
        $cached = $this->cache->get($cacheKey, 3600);
        if ($cached) {
            return $cached;
        }
        $params = [
            'type' => 'public',
            'q' => $query,
            'app_id' => $this->appId,
            'app_key' => $this->appKey,
            'calories' => "0-$calories",
            'imageSize' => 'REGULAR',
            'field' => ['label','image','calories','totalNutrients','ingredientLines'],
            'size' => 6
        ];
        if ($diet) $params['diet'] = $diet;
        if ($mealType) $params['mealType'] = $mealType;
        if ($dishType) $params['dishType'] = $dishType;
        if ($health) $params['health'] = $health;
        if ($cuisineType) $params['cuisineType'] = $cuisineType;
        $url = "https://api.edamam.com/api/recipes/v2?" . http_build_query($params);
        $response = $this->curlRequest($url);
        if ($response === false) {
            return ['error' => 'Gagal terhubung ke Edamam API.'];
        }
        $data = json_decode($response, true);
        // Jika response bukan array atau tidak ada hits, tampilkan error dan info response
        if (!is_array($data) || !isset($data['hits'])) {
            // Jika response hanya info akun atau format aneh, tampilkan semua key utama
            $debug = '';
            if (is_array($data)) {
                $debug = ' [Key: ' . implode(', ', array_keys($data)) . ']';
            }
            if (isset($data['error'])) {
                return ['error' => 'Edamam API: ' . $data['error'] . $debug];
            } elseif (isset($data['message'])) {
                return ['error' => 'Edamam API: ' . $data['message'] . $debug];
            } else {
                return ['error' => 'Response Edamam API tidak valid.' . $debug . ' Raw: ' . substr($response,0,200)];
            }
        }
        $this->cache->set($cacheKey, $data);
        return $data;
    }

    /**
     * Ambil detail resep dari Edamam API berdasarkan uri/id
     * @param string $uri Edamam Recipe URI (atau ID)
     * @return array
     */
    public function getRecipeDetail(string $uri): array
    {
        // Edamam API expects full URI or ID
        $params = [
            'type' => 'public',
            'app_id' => $this->appId,
            'app_key' => $this->appKey,
        ];
        // Jika hanya id, tambahkan prefix
        if (strpos($uri, 'http') !== 0) {
            $uri = 'https://api.edamam.com/api/recipes/v2/' . $uri;
        }
        $url = $uri . '?' . http_build_query($params);
        $response = $this->curlRequest($url);
        if ($response === false) {
            return ['error' => 'Gagal mengambil detail resep dari Edamam API.'];
        }
        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['recipe'])) {
            return ['error' => 'Data detail resep tidak valid dari Edamam API.'];
        }
        return $data['recipe'];
    }

    /**
     * Analisis nutrisi makanan dari Edamam Nutrition Analysis API
     * @param array $ingredients Daftar ingredientLines (string[])
     * @return array
     */
    public function analyzeNutrition(array $ingredients): array
    {
        $url = 'https://api.edamam.com/api/nutrition-details?app_id=' . $this->appId . '&app_key=' . $this->appKey;
        $body = json_encode(['ingr' => $ingredients]);
        $response = $this->curlRequest($url, 'POST', $body);
        if ($response === false) {
            return ['error' => 'Gagal terhubung ke Edamam Nutrition Analysis API.'];
        }
        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['calories'])) {
            return ['error' => 'Data analisis nutrisi tidak valid dari Edamam API.'];
        }
        return $data;
    }

    /**
     * Helper untuk request ke Edamam API dengan header user
     */
    private function curlRequest(string $url, string $method = 'GET', $body = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Untuk dev/local
        $headers = [];
        if ($this->user) {
            $headers[] = 'Edamam-Account-User: ' . $this->user;
        }
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            $headers[] = 'Content-Type: application/json';
        }
        if ($headers) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
