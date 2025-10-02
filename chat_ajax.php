<?php
/**
 * AJAX Endpoint for Chat Application
 * 
 * This file handles AJAX requests for the chat functionality.
 * It processes user messages and returns JSON responses with the AI's reply.
 */

// Include Composer's autoloader
require_once 'vendor/autoload.php';

// Import necessary classes
use Gemini\Enums\ModelVariation;
use Gemini\GeminiHelper;
use Gemini\Factory;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use Dotenv\Dotenv;

// Set content type to JSON for AJAX responses
header('Content-Type: application/json');

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Get API key
$yourApiKey = $_ENV['GEMINI_API_KEY'];

// Create Gemini client
$client = (new Factory())->withApiKey($yourApiKey)->make();

// Start session
session_start();

// Initialize Parsedown for markdown parsing
$parsedown = new \Parsedown();

// Function to send JSON response
function sendResponse($success, $data = null, $error = null) {
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'error' => $error
    ]);
    exit();
}

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, null, 'Only POST requests are allowed');
}

// Get the action from POST data
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'send_message':
        handleSendMessage($client, $parsedown);
        break;
    case 'clear_chat':
        handleClearChat();
        break;
    case 'get_chat_history':
        handleGetChatHistory($parsedown);
        break;
    default:
        sendResponse(false, null, 'Invalid action');
}

/**
 * Handle sending a message to the AI
 */
function handleSendMessage($client, $parsedown) {
    // Validate input
    if (!isset($_POST['message']) || empty(trim($_POST['message']))) {
        sendResponse(false, null, 'Message is required');
    }
    
    $userInput = trim($_POST['message']);
    
    // Initialize chat history if it doesn't exist
    if (!isset($_SESSION['chat_history'])) {
        $_SESSION['chat_history'] = [];
    }
    
    // Add user message to history
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $userInput];
    
    // Convert session history to API format
    $history = [];
    foreach ($_SESSION['chat_history'] as $message) {
        $role = $message['role'] === 'user' ? Role::USER : Role::MODEL;
        $history[] = Content::parse(part: $message['content'], role: $role);
    }
    
    // Set instruction type (same as main file)
    $instructionType = 'mealplanner';
    
    // Load system instructions
    $configFile = __DIR__ . "/config/instructions_{$instructionType}.txt";
    if (!file_exists($configFile)) {
        $configFile = __DIR__ . "/config/instructions_default.txt";
    }
    $systemInstructions = file_get_contents($configFile);
    
    // Create chat session
    $chat = $client
        ->generativeModel(model: 'gemini-2.0-flash')
        ->withSystemInstruction(Content::parse(part: $systemInstructions))
        ->startChat(history: $history);
    
    // Send message and get response
    try {
        $result = $chat->sendMessage($userInput);
        $response = $result->text();
    } catch (Exception $e) {
        sendResponse(false, null, "Error processing request: " . $e->getMessage());
    }
    
    // Add AI response to history
    $_SESSION['chat_history'][] = ['role' => 'model', 'content' => $response];
    
    // Return the new messages (user message and AI response)
    $newMessages = [
        [
            'role' => 'user',
            'content' => $userInput,
            'formatted_content' => nl2br(htmlspecialchars($userInput))
        ],
        [
            'role' => 'model',
            'content' => $response,
            'formatted_content' => $parsedown->text($response)
        ]
    ];
    
    sendResponse(true, $newMessages);
}

/**
 * Handle clearing the chat history
 */
function handleClearChat() {
    unset($_SESSION['chat_history']);
    sendResponse(true, 'Chat cleared successfully');
}

/**
 * Handle getting the current chat history
 */
function handleGetChatHistory($parsedown) {
    $chatHistory = $_SESSION['chat_history'] ?? [];
    
    // Format the chat history for display
    $formattedHistory = [];
    foreach ($chatHistory as $message) {
        $formattedHistory[] = [
            'role' => $message['role'],
            'content' => $message['content'],
            'formatted_content' => $message['role'] === 'model' 
                ? $parsedown->text($message['content'])
                : nl2br(htmlspecialchars($message['content']))
        ];
    }
    
    sendResponse(true, $formattedHistory);
}
?>
