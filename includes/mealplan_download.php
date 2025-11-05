<?php
/**
 * Meal Plan Download Functions
 * 
 * This file contains functions for generating PDF meal plan files
 * from chat history using markdown-to-HTML-to-PDF conversion.
 */

require_once __DIR__ . '/../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Extract the latest mealplan from chat history
 * 
 * Searches backwards through chat history to find the last complete mealplan.
 * Only extracts content from the last mealplan generation, excluding:
 * - Previous mealplan versions
 * - General questions and answers after mealplan generation
 * 
 * Strategy:
 * 1. Find the last message containing "Måltidsplan" (marks the start of a mealplan)
 * 2. Collect that message and all model messages until a user message
 * 
 * @param array $chatHistory Array of chat messages with 'role' and 'content' keys
 * @return string The markdown content of the latest mealplan, or empty string if none found
 */
function extractLatestMealPlan($chatHistory) {
    $mealplanSections = ['Måltidsplan', 'Handleliste', 'Oppskrifter', 'Tips og Triks'];
    
    // Find the index of the last message that contains "Måltidsplan" AND at least one other mealplan section
    $lastMealPlanIndex = -1;
    for ($i = count($chatHistory) - 1; $i >= 0; $i--) {
        $message = $chatHistory[$i];
        if ($message['role'] === 'model') {
            $content = $message['content'] ?? '';
            
            // Check if this message contains "Måltidsplan"
            if (stripos($content, 'Måltidsplan') !== false) {
                // Count how many mealplan sections this message contains
                $sectionCount = 0;
                foreach ($mealplanSections as $section) {
                    if (stripos($content, $section) !== false) {
                        $sectionCount++;
                    }
                }
                
                // Only consider it a mealplan if it has "Måltidsplan" AND at least one other section
                // This filters out general Q&A that just mentions "Måltidsplan"
                if ($sectionCount >= 2) {
                    $lastMealPlanIndex = $i;
                    break;
                }
            }
        }
    }
    
    // If no mealplan found, return empty string
    if ($lastMealPlanIndex === -1) {
        return '';
    }
    
    // Collect all model messages from the mealplan start until we hit a user message
    $mealPlanContent = [];
    
    // Collect all model messages until we hit a user message
    for ($i = $lastMealPlanIndex; $i < count($chatHistory); $i++) {
        $message = $chatHistory[$i];
        
        // Stop if hitting a user message
        if ($message['role'] === 'user') {
            break;
        }
        
        // Collect all model messages
        if ($message['role'] === 'model') {
            $mealPlanContent[] = $message['content'] ?? '';
        }
    }
    

    
    // Combine all collected messages into a single markdown string
    return implode("\n\n", $mealPlanContent);
}

/**
 * Generate meal plan PDF from chat history
 * 
 * 1. Extract only the latest mealplan content
 * 2. Convert it to HTML using Parsedown
 * 3. Convert HTML to PDF using DomPDF
 * 
 * @param array $chatHistory Array of chat messages with 'role' and 'content' keys
 * @return Dompdf PDF object ready to output
 */
function generateMealPlanPDF($chatHistory) {
    // Extract only the latest mealplan (excludes previous versions and general Q&A)
    $markdown = extractLatestMealPlan($chatHistory);
    
    // If no mealplan was found, return null or throw an error
    if (empty($markdown)) {
        throw new Exception('Ingen måltidsplan funnet i samtalehistorikken');
    }

    // Convert markdown to HTML using Parsedown
    $parsedown = new \Parsedown();
    $html = $parsedown->text($markdown);

    // Wrap the HTML with proper structure and styling
    $fullHtml = <<<HTML
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <title>Måltidsplan</title>
    <style>
        @page {
            margin: 20mm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #333;
        }
        h1 {
            color: #0078d4;
            font-size: 18pt;
            margin-top: 20pt;
            margin-bottom: 10pt;
            border-bottom: 2px solid #0078d4;
            padding-bottom: 5pt;
        }
        h2 {
            color: #0078d4;
            font-size: 14pt;
            margin-top: 15pt;
            margin-bottom: 8pt;
        }
        h3 {
            font-size: 12pt;
            margin-top: 12pt;
            margin-bottom: 6pt;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10pt 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8pt;
            text-align: left;
        }
        th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        code {
            background-color: #f5f5f5;
            padding: 2pt 4pt;
            border-radius: 3pt;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10pt;
            border-radius: 5pt;
            overflow-x: auto;
        }
        ul, ol {
            margin: 8pt 0;
            padding-left: 20pt;
        }
        li {
            margin: 4pt 0;
        }
        p {
            margin: 8pt 0;
        }
    </style>
</head>
<body>
    {$html}
</body>
</html>
HTML;

    // Configure DomPDF options
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('defaultFont', 'DejaVu Sans');

    // Create DomPDF instance
    $dompdf = new Dompdf($options);
    
    // Set document information
    $dompdf->setBasePath(__DIR__);
    $dompdf->loadHtml($fullHtml, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    
    // Render PDF
    $dompdf->render();

    return $dompdf;
}
?>
