<?php
/**
 * IS-115 Prosjektoppgave - AI Chat Application
 * 
 * This is a PHP web application that creates an interactive chat interface
 * using Google's Gemini AI model. The application allows students to have
 * conversations with an AI assistant that can be configured with different
 * instruction sets (tutor, debugger, casual, or default).
 * 
 * Main Features:
 * - Interactive chat interface with persistent conversation history
 * - Multiple AI personality modes via configuration files
 * - Markdown support for rich text responses
 * - Session-based chat history management
 * - Syntax highlighting for code examples
 * 
 * @author IS-115 Studentgruppe 2
 * @version 1.0
 */

// Include Composer's autoloader to load all required dependencies
// This automatically loads all the packages defined in composer.json
require_once 'vendor/autoload.php';

// Include session management functions
require_once 'includes/session_functions.php';

// Import necessary classes from the Google Gemini PHP client library
use Gemini\Enums\Role;            // Enum for user/model roles in conversations

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
 */
$parsedown = new \Parsedown();

/**
 * Variable Initialization
 * 
 * Initialize variables that will be used throughout the application
 * These variables store the current state of the chat interface
 */

$chatHistory = [];      // Array to hold the conversation history for display


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
if (!isset($_SESSION['sessions'])) {
    $_SESSION['sessions'] = [];
}

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


/**
 * Display the HTML for the chat application
 */
?>

<!DOCTYPE html>
<html lang="no">

<head>
    <title>Kunnskapsgryta</title>
    <!-- Set the icon for the chat application -->
    <link rel="icon" type="image/png" href="assets/images/Kunnskapsgryta_Uten_bakgrunn_Ingen_Tekst_zoom.png">
    
    <!-- Link to our custom stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Prism.js for syntax highlighting -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-core.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/autoloader/prism-autoloader.min.js"></script>
</head>

<body>
    <!-- Main container for the entire application -->
    <div class="container">
        <!-- Header for the chat application -->
        <div class="header">
            <!-- Branding for the chat application -->
            <div class="branding" style="display: flex; align-items: center; gap: 15px; margin-bottom: 20px;">
                <!-- Image for the branding -->
                <div class="branding-image" style="flex-shrink: 0;">
                <img src="assets/images/Kunnskapsgryta_Uten_bakgrunn.png" alt="Kunnskapsgryta" height="75px" width="75px">
            </div>
                <!-- Text for the branding -->
                <div class="branding-text" style="flex: 1;">
                <h1 style="margin: 0; color: #333; font-size: 2em;">Kunnskapsgryta</h1>
            </div>
        </div>
        </div>
        
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
                        <!-- File export form -->
                        <div class="file-export-btn-wrapper">
                            <form method="POST" action="chat_ajax.php" style="display: inline;">
                                <input type="hidden" name="action" value="export_file">
                                <button type="submit" class="file-export-btn">Eksporter måltidsplan</button>
                            </form>
                        </div>
                        <div class="messages-container">
                            <!-- Loop through each message in the chat history -->
                            <?php 
                            $firstUserMessageSkipped = false;
                            foreach ($chatHistory as $index => $message): 
                                // Make the first user message collapsible using javascript
                                if ($message['role'] === 'user' && !$firstUserMessageSkipped) {
                                    $firstUserMessageSkipped = true;
                            ?>
                                <!-- Collapsible message for the first user message -->
                                <div class="message collapsible-message" role="<?php echo $message['role']; ?>">
                                    <!-- Collapsible header for the first user message -->
                                    <div class="collapsible-header" onclick="toggleCollapsible(this)">
                                        <!-- Collapsible icon -->
                                        <span class="collapsible-icon">▼</span>
                                        <span class="collapsible-title">Dine matpreferanser</span>
                                    </div>
                                    <!-- Collapsible content for the first user message -->
                                    <div class="collapsible-content" style="display: none;">
                                        <?php echo nl2br(htmlspecialchars($message['content'])); ?>
                                    </div>
                                </div>
                            <?php 
                                } else {
                            ?>
                                <!-- Message for the chat history -->
                                 <div class="message" role="<?php echo $message['role']; ?>">
                                    <?php 
                                    if ($message['role'] === 'model') {
                                        // Parse markdown for AI responses to enable better formatting
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
                        <option value="Pescetarian">Pescetarianer</option>
                        <option value="glutenfri">Glutenfri</option>
                        <option value="laktosefri">Laktosefri</option>
                        <option value="annet">Annet</option>
                    </select>
                    <!-- Other diet type input -->
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
                    <!-- Other allergies input -->
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
                    
                    <!-- Nutritional constraints -->
                    <h4 style="margin-top: 20px; margin-bottom: 10px; color: #333;">Ernæringsmål (valgfritt)</h4>
                    
                    <!-- Max calories per meal -->
                    <label for="maxCaloriesPerMeal">Maksimalt antall kalorier per måltid:</label>
                    <input type="number" id="maxCaloriesPerMeal" name="maxCaloriesPerMeal" min="0" step="10" placeholder="F.eks. 500"><br>
                    
                    <!-- Max calories per day -->
                    <label for="maxCaloriesPerDay">Maksimalt antall kalorier per dag:</label>
                    <input type="number" id="maxCaloriesPerDay" name="maxCaloriesPerDay" min="0" step="50" placeholder="F.eks. 2000"><br>
                    
                    <!-- Protein goal -->
                    <label for="proteinGoal">Proteinmål per dag (gram):</label>
                    <input type="number" id="proteinGoal" name="proteinGoal" min="0" step="5" placeholder="F.eks. 80"><br>
                    
                    <!-- Submit button -->
                    <button type="submit" id="sendPreferencesButton">Send inn preferanser</button>

                    <!-- Skip button (no submit) -->
                    <button type="button" id="skipFormButton">
                        Hopp over
                    </button>

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

<!-- 
JavaScript for AJAX functionality and syntax highlighting
This file is used to handle the AJAX functionality for the chat application,
including sending messages, receiving responses, and updating the UI
without page reloads.
-->
<script src="assets/js/index.js"></script>

</html>