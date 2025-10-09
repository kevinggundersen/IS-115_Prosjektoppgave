<?php
/**
 * AJAX Endpoint for Chat Application
 * 
 * This file handles AJAX requests for the chat functionality.
 * It processes user messages and returns JSON responses with the AI's reply.
 */

// Set instruction type
$instructionType = 'mealplanner';

// Include Composer's autoloader
require_once 'vendor/autoload.php';

// Include session management functions
require_once 'includes/session_functions.php';

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
        handleSendMessage($client, $parsedown, $instructionType);
        break;
    case 'send_meal_preferences':
        handleSendMealPreferences($client, $parsedown, $instructionType);
        break;
    case 'clear_chat':
        handleClearChat();
        break;
    case 'get_chat_history':
        handleGetChatHistory($parsedown);
        break;
    case 'create_new_session':
        handleCreateNewSession();
        break;
    case 'load_session':
        handleLoadSession($parsedown);
        break;
    case 'get_sessions':
        handleGetSessions();
        break;
    case 'delete_session':
        handleDeleteSession();
        break;
    default:
        sendResponse(false, null, 'Invalid action');
}

/**
 * Handle sending a message to the AI
 */
function handleSendMessage($client, $parsedown, $instructionType) {
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
    
    // Update current session if it exists
    if (isset($_SESSION['current_session_id']) && isset($_SESSION['sessions'][$_SESSION['current_session_id']])) {
        $_SESSION['sessions'][$_SESSION['current_session_id']]['messages'] = $_SESSION['chat_history'];
        $_SESSION['sessions'][$_SESSION['current_session_id']]['updated_at'] = date('Y-m-d H:i:s');
        
        // Update session title based on first user message if it's still "New Chat"
        if ($_SESSION['sessions'][$_SESSION['current_session_id']]['title'] === 'New Chat') {
            $firstUserMessage = '';
            foreach ($_SESSION['chat_history'] as $message) {
                if ($message['role'] === 'user') {
                    $firstUserMessage = $message['content'];
                    break;
                }
            }
            if (!empty($firstUserMessage)) {
                // Truncate title to 50 characters
                $title = strlen($firstUserMessage) > 50 ? substr($firstUserMessage, 0, 50) . '...' : $firstUserMessage;
                $_SESSION['sessions'][$_SESSION['current_session_id']]['title'] = $title;
            }
        }
    } else {
        // If no current session exists, create one
        if (!isset($_SESSION['sessions'])) {
            $_SESSION['sessions'] = [];
        }
        
        $sessionId = uniqid('session_', true);
        $sessionTitle = 'New Chat';
        
        // Update title if we have messages
        if (!empty($_SESSION['chat_history'])) {
            foreach ($_SESSION['chat_history'] as $message) {
                if ($message['role'] === 'user') {
                    $sessionTitle = strlen($message['content']) > 50 ? substr($message['content'], 0, 50) . '...' : $message['content'];
                    break;
                }
            }
        }
        
        $_SESSION['sessions'][$sessionId] = [
            'id' => $sessionId,
            'title' => $sessionTitle,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'messages' => $_SESSION['chat_history']
        ];
        
        $_SESSION['current_session_id'] = $sessionId;
    }
    
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
 * Handle sending meal preferences to the AI
 */
function handleSendMealPreferences($client, $parsedown, $instructionType) {
    // Validate required fields
    if (!isset($_POST['budget']) || empty(trim($_POST['budget']))) {
        sendResponse(false, null, 'Budget is required');
    }
    
    // Collect form data
    $dietType = $_POST['dietType'] ?? 'Ingen spesielle krav';
    $dietTypeOther = $_POST['dietTypeOther'] ?? '';
    $allergies = $_POST['allergies'] ?? [];
    $allergiesOther = $_POST['allergiesOther'] ?? '';
    $likes = $_POST['likes'] ?? 'Ikke spesifisert';
    $dislikes = $_POST['dislikes'] ?? 'Ikke spesifisert';
    $budget = trim($_POST['budget']);
    $equipment = $_POST['equipment'] ?? 'Ikke spesifisert';
    $cookTime = $_POST['cookTime'] ?? 'Ikke spesifisert';
    $mealsPerDay = $_POST['mealsPerDay'] ?? 'Ikke spesifisert';
    $peopleAmount = $_POST['peopleAmount'] ?? 'Ikke spesifisert';
    
    // Handle custom diet type
    if ($dietType === 'annet' && !empty($dietTypeOther)) {
        $dietType = $dietTypeOther;
    }
    
    // Handle custom allergies
    $allergiesList = $allergies;
    if (in_array('annet', $allergies) && !empty($allergiesOther)) {
        // Remove 'annet' from the array and add the custom allergy
        $allergiesList = array_filter($allergies, function($allergy) {
            return $allergy !== 'annet';
        });
        $allergiesList[] = $allergiesOther;
    }
    
    // Format allergies array
    $allergiesString = !empty($allergiesList) ? implode(', ', $allergiesList) : 'Ingen allergier';
    
    // Generate the formatted message in PHP
    $currentDate = date('Y.m.d');
    $userInput = 
        "
        Dato: {$currentDate}. 
        Diettype: {$dietType}, 
        Allergier: {$allergiesString}, 
        Liker: {$likes}, 
        Liker ikke: {$dislikes}, 
        Budsjett: {$budget}, 
        Ustyr: {$equipment}, 
        Tid til matlaging: {$cookTime}, 
        Antall måltider per dag: {$mealsPerDay},
        Antall personer: {$peopleAmount},
        ";
    
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
    
    // Update current session if it exists
    if (isset($_SESSION['current_session_id']) && isset($_SESSION['sessions'][$_SESSION['current_session_id']])) {
        $_SESSION['sessions'][$_SESSION['current_session_id']]['messages'] = $_SESSION['chat_history'];
        $_SESSION['sessions'][$_SESSION['current_session_id']]['updated_at'] = date('Y-m-d H:i:s');
        
        // Update session title based on first user message if it's still "New Chat"
        if ($_SESSION['sessions'][$_SESSION['current_session_id']]['title'] === 'New Chat') {
            $firstUserMessage = '';
            foreach ($_SESSION['chat_history'] as $message) {
                if ($message['role'] === 'user') {
                    $firstUserMessage = $message['content'];
                    break;
                }
            }
            if (!empty($firstUserMessage)) {
                // Truncate title to 50 characters
                $title = strlen($firstUserMessage) > 50 ? substr($firstUserMessage, 0, 50) . '...' : $firstUserMessage;
                $_SESSION['sessions'][$_SESSION['current_session_id']]['title'] = $title;
            }
        }
    } else {
        // If no current session exists, create one
        if (!isset($_SESSION['sessions'])) {
            $_SESSION['sessions'] = [];
        }
        
        $sessionId = uniqid('session_', true);
        $sessionTitle = 'Meal Preferences';
        
        $_SESSION['sessions'][$sessionId] = [
            'id' => $sessionId,
            'title' => $sessionTitle,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'messages' => $_SESSION['chat_history']
        ];
        
        $_SESSION['current_session_id'] = $sessionId;
    }
    
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

/**
 * Handle creating a new session
 */
function handleCreateNewSession() {
    // Initialize sessions array if it doesn't exist
    initializeSessions();
    
    // Save current session if it exists and has messages
    if (isset($_SESSION['current_session_id']) && isset($_SESSION['sessions'][$_SESSION['current_session_id']])) {
        $_SESSION['sessions'][$_SESSION['current_session_id']]['messages'] = $_SESSION['chat_history'] ?? [];
        $_SESSION['sessions'][$_SESSION['current_session_id']]['updated_at'] = date('Y-m-d H:i:s');
    }
    
    // Create new session with unique ID
    $sessionId = uniqid('session_', true);
    $sessionTitle = 'New Chat';
    
    // Create empty session
    $_SESSION['sessions'][$sessionId] = [
        'id' => $sessionId,
        'title' => $sessionTitle,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'messages' => []
    ];
    
    // Clear current chat history and set new session as current
    $_SESSION['chat_history'] = [];
    $_SESSION['current_session_id'] = $sessionId;
    
    sendResponse(true, ['session_id' => $sessionId, 'title' => $sessionTitle]);
}

/**
 * Handle loading a specific session
 */
function handleLoadSession($parsedown) {
    $sessionId = $_POST['session_id'] ?? '';
    
    if (empty($sessionId)) {
        sendResponse(false, null, 'Session ID is required');
    }
    
    if (!isset($_SESSION['sessions'][$sessionId])) {
        sendResponse(false, null, 'Session not found: ' . $sessionId);
    }
    
    // Load the session's chat history
    $_SESSION['chat_history'] = $_SESSION['sessions'][$sessionId]['messages'];
    $_SESSION['current_session_id'] = $sessionId;
    
    // Format the chat history for display
    $formattedHistory = [];
    if (!empty($_SESSION['chat_history'])) {
        foreach ($_SESSION['chat_history'] as $message) {
            if (isset($message['role']) && isset($message['content'])) {
                $formattedHistory[] = [
                    'role' => $message['role'],
                    'content' => $message['content'],
                    'formatted_content' => $message['role'] === 'model' 
                        ? $parsedown->text($message['content'])
                        : nl2br(htmlspecialchars($message['content']))
                ];
            }
        }
    }
    
    sendResponse(true, [
        'session_id' => $sessionId,
        'title' => $_SESSION['sessions'][$sessionId]['title'],
        'messages' => $formattedHistory
    ]);
}

/**
 * Handle getting all sessions
 */
function handleGetSessions() {
    $sessions = getAllSessions();
    sendResponse(true, $sessions);
}

/**
 * Handle deleting a session
 */
function handleDeleteSession() {
    $sessionId = $_POST['session_id'] ?? '';
    
    if (empty($sessionId) || !isset($_SESSION['sessions'][$sessionId])) {
        sendResponse(false, null, 'Session not found');
    }
    
    // Remove the session
    unset($_SESSION['sessions'][$sessionId]);
    
    sendResponse(true, 'Session deleted successfully');
}

?>
