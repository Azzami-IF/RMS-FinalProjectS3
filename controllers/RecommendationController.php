<?php
require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../classes/Cache.php';
require_once __DIR__ . '/../classes/EdamamService.php';

class RecommendationController {
    private EdamamService $edamam;
    public function __construct(EdamamService $edamam) {
        $this->edamam = $edamam;
    }

    private function expandEdamamQuery(string $search): string
    {
        $s = trim($search);
        if ($s === '') return 'healthy';

        $translated = $this->translateFoodKeywordToEnglish($s);
        $translated = trim($translated);

        if ($translated === '' || strcasecmp($translated, $s) === 0) {
            return $s;
        }

        // Avoid duplicating the same words.
        if (stripos($s, $translated) !== false) {
            return $s;
        }

        // Append translation instead of doing a second API call.
        return $s . ' ' . $translated;
    }

    private function translateFoodKeywordToEnglish(string $text): string
    {
        // Lightweight dictionary-based translation (no external API).
        // This helps Edamam understand common Indonesian keywords.
        $raw = trim($text);
        if ($raw === '') return '';

        $lower = strtolower($raw);

        // Phrase-level replacements (longer first)
        $phrases = [
            'daging sapi' => 'beef',
            'dada ayam' => 'chicken breast',
            'nasi goreng' => 'fried rice',
            'mie goreng' => 'fried noodles',
            'mi goreng' => 'fried noodles',
            'sate ayam' => 'chicken satay',
            'sate sapi' => 'beef satay',
            'ikan asin' => 'salted fish',
            'ikan tuna' => 'tuna',
            'ikan salmon' => 'salmon',
            'susu rendah lemak' => 'low fat milk',
            'roti gandum' => 'whole wheat bread',
            'tepung terigu' => 'wheat flour',
            'minyak zaitun' => 'olive oil',
        ];
        foreach ($phrases as $id => $en) {
            if (str_contains($lower, $id)) {
                $lower = str_replace($id, $en, $lower);
            }
        }

        // Token-level replacements
        $words = [
            'ayam' => 'chicken',
            'sapi' => 'beef',
            'daging' => 'meat',
            'ikan' => 'fish',
            'udang' => 'shrimp',
            'kepiting' => 'crab',
            'cumi' => 'squid',
            'telur' => 'egg',
            'putih' => 'white',
            'kuning' => 'yolk',
            'nasi' => 'rice',
            'beras' => 'rice',
            'mie' => 'noodles',
            'mi' => 'noodles',
            'roti' => 'bread',
            'kentang' => 'potato',
            'ubi' => 'sweet potato',
            'sayur' => 'vegetables',
            'buah' => 'fruit',
            'apel' => 'apple',
            'pisang' => 'banana',
            'jeruk' => 'orange',
            'mangga' => 'mango',
            'alpukat' => 'avocado',
            'nanas' => 'pineapple',
            'semangka' => 'watermelon',
            'melon' => 'melon',
            'stroberi' => 'strawberry',
            'anggur' => 'grapes',
            'tomat' => 'tomato',
            'bawang' => 'onion',
            'bawang putih' => 'garlic',
            'bawang merah' => 'shallot',
            'cabai' => 'chili',
            'cabe' => 'chili',
            'garam' => 'salt',
            'gula' => 'sugar',
            'madu' => 'honey',
            'susu' => 'milk',
            'yogurt' => 'yogurt',
            'keju' => 'cheese',
            'mentega' => 'butter',
            'minyak' => 'oil',
            'zaitun' => 'olive',
            'kacang' => 'peanut',
            'kedelai' => 'soy',
            'tahu' => 'tofu',
            'tempe' => 'tempeh',
            'oat' => 'oats',
            'oatmeal' => 'oatmeal',
            'gandum' => 'wheat',
            'brokoli' => 'broccoli',
            'wortel' => 'carrot',
            'bayam' => 'spinach',
            'kangkung' => 'water spinach',
        ];

        $normalized = preg_replace('/[^a-z0-9\s-]+/i', ' ', $lower);
        $normalized = preg_replace('/\s+/', ' ', trim((string)$normalized));
        if ($normalized === '') return '';

        $tokens = explode(' ', $normalized);
        $out = [];
        foreach ($tokens as $tok) {
            $t = trim($tok);
            if ($t === '') continue;
            if (isset($words[$t])) {
                $out[] = $words[$t];
            }
        }

        // If no dictionary hits, return empty to keep original query untouched.
        if (!$out) return '';

        // Deduplicate while preserving order.
        $seen = [];
        $unique = [];
        foreach ($out as $w) {
            $k = strtolower($w);
            if (isset($seen[$k])) continue;
            $seen[$k] = true;
            $unique[] = $w;
        }
        return implode(' ', $unique);
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
        // If search is empty, show a healthy default recommendation list.
        // If user types Indonesian/local terms, append English synonyms to improve Edamam recall.
        $query = $this->expandEdamamQuery($search);
        $data = $this->edamam->searchRecipes($query, $calories, $options);
        if (isset($data['error'])) return ['error' => $data['error']];
        return $data;
    }
}
