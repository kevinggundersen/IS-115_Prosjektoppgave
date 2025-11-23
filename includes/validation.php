<?php
/**
 * Input Validation and Sanitization Functions
 * 
 * This file contains reusable validation and sanitization functions
 * for secure handling of user input across the application.
 */

/**
 * Sanitize input data to prevent XSS and other security issues
 * 
 * @param mixed $input The input to sanitize
 * @param int $maxLength Maximum allowed length
 * @param bool $allowHtml Whether to allow basic HTML tags
 * @return mixed Sanitized input
 */
function sanitizeInput($input, $maxLength = 1000, $allowHtml = false) {
    if (is_array($input)) {
        return array_map(function($item) use ($maxLength, $allowHtml) {
            return sanitizeInput($item, $maxLength, $allowHtml);
        }, $input);
    }
    
    if (!is_string($input)) {
        return $input;
    }
    
    // Trim whitespace
    $input = trim($input);
    
    // Limit length
    if (strlen($input) > $maxLength) {
        $input = substr($input, 0, $maxLength);
    }
    
    // Remove null bytes and control characters (except newlines and tabs)
    $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $input);
    
    if ($allowHtml) {
        // Allow basic HTML but sanitize dangerous tags
        $input = strip_tags($input, '<p><br><strong><em><ul><ol><li>');
    } else {
        // Escape HTML entities for display
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    
    return $input;
}

/**
 * Validate numeric input
 * 
 * @param mixed $input The input to validate
 * @param float $min Minimum allowed value
 * @param float $max Maximum allowed value
 * @return mixed Validated numeric value or empty string if invalid
 */
function validateNumericInput($input, $min = 0, $max = 999999) {
    if (empty($input) || $input === 'Ikke spesifisert') {
        return '';
    }
    
    // Remove any non-numeric characters except decimal point
    $cleaned = preg_replace('/[^0-9.]/', '', $input);
    
    if (!is_numeric($cleaned)) {
        return '';
    }
    
    $value = floatval($cleaned);
    
    if ($value < $min || $value > $max) {
        return '';
    }
    
    return $value;
}

/**
 * Validate and sanitize diet type
 * 
 * @param string $dietType The diet type to validate
 * @param string $dietTypeOther Custom diet type if "annet" is selected
 * @return string Validated diet type
 */
function validateDietType($dietType, $dietTypeOther) {
    $allowedDietTypes = [
        'Ingen spesielle krav',
        'Vegetarisk',
        'Vegansk',
        'Glutenfri',
        'Laktosefri',
        'Keto',
        'Paleo',
        'Lavkarbo',
        'annet'
    ];
    
    if (in_array($dietType, $allowedDietTypes)) {
        if ($dietType === 'annet' && !empty($dietTypeOther)) {
            return sanitizeInput($dietTypeOther, 100);
        }
        return $dietType;
    }
    
    return 'Ingen spesielle krav';
}

/**
 * Validate and sanitize allergies
 * 
 * @param array $allergies Array of allergies to validate
 * @param string $allergiesOther Custom allergies if "annet" is selected
 * @return array Validated allergies array
 */
function validateAllergies($allergies, $allergiesOther) {
    $allowedAllergies = [
        'Nøtter',
        'Melk',
        'Egg',
        'Fisk',
        'Skalldyr',
        'Soya',
        'Gluten',
        'Sesam',
        'annet'
    ];
    
    if (!is_array($allergies)) {
        return [];
    }
    
    $validAllergies = [];
    foreach ($allergies as $allergy) {
        if (in_array($allergy, $allowedAllergies)) {
            if ($allergy === 'annet' && !empty($allergiesOther)) {
                $validAllergies[] = sanitizeInput($allergiesOther, 100);
            } elseif ($allergy !== 'annet') {
                $validAllergies[] = $allergy;
            }
        }
    }
    
    return $validAllergies;
}

/**
 * Validate message content for security and spam prevention
 * 
 * @param string $message The message to validate
 * @param int $maxLength Maximum allowed message length
 * @return array Array with 'valid' boolean and 'error' string if invalid
 */
function validateMessageContent($message, $maxLength = 2000) {
    // Check if message is empty
    if (empty(trim($message))) {
        return ['valid' => false, 'error' => 'Meldingen kan ikke være tom'];
    }
    
    // Check for excessive repetition (potential spam)
    $words = explode(' ', $message);
    if (count($words) > 3) {
        $wordCounts = array_count_values($words);
        foreach ($wordCounts as $word => $count) {
            if ($count > count($words) * 0.5) { // If any word appears more than 50% of the time
                return ['valid' => false, 'error' => 'Meldingen inneholder for mye repetisjon'];
            }
        }
    }
    
    // Check for excessive special characters (potential obfuscation)
    $specialCharCount = preg_match_all('/[^a-zA-Z0-9\sæøåÆØÅ.,!?()-]/', $message);
    if ($specialCharCount > strlen($message) * 0.3) { // More than 30% special characters
        return ['valid' => false, 'error' => 'Meldingen inneholder for mange spesialtegn'];
    }
    
    // Check for suspicious patterns that might indicate injection attempts
    $suspiciousPatterns = [
        '/<script[^>]*>.*?<\/script>/i',
        '/javascript:/i',
        '/on\w+\s*=/i',
        '/<iframe[^>]*>.*?<\/iframe>/i',
        '/<object[^>]*>.*?<\/object>/i',
        '/<embed[^>]*>/i',
        '/<link[^>]*>/i',
        '/<meta[^>]*>/i'
    ];
    
    foreach ($suspiciousPatterns as $pattern) {
        if (preg_match($pattern, $message)) {
            return ['valid' => false, 'error' => 'Meldingen inneholder ikke-tillatt innhold'];
        }
    }
    
    return ['valid' => true, 'error' => null];
}

/**
 * Validate instruction type to prevent path traversal
 * 
 * @param string $instructionType The instruction type to validate
 * @return string Valid instruction type
 */
function validateInstructionType($instructionType) {
    $allowedInstructionTypes = ['default', 'mealplanner', 'tutor', 'casual', 'debugger'];
    
    if (in_array($instructionType, $allowedInstructionTypes)) {
        return $instructionType;
    }
    
    return 'default';
}

/**
 * Sanitize and validate form data for meal preferences
 * 
 * @param array $postData The POST data to validate
 * @return array Array with sanitized data and any validation errors
 */
function validateMealPreferencesData($postData) {
    $errors = [];
    $data = [];
    
    // Validate required budget field
    if (!isset($postData['budget']) || empty(trim($postData['budget']))) {
        $errors[] = 'Budsjett er påkrevd';
    }
    
    // Sanitize and validate all fields
    $data['dietType'] = validateDietType(
        sanitizeInput($postData['dietType'] ?? 'Ingen spesielle krav', 50),
        sanitizeInput($postData['dietTypeOther'] ?? '', 100)
    );
    
    $data['allergies'] = validateAllergies(
        $postData['allergies'] ?? [],
        sanitizeInput($postData['allergiesOther'] ?? '', 100)
    );
    
    $data['likes'] = sanitizeInput($postData['likes'] ?? 'Ikke spesifisert', 500);
    $data['dislikes'] = sanitizeInput($postData['dislikes'] ?? 'Ikke spesifisert', 500);
    $data['budget'] = sanitizeInput(trim($postData['budget'] ?? ''), 100);
    $data['equipment'] = sanitizeInput($postData['equipment'] ?? 'Ikke spesifisert', 200);
    $data['cookTime'] = sanitizeInput($postData['cookTime'] ?? 'Ikke spesifisert', 100);
    $data['mealsPerDay'] = sanitizeInput($postData['mealsPerDay'] ?? 'Ikke spesifisert', 50);
    $data['peopleAmount'] = sanitizeInput($postData['peopleAmount'] ?? 'Ikke spesifisert', 50);
    
    // Nutritional constraints - validate as numeric
    $data['maxCaloriesPerMeal'] = validateNumericInput($postData['maxCaloriesPerMeal'] ?? '', 0, 10000);
    $data['maxCaloriesPerDay'] = validateNumericInput($postData['maxCaloriesPerDay'] ?? '', 0, 50000);
    $data['proteinGoal'] = validateNumericInput($postData['proteinGoal'] ?? '', 0, 1000);
    
    // Additional validation for budget
    if (empty($data['budget']) || $data['budget'] === 'Ikke spesifisert') {
        $errors[] = 'Budsjett er påkrevd og må spesifiseres';
    }
    
    return [
        'data' => $data,
        'errors' => $errors
    ];
}
?>
