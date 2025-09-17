<?php
require_once 'vendor/autoload.php';

use Gemini\Enums\ModelVariation;
use Gemini\GeminiHelper;
use Gemini\Factory;
use Gemini\Data\Content;
use Gemini\Enums\Role;
use Dotenv\Dotenv;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$yourApiKey = $_ENV['GEMINI_API_KEY'];
$client = (new Factory())->withApiKey($yourApiKey)->make();

// Start session
session_start();

// Initialize variables
$userInput = '';
$chatHistory = [];
$lastResponse = '';

// Check if form was submitted
if ($_POST && isset($_POST['name']) && !empty(trim($_POST['name']))) {
    $userInput = trim($_POST['name']);
    
    // Initialize chat history if not exists
    if (!isset($_SESSION['chat_history'])) {
        $_SESSION['chat_history'] = [];
    }
    
    // Add user message to history first
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $userInput];
    
    // Convert session history to Content objects for the API
    $history = [];
    foreach ($_SESSION['chat_history'] as $message) {
        $role = $message['role'] === 'user' ? Role::USER : Role::MODEL;
        $history[] = Content::parse(part: $message['content'], role: $role);
    }
    
    // Create a new chat object with existing history (don't store in session)
    $chat = $client
        ->generativeModel(model: 'gemini-2.0-flash')
        ->startChat(history: $history);
    
    // Send message to chat and get response
    $result = $chat->sendMessage($userInput);
    $response = $result->text();
    
    // Add model response to history
    $_SESSION['chat_history'][] = ['role' => 'model', 'content' => $response];
    
    // Store the last response for display
    $lastResponse = $response;
}

// Get chat history for display
if (isset($_SESSION['chat_history'])) {
    $chatHistory = $_SESSION['chat_history'];
}

// Debug: Let's see what's in the session
// echo "<pre>Session data: "; print_r($_SESSION); echo "</pre>";

// Handle clear chat
if (isset($_POST['clear_chat'])) {
    unset($_SESSION['chat_history']);
    $chatHistory = [];
    $lastResponse = '';
}
?>

<DOCTYPE html>
    <html lang="no">

    <head>
        <title>IS-115 - Prosjektoppgave</title>
    </head>

    <body>
        <h1>IS-115 - Prosjektoppgave - Multi-turn Chat</h1>
        
        <?php if (!empty($chatHistory)): ?>
            <div>
                <h3>Conversation History:</h3>
                <?php foreach ($chatHistory as $index => $message): ?>
                    <div>
                        <strong><?php echo $message['role'] === 'user' ? 'You' : 'AI'; ?>:</strong><br>
                        <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>Start a conversation by entering a message below.</p>
        <?php endif; ?>
        
        <form action="index.php" method="post">
            <input type='text' name="name" placeholder='Enter your message here...' required>
            <button type="submit">Send</button>
            <?php if (!empty($chatHistory)): ?>
                <button type="submit" name="clear_chat">Clear Chat</button>
            <?php endif; ?>
        </form>
    </body>

    </html>