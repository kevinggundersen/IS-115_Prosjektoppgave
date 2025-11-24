<?php

/**
 * Norwegian Food Database API Service
 * 
 * This class provides methods to fetch and process nutritional data
 * from the Norwegian Food Database (Matvaretabellen) API (https://www.matvaretabellen.no/api/).
 */
class NutritionalDataService {
    private $apiUrl = "https://www.matvaretabellen.no/api/nb/foods.json";
    private $foodGroupsUrl = "https://www.matvaretabellen.no/api/nb/food-groups.json";
    private $cacheFile = "cache/nutritional_data.json";
    private $foodGroupsCacheFile = "cache/food_groups.json";
    private $cacheExpiry = 31536000; // 1 year in seconds, as the API is updated every year
    
    /**
     * Get all foods from the API with caching
     */
    public function getAllFoods() {
        // Check if we have valid cached data
        if ($this->isCacheValid()) {
            return $this->getCachedData();
        }
        
        // Fetch fresh data from API
        $data = $this->fetchFromAPI();
        if ($data) {
            $this->saveToCache($data);
            return $data;
        }
        
        return null;
    }
    
    /**
     * Search for foods by name
     */
    public function searchFoods($searchTerm) {
        $allFoods = $this->getAllFoods();
        if (!$allFoods || !isset($allFoods['foods'])) {
            return [];
        }
        
        $searchTerm = strtolower($searchTerm);
        $results = [];
        
        foreach ($allFoods['foods'] as $food) {
            if (strpos(strtolower($food['foodName']), $searchTerm) !== false) {
                $results[] = $this->formatFoodData($food);
            }
        }
        
        return $results;
    }
    
    /**
     * Get nutritional data for a specific food
     */
    public function getFoodNutrition($foodId) {
        $allFoods = $this->getAllFoods();
        if (!$allFoods || !isset($allFoods['foods'])) {
            return null;
        }
        
        foreach ($allFoods['foods'] as $food) {
            if ($food['foodId'] == $foodId) {
                return $this->formatFoodData($food);
            }
        }
        
        return null;
    }
    
    /**
     * Get foods within a specific calorie range
     */
    public function getFoodsByCalorieRange($minCalories = 0, $maxCalories = 1000) {
        $allFoods = $this->getAllFoods();
        if (!$allFoods || !isset($allFoods['foods'])) {
            return [];
        }
        
        $results = [];
        foreach ($allFoods['foods'] as $food) {
            $calories = $food['calories']['quantity'] ?? 0;
            if ($calories >= $minCalories && $calories <= $maxCalories) {
                $results[] = $this->formatFoodData($food);
            }
        }
        
        return $results;
    }
    
    /**
     * Format food data for consistent output
     */
    public function formatFoodData($food) {
        $nutrition = [
            'calories' => 0,
            'fat' => 0,
            'protein' => 0,
            'carbs' => 0,
            'fiber' => 0,
            'sugar' => 0
        ];
        
        // Extract calories
        if (isset($food['calories']['quantity'])) {
            $nutrition['calories'] = (float)$food['calories']['quantity'];
        }
        
        // Extract other nutrients
            if (isset($food['constituents']) && is_array($food['constituents'])) {
                foreach ($food['constituents'] as $nutrient) {
                    $id = strtolower($nutrient['nutrientId'] ?? '');
                $quantity = (float)($nutrient['quantity'] ?? 0);
                
                switch ($id) {
                    case 'fett':
                        $nutrition['fat'] = $quantity;
                        break;
                    case 'protein':
                        $nutrition['protein'] = $quantity;
                        break;
                    case 'karbo':
                        $nutrition['carbs'] = $quantity;
                        break;
                    case 'fiber':
                        $nutrition['fiber'] = $quantity;
                        break;
                    case 'sukker':
                        $nutrition['sugar'] = $quantity;
                        break;
                }
            }
        }
        
        return [
            'id' => $food['foodId'],
            'name' => $food['foodName'],
            'group_id' => $food['foodGroupId'],
            'uri' => $food['uri'],
            'nutrition' => $nutrition
        ];
    }
    
    /**
     * Fetch data from the API
     */
    private function fetchFromAPI() {
        $json = @file_get_contents($this->apiUrl);
        if ($json === false) {
            error_log("Error fetching data from Norwegian Food Database API");
            return null;
        }
        
        $data = json_decode($json, true);
        if ($data === null) {
            error_log("Error decoding JSON from Norwegian Food Database API");
            return null;
        }
        
        return $data;
    }
    
    /**
     * Check if cached data is still valid
     */
    private function isCacheValid() {
        if (!file_exists($this->cacheFile)) {
            return false;
        }
        
        $cacheTime = filemtime($this->cacheFile);
        return (time() - $cacheTime) < $this->cacheExpiry;
    }
    
    /**
     * Get data from cache
     */
    private function getCachedData() {
        $json = file_get_contents($this->cacheFile);
        return json_decode($json, true);
    }
    
    /**
     * Save data to cache
     */
    private function saveToCache($data) {
        // Create cache directory if it doesn't exist
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($this->cacheFile, json_encode($data));
    }
    
    /**
     * Get food groups from the API with caching
     */
    public function getFoodGroups() {
        // Check if we have valid cached data
        if ($this->isFoodGroupsCacheValid()) {
            return $this->getCachedFoodGroups();
        }
        
        // Fetch fresh data from API
        $data = $this->fetchFoodGroupsFromAPI();
        if ($data) {
            $this->saveFoodGroupsToCache($data);
            return $data;
        }
        
        return null;
    }
    
    /**
     * Get food group name by ID
     */
    public function getFoodGroupName($groupId) {
        $foodGroups = $this->getFoodGroups();
        if (!$foodGroups || !isset($foodGroups['foodGroups'])) {
            return 'Unknown';
        }
        
        foreach ($foodGroups['foodGroups'] as $group) {
            if ($group['foodGroupId'] == $groupId) {
                return $group['name'];
            }
        }
        
        return 'Unknown';
    }
    
    /**
     * Get parent food group name for subcategories
     */
    private function getParentFoodGroupName($groupId) {
        $foodGroups = $this->getFoodGroups();
        if (!$foodGroups || !isset($foodGroups['foodGroups'])) {
            return 'Unknown';
        }
        
        // Find the group
        $currentGroup = null;
        foreach ($foodGroups['foodGroups'] as $group) {
            if ($group['foodGroupId'] == $groupId) {
                $currentGroup = $group;
                break;
            }
        }
        
        if (!$currentGroup || !isset($currentGroup['parentId'])) {
            return $this->getFoodGroupName($groupId);
        }
        
        // Find parent group
        foreach ($foodGroups['foodGroups'] as $group) {
            if ($group['foodGroupId'] == $currentGroup['parentId']) {
                return $group['name'];
            }
        }
        
        return $this->getFoodGroupName($groupId);
    }
    
    /**
     * Categorize foods by their official food group IDs
     */
    public function categorizeFoodsByGroup($filteredFoods) {
        $categories = [];
        
        foreach ($filteredFoods as $food) {
            $groupId = $food['group_id'];
            $parentGroupName = $this->getParentFoodGroupName($groupId);
            
            // Use the original Norwegian group name as category
            if (!isset($categories[$parentGroupName])) {
                $categories[$parentGroupName] = [];
            }
            $categories[$parentGroupName][] = $food;
        }
        
        // Sort categories alphabetically for consistent ordering
        ksort($categories);
        
        return $categories;
    }
    
    
    /**
     * Fetch food groups from the API
     */
    private function fetchFoodGroupsFromAPI() {
        $json = @file_get_contents($this->foodGroupsUrl);
        if ($json === false) {
            error_log("Error fetching food groups from Norwegian Food Database API");
            return null;
        }
        
        $data = json_decode($json, true);
        if ($data === null) {
            error_log("Error decoding food groups JSON from Norwegian Food Database API");
            return null;
        }
        
        return $data;
    }
    
    /**
     * Check if food groups cache is still valid
     */
    private function isFoodGroupsCacheValid() {
        if (!file_exists($this->foodGroupsCacheFile)) {
            return false;
        }
        
        $cacheTime = filemtime($this->foodGroupsCacheFile);
        return (time() - $cacheTime) < $this->cacheExpiry;
    }
    
    /**
     * Get food groups data from cache
     */
    private function getCachedFoodGroups() {
        $json = file_get_contents($this->foodGroupsCacheFile);
        return json_decode($json, true);
    }
    
    /**
     * Save food groups data to cache
     */
    private function saveFoodGroupsToCache($data) {
        // Create cache directory if it doesn't exist
        $cacheDir = dirname($this->foodGroupsCacheFile);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        file_put_contents($this->foodGroupsCacheFile, json_encode($data));
    }
}
?>