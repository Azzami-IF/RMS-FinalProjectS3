<?php
class SpoonacularAPI {
    private $apiKey;
    private $baseUrl = 'https://api.spoonacular.com/';

    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }

    public function searchFood($query, $number = 10) {
        $url = $this->baseUrl . 'food/ingredients/search?query=' . urlencode($query) . '&number=' . $number . '&apiKey=' . $this->apiKey;
        $response = file_get_contents($url);
        return json_decode($response, true);
    }

    public function getIngredientInformation($id, $amount = 100, $unit = 'grams') {
        $url = $this->baseUrl . 'food/ingredients/' . $id . '/information?amount=' . $amount . '&unit=' . $unit . '&apiKey=' . $this->apiKey;
        $response = file_get_contents($url);
        return json_decode($response, true);
    }
}
?>