<?php
/**
 * IS-115 Prosjektoppgave - AI Chat Application
 * 
 * This is a PHP web application that creates an interactive chat interface
 * using Google's Gemini AI model. The application allows students to have
 * conversations with an AI assistant that can be configured with different
 * instruction sets (tutor, debugger, casual, or default).
 * 
 * Key Features:
 * - Interactive chat interface with persistent conversation history
 * - Multiple AI personality modes via configuration files
 * - Markdown support for rich text responses
 * - Session-based chat history management
 * - Post-Redirect-Get pattern for form handling
 * - Syntax highlighting for code examples
 * 
 * @author IS-115 Student
 * @version 1.0
 */

// Include Composer's autoloader to load all required dependencies
// This automatically loads all the packages defined in composer.json
require_once 'vendor/autoload.php';

// Import necessary classes from the Google Gemini PHP client library
use Gemini\Enums\ModelVariation;  // For model variations (not used in current implementation)
use Gemini\GeminiHelper;          // Helper functions for Gemini API
use Gemini\Factory;               // Factory class to create Gemini client instances
use Gemini\Data\Content;          // Content class for structuring messages
use Gemini\Enums\Role;            // Enum for user/model roles in conversations
use Dotenv\Dotenv;                // Library for loading environment variables from .env file

/**
 * Environment Configuration
 * 
 * Load sensitive configuration data (like API keys) from a .env file
 * This keeps sensitive information out of the source code and version control
 */
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Retrieve the Gemini API key from environment variables
// This key is required to authenticate with Google's Gemini API
$yourApiKey = $_ENV['GEMINI_API_KEY'];

// Create a Gemini client instance using the factory pattern
// The factory handles the complex setup of HTTP clients and authentication
$client = (new Factory())->withApiKey($yourApiKey)->make();

/**
 * Session Management
 * 
 * Start a PHP session to maintain conversation history across page requests
 * Sessions allow us to store data on the server that persists between
 * different page loads for the same user
 */
session_start();

/**
 * Markdown Parser Initialization
 * 
 * Initialize Parsedown library to convert markdown text to HTML
 * This allows the AI to format responses with headers, code blocks, lists, etc.
 * The AI responses will be much more readable and professional-looking
 */
$parsedown = new \Parsedown();

/**
 * Variable Initialization
 * 
 * Initialize variables that will be used throughout the application
 * These variables store the current state of the chat interface
 */
$userInput = '';        // Stores the user's current message input
$chatHistory = [];      // Array to hold the conversation history for display
$lastResponse = '';     // Stores the AI's last response (not currently used)

/**
 * Form Processing - Handle User Input
 * 
 * This section processes the user's message when they submit the chat form.
 * It follows the Post-Redirect-Get (PRG) pattern to prevent form resubmission
 * when users refresh the page.
 */
if ($_POST && isset($_POST['name']) && !empty(trim($_POST['name']))) {
    // Sanitize user input by trimming whitespace
    $userInput = trim($_POST['name']);

    /**
     * Chat History Management
     * 
     * Initialize the chat history array in the session if it doesn't exist.
     * The session stores the conversation history so it persists across page loads.
     */
    if (!isset($_SESSION['chat_history'])) {
        $_SESSION['chat_history'] = [];
    }

    // Add the user's message to the conversation history
    // We store both the role (user/model) and content for each message
    $_SESSION['chat_history'][] = ['role' => 'user', 'content' => $userInput];

    /**
     * Convert Session History to API Format
     * 
     * The Gemini API expects Content objects with specific roles.
     * We need to convert our session data format to the API's expected format.
     */
    $history = [];
    foreach ($_SESSION['chat_history'] as $message) {
        // Convert our role strings to the API's Role enum values
        $role = $message['role'] === 'user' ? Role::USER : Role::MODEL;
        // Create Content objects that the API can understand
        $history[] = Content::parse(part: $message['content'], role: $role);
    }

    /**
     * AI Personality Configuration
     * 
     * This variable controls which instruction set the AI will use.
     * Different instruction sets give the AI different personalities and behaviors:
     * - 'default': General helpful assistant
     * - 'tutor': Programming tutor focused on education
     * - 'debugger': Specialized in helping with code debugging
     * - 'casual': Friendly, casual conversation style
     */
    $instructionType = 'default'; // Options: 'default', 'tutor', 'debugger', 'casual'
    
    /**
     * Context Enhancement
     * 
     * Add additional context to the user's input to help the AI provide
     * more relevant and current responses. For example, we can add the current date.
     */
    $enhancedInput = $userInput;
    if ($instructionType === 'default') {
        $enhancedInput = "Current date: " . date('Y-m-d') . "\n" . $userInput;
    }

    /**
     * Load System Instructions
     * 
     * System instructions define how the AI should behave and respond.
     * Each instruction type has its own configuration file that contains
     * specific guidelines for the AI's personality and response style.
     */
    $configFile = __DIR__ . "/config/instructions_{$instructionType}.txt";
    
    // Fallback to default instructions if the requested config file doesn't exist
    // This prevents errors if someone specifies an invalid instruction type
    if (!file_exists($configFile)) {
        $configFile = __DIR__ . "/config/instructions_default.txt";
    }
    
    // Read the system instructions from the configuration file
    $systemInstructions = file_get_contents($configFile);
    
    /**
     * Create Chat Session with Gemini API
     * 
     * Initialize a new chat session with the Gemini API, including:
     * - The specific model to use (gemini-2.0-flash)
     * - System instructions that define the AI's behavior
     * - Conversation history to maintain context
     */
    $chat = $client
        ->generativeModel(model: 'gemini-2.0-flash')  // Use the latest Gemini model
        ->withSystemInstruction(Content::parse(part: $systemInstructions))  // Set AI personality
        ->startChat(history: $history);  // Include conversation history for context

    /**
     * Send Message and Handle Response
     * 
     * Send the user's message to the AI and handle the response.
     * We use try-catch to gracefully handle any API errors.
     */
    try {
        // Send the enhanced user input to the AI
        $result = $chat->sendMessage($enhancedInput);
        // Extract the text response from the API result
        $response = $result->text();
    } catch (Exception $e) {
        // If there's an error, provide a user-friendly error message
        $response = "Sorry, there was an error processing your request: " . $e->getMessage();
    }

    // Add the AI's response to the conversation history
    $_SESSION['chat_history'][] = ['role' => 'model', 'content' => $response];

    /**
     * Post-Redirect-Get Pattern
     * 
     * Redirect to the same page after processing the form to prevent
     * form resubmission when users refresh the page. This is a web development
     * best practice that improves user experience.
     */
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

/**
 * Display Data Preparation
 * 
 * Retrieve the chat history from the session for display in the HTML.
 * This makes the conversation history available to the template.
 */
if (isset($_SESSION['chat_history'])) {
    $chatHistory = $_SESSION['chat_history'];
}

// Debug: Uncomment the line below to see session data for debugging
// echo "<pre>Session data: "; print_r($_SESSION); echo "</pre>";

/**
 * Clear Chat Functionality
 * 
 * Handle the "Clear Chat" button click by removing all chat history
 * from the session and redirecting to prevent form resubmission.
 */
if (isset($_POST['clear_chat'])) {
    unset($_SESSION['chat_history']);
    // Redirect to prevent form resubmission on refresh (PRG pattern)
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="no">

<head>
    <title>IS-115 - Prosjektoppgave</title>
    
    <!-- External CSS - Link to our custom stylesheet -->
    <link rel="stylesheet" href="assest/css/style.css">
    
    <!-- Prism.js for syntax highlighting -->
    <!-- These libraries provide beautiful syntax highlighting for code blocks -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
</head>

<body>
    <!-- Main container for the entire application -->
    <div class="container">
        <h1>IS-115 - Prosjektoppgave</h1>
        <h3>Conversation History:</h3>
        
        <!-- Chat area where conversation history is displayed -->
        <div class="chat-area">
            <?php if (!empty($chatHistory)): ?>
                <div>
                    <!-- Loop through each message in the chat history -->
                    <?php foreach ($chatHistory as $index => $message): ?>
                         <div class="message" role="<?php echo $message['role']; ?>">
                            <?php 
                            if ($message['role'] === 'model') {
                                // Parse markdown for AI responses to enable rich formatting
                                // This allows the AI to use headers, code blocks, lists, etc.
                                echo $parsedown->text($message['content']);
                            } else {
                                // Escape HTML for user messages to prevent XSS attacks
                                // nl2br converts newlines to <br> tags for proper display
                                echo nl2br(htmlspecialchars($message['content']));
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <!-- Show welcome message when no conversation history exists -->
                <p>Start a conversation by entering a message below.</p>
            <?php endif; ?>
        </div>
        
        <!-- Chat input form -->
        <form action="index.php" method="post">
            <!-- Text input for user messages -->
            <input type='text' name="name" placeholder='Enter your message here...' required>
            <!-- Submit button to send the message -->
            <button type="submit">Send</button>
            
            <!-- Clear chat button - only show if there's conversation history -->
            <?php if (!empty($chatHistory)): ?>
                <button type="submit" name="clear_chat" formnovalidate>Clear Chat</button>
            <?php endif; ?>
        </form>
    </div>
</body>

<!-- JavaScript for syntax highlighting -->
<script>
    /**
     * Initialize syntax highlighting when the page loads
     * 
     * This script runs after the page is fully loaded and applies
     * syntax highlighting to any code blocks in the chat messages.
     * Prism.js automatically detects code blocks and applies appropriate styling.
     */
    document.addEventListener('DOMContentLoaded', function() {
        // Check if Prism.js is loaded before trying to use it
        if (typeof Prism !== 'undefined') {
            Prism.highlightAll();
        }
    });
</script>

</html>