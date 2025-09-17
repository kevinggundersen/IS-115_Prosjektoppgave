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

    // Redirect to prevent form resubmission on refresh (Post-Redirect-Get pattern)
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
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
    // Redirect to prevent form resubmission on refresh
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="no">

<head>
    <title>IS-115 - Prosjektoppgave</title>
    <style>
        body {

            margin: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
        }

        .chat-area {
            min-height: 200px;
            max-height: 80vh;
            border: 1px solid #ccc;
            padding: 15px;
            margin: 20px 0;
            background-color: #f9f9f9;
            line-height: 1.4;
            font-size: 1.0rem;
            overflow-y: scroll;
        }

        .message {
            margin: 20px 0;
            padding: 10px;
            border-radius: 10px;
        }
        .message[role="user"] {
            background-color:rgb(231, 231, 231);
            text-align: left;
            margin-left: 20vh;
        }
        .message[role="model"] {
            text-align: left;
        }
         
        input[type="text"] {
            width: 70%;
            padding: 10px;
        }

        button {
            padding: 10px 20px;
            margin: 5px;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>IS-115 - Prosjektoppgave </h1>
        <h3>Conversation History:</h3>
        <div class="chat-area">
            <?php if (!empty($chatHistory)): ?>
                <div>
                    
                    <?php foreach ($chatHistory as $index => $message): ?>
                         <div class="message" role="<?php echo $message['role']; ?>">
                            <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p>Start a conversation by entering a message below.</p>
            <?php endif; ?>
            </div>
            <form action="index.php" method="post">
                <input type='text' name="name" placeholder='Enter your message here...' required>
                <button type="submit">Send</button>
                <?php if (!empty($chatHistory)): ?>
                    <button type="submit" name="clear_chat" formnovalidate>Clear Chat</button>
                <?php endif; ?>
            </form>
        </div>
    </body>

    </html>