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
 * AJAX Form Processing - No longer needed in main file
 * 
 * Form processing has been moved to chat_ajax.php to handle AJAX requests.
 * This allows for dynamic updates without page reloads.
 */

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
 * Clear Chat Functionality - Moved to AJAX
 * 
 * Clear chat functionality has been moved to chat_ajax.php to handle AJAX requests.
 */
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
                <div class="messages-container">
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
        <form id="chatForm">
            <!-- Text input for user messages -->
            <input type='text' id="messageInput" name="message" placeholder='Enter your message here...' required>
            <!-- Submit button to send the message -->
            <button type="submit" id="sendButton">Send</button>
            
            <!-- Clear chat button - only show if there's conversation history -->
            <?php if (!empty($chatHistory)): ?>
                <button type="button" id="clearButton">Clear Chat</button>
            <?php endif; ?>
        </form>
        
        <!-- Loading indicator -->
        <div id="loadingIndicator" style="display: none; margin: 10px 0; color: #666;">
            <em>AI is thinking...</em>
        </div>
    </div>
</body>

<!-- JavaScript for AJAX functionality and syntax highlighting -->
<script>
    /**
     * AJAX Chat Application
     * 
     * This script handles all the AJAX functionality for the chat application,
     * including sending messages, receiving responses, and updating the UI
     * without page reloads.
     */
    
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize syntax highlighting
        if (typeof Prism !== 'undefined') {
            Prism.highlightAll();
        }
        
        // Get DOM elements
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const clearButton = document.getElementById('clearButton');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const chatArea = document.querySelector('.chat-area');
        
        // Handle form submission
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
        
        // Handle clear chat button
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                clearChat();
            });
        }
        
        /**
         * Send a message to the AI via AJAX
         */
        function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;
            
            // Disable form and show loading
            setLoadingState(true);
            
            // Create form data
            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('message', message);
            
            // Send AJAX request
            fetch('chat_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Add new messages to chat area
                    addMessagesToChat(data.data);
                    // Clear input
                    messageInput.value = '';
                    // Show clear button if not already visible
                    showClearButton();
                } else {
                    showError(data.error || 'An error occurred');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Network error occurred');
            })
            .finally(() => {
                setLoadingState(false);
            });
        }
        
        /**
         * Clear the chat history via AJAX
         */
        function clearChat() {
            if (!confirm('Are you sure you want to clear the chat?')) {
                return;
            }
            
            setLoadingState(true);
            
            const formData = new FormData();
            formData.append('action', 'clear_chat');
            
            fetch('chat_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear chat area
                    chatArea.innerHTML = '<p>Start a conversation by entering a message below.</p>';
                    // Hide clear button
                    hideClearButton();
                } else {
                    showError(data.error || 'Failed to clear chat');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Network error occurred');
            })
            .finally(() => {
                setLoadingState(false);
            });
        }
        
        /**
         * Add new messages to the chat area
         */
        function addMessagesToChat(messages) {
            // Remove welcome message if it exists
            const welcomeMessage = chatArea.querySelector('p');
            if (welcomeMessage && welcomeMessage.textContent.includes('Start a conversation')) {
                welcomeMessage.remove();
            }
            
            // Create messages container if it doesn't exist
            let messagesContainer = chatArea.querySelector('.messages-container');
            if (!messagesContainer) {
                messagesContainer = document.createElement('div');
                messagesContainer.className = 'messages-container';
                chatArea.appendChild(messagesContainer);
            }
            
            // Add each new message
            messages.forEach(message => {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'message';
                messageDiv.setAttribute('role', message.role);
                messageDiv.innerHTML = message.formatted_content;
                messagesContainer.appendChild(messageDiv);
            });
            
            // Scroll to bottom
            chatArea.scrollTop = chatArea.scrollHeight;
            
            // Re-apply syntax highlighting
            if (typeof Prism !== 'undefined') {
                Prism.highlightAll();
            }
        }
        
        /**
         * Set loading state for the form
         */
        function setLoadingState(loading) {
            sendButton.disabled = loading;
            messageInput.disabled = loading;
            if (clearButton) clearButton.disabled = loading;
            
            if (loading) {
                loadingIndicator.style.display = 'block';
                sendButton.textContent = 'Sending...';
            } else {
                loadingIndicator.style.display = 'none';
                sendButton.textContent = 'Send';
            }
        }
        
        /**
         * Show error message
         */
        function showError(message) {
            // Create or update error message
            let errorDiv = document.getElementById('errorMessage');
            if (!errorDiv) {
                errorDiv = document.createElement('div');
                errorDiv.id = 'errorMessage';
                errorDiv.style.cssText = 'color: red; margin: 10px 0; padding: 10px; background-color: #ffe6e6; border: 1px solid #ffcccc; border-radius: 5px;';
                chatForm.parentNode.insertBefore(errorDiv, chatForm);
            }
            errorDiv.textContent = 'Error: ' + message;
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                if (errorDiv) {
                    errorDiv.remove();
                }
            }, 5000);
        }
        
        /**
         * Show clear button
         */
        function showClearButton() {
            if (!clearButton) {
                const newClearButton = document.createElement('button');
                newClearButton.type = 'button';
                newClearButton.id = 'clearButton';
                newClearButton.textContent = 'Clear Chat';
                newClearButton.addEventListener('click', clearChat);
                chatForm.appendChild(newClearButton);
            }
        }
        
        /**
         * Hide clear button
         */
        function hideClearButton() {
            if (clearButton) {
                clearButton.remove();
            }
        }
    });
</script>

</html>