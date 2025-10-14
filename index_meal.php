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

// Include session management functions
require_once 'includes/session_functions.php';

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


// Initialize sessions
initializeSessions();

// Initialize current session if it doesn't exist
if (!isset($_SESSION['current_session_id']) && !empty($chatHistory)) {
    $sessionId = uniqid('session_', true);
    $sessionTitle = createSessionTitle($chatHistory);
    
    $_SESSION['sessions'][$sessionId] = [
        'id' => $sessionId,
        'title' => $sessionTitle,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'messages' => $chatHistory
    ];
    
    $_SESSION['current_session_id'] = $sessionId;
}

// Get current session data for rendering
$allSessions = getAllSessions();
$currentSessionId = $_SESSION['current_session_id'] ?? null;

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
    <title>Kunnskapsgryta</title>
    
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
        <h1>Kunnskapsgryta</h1>
        
        <!-- Sidebar toggle button for mobile/tablet -->
        <button id="sidebarToggle" class="sidebar-toggle">☰ Samtaler</button>
        
        <!-- Main layout with sidebar and chat area -->
        <div class="main-layout">
            <!-- Sidebar for session history -->
            <div class="sidebar" id="sidebar">
                <div class="sidebar-header">
                    <button id="newChatButton" class="new-chat-btn">+ Ny Samtale</button>
                </div>
                <div class="session-list" id="sessionList">
                    <?php echo renderSessionList($allSessions, $currentSessionId); ?>
                </div>
            </div>
            
            <!-- Main chat area -->
            <div class="chat-container">
                <!-- Chat area where conversation history is displayed -->
                <div class="chat-area" id="chatArea">
                    <?php if (!empty($chatHistory)): ?>
                        <div class="messages-container">
                            <!-- Loop through each message in the chat history -->
                            <?php 
                            $firstUserMessageSkipped = false;
                            foreach ($chatHistory as $index => $message): 
                                // Make the first user message collapsible
                                if ($message['role'] === 'user' && !$firstUserMessageSkipped) {
                                    $firstUserMessageSkipped = true;
                            ?>
                                <div class="message collapsible-message" role="<?php echo $message['role']; ?>">
                                    <div class="collapsible-header" onclick="toggleCollapsible(this)">
                                        <span class="collapsible-icon">▼</span>
                                        <span class="collapsible-title">Dine matpreferanser</span>
                                    </div>
                                    <div class="collapsible-content" style="display: none;">
                                        <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                    </div>
                                </div>
                            <?php 
                                } else {
                            ?>
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
                            <?php 
                                }
                            endforeach; ?>
                        </div>
                    <?php else: ?>
                        <!-- Show welcome message when no conversation history exists -->
                        <p>Start en samtale ved å skrive en melding nedenfor.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Chat input form -->
                <form id="mealPreferencesForm" <?php echo (count($chatHistory) > 0) ? 'style="display: none;"' : ''; ?>>
                    <h3>Fortell oss om dine matpreferanser</h3>
                    
                    <!-- Diet type -->
                    <label for="dietType">Kosthold:</label>
                    <select id="dietType" name="dietType">
                        <option value="">Ingen spesielle krav</option>
                        <option value="vegetarisk">Vegetarisk</option>
                        <option value="vegansk">Vegansk</option>
                        <option value="pescetarian">Pescetarianer</option>
                        <option value="glutenfri">Glutenfri</option>
                        <option value="laktosefri">Laktosefri</option>
                        <option value="annet">Annet</option>
                    </select>
                    <input type="text" id="dietTypeOther" name="dietTypeOther" placeholder="Spesifiser kosthold..." style="display: none; margin-top: 5px;"><br>
                    <!-- Allergies -->
                    <label for="allergies">Allergier (velg alle som gjelder):</label>
                    <div id="allergies">
                        <label><input type="checkbox" name="allergies[]" value="nøtter"> Nøtter</label>
                        <label><input type="checkbox" name="allergies[]" value="egg"> Egg</label>
                        <label><input type="checkbox" name="allergies[]" value="melk"> Melk</label>
                        <label><input type="checkbox" name="allergies[]" value="gluten"> Gluten</label>
                        <label><input type="checkbox" name="allergies[]" value="skalldyr"> Skalldyr</label>
                        <label><input type="checkbox" name="allergies[]" value="soya"> Soya</label>
                        <label><input type="checkbox" name="allergies[]" value="fisk"> Fisk</label>
                        <label><input type="checkbox" name="allergies[]" value="annet" id="allergiesAnnet"> Annet</label>
                    </div>
                    <input type="text" id="allergiesOther" name="allergiesOther" placeholder="Spesifiser allergier..." style="display: none; margin-top: 5px;">
                    
                    <!-- Likes -->
                    <label for="likes">Mat du liker (valgfritt):</label>
                    <input type="text" id="likes" name="likes" placeholder="F.eks. pasta, kylling, tomat ..."><br>
                    
                    <!-- Dislikes -->
                    <label for="dislikes">Mat du ikke liker (valgfritt):</label>
                    <input type="text" id="dislikes" name="dislikes" placeholder="F.eks. fisk, brokkoli ..."><br>
                    
                    <!-- Budget -->
                    <label for="budget">Ukentlig matbudsjett (kr):</label>
                    <input type="number" id="budget" name="budget" min="0" step="1" placeholder="F.eks. 400" required><br>
                    
                    <!-- Kitchen equipment -->
                    <label for="equipment">Tilgjengelig kjøkkenutstyr:</label>
                    <input type="text" id="equipment" name="equipment" placeholder="F.eks. komfyr, mikrobølgeovn, vannkoker ..."><br>
                    
                    <!-- Cooking time -->
                    <label for="cookTime">Hvor mye tid har du til matlaging per dag? (minutter):</label>
                    <input type="number" id="cookTime" name="cookTime" min="0" step="1" placeholder="F.eks. 30"><br>
                    
                    <!-- Meals per day -->
                    <label for="mealsPerDay">Antall måltider per dag:</label>
                    <input type="number" id="mealsPerDay" name="mealsPerDay" min="1" max="6" placeholder="F.eks. 3"><br>

                    <!-- amount people -->
                    <label for="peopleAmount">Hvor mange lager du for?</label>
                    <input type="number" id="peopleAmount" name="peopleAmount" min="1" max="10" placeholder="F.eks. 3"><br>
                    
                    <!-- Submit button -->
                    <button type="submit" id="sendPreferencesButton">Send inn preferanser</button>
                </form>
                
                <!-- Chat input form -->
                <form id="chatForm" <?php echo (count($chatHistory) > 0) ? '' : 'style="display: none;"'; ?>>
                    <!-- Text input for user messages -->
                    <input type='text' id="messageInput" name="message" placeholder='Skriv meldingen din her...' required>
                    <!-- Submit button to send the message -->
                    <button type="submit" id="sendButton">Send</button>
                </form>
                
                <!-- Loading indicator -->
                <div id="loadingIndicator" style="display: none; margin: 10px 0; color: #666;">
                    <em>Forbereder noe godt...</em>
                </div>
            </div>
        </div>
    </div>
</body>

<!-- JavaScript for AJAX functionality and syntax highlighting -->
<script src="assest/js/index.js"></script>

</html>