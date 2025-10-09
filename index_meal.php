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
                    <label for="protionsNumber">Antal posjoner:</label>
                    <input type="number" id="protionsNumber" name="protionsNumber" min="1" max="10" placeholder="F.eks. 3"><br>
                    
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
                    <em>AI tenker...</em>
                </div>
            </div>
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
        
        // Set initial scroll position to bottom after content is rendered
        setTimeout(() => {
            chatArea.scrollTop = chatArea.scrollHeight;
        }, 0);
        
        // Get DOM elements
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const chatArea = document.getElementById('chatArea');
        const newChatButton = document.getElementById('newChatButton');
        const sessionList = document.getElementById('sessionList');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const mealPreferencesForm = document.getElementById('mealPreferencesForm');
        const sendPreferencesButton = document.getElementById('sendPreferencesButton');
        
        // Add click handlers to existing session items
        addSessionClickHandlers();
        
        // Check if form should be hidden on page load
        checkFormVisibility();
        
        // Handle form submission
        if (chatForm) {
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendMessage();
            });
        }
        
        // Handle meal preferences form submission
        if (mealPreferencesForm) {
            mealPreferencesForm.addEventListener('submit', function(e) {
                e.preventDefault();
                sendMealPreferences();
            });
        }
        
        // Handle diet type "annet" option
        const dietTypeSelect = document.getElementById('dietType');
        const dietTypeOtherInput = document.getElementById('dietTypeOther');
        
        if (dietTypeSelect && dietTypeOtherInput) {
            dietTypeSelect.addEventListener('change', function() {
                if (this.value === 'annet') {
                    dietTypeOtherInput.style.display = 'block';
                    dietTypeOtherInput.required = true;
                } else {
                    dietTypeOtherInput.style.display = 'none';
                    dietTypeOtherInput.required = false;
                    dietTypeOtherInput.value = '';
                }
            });
        }
        
        // Handle allergies "annet" checkbox
        const allergiesAnnetCheckbox = document.getElementById('allergiesAnnet');
        const allergiesOtherInput = document.getElementById('allergiesOther');
        
        if (allergiesAnnetCheckbox && allergiesOtherInput) {
            allergiesAnnetCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    allergiesOtherInput.style.display = 'block';
                    allergiesOtherInput.required = true;
                } else {
                    allergiesOtherInput.style.display = 'none';
                    allergiesOtherInput.required = false;
                    allergiesOtherInput.value = '';
                }
            });
        }
        
        // Handle new chat button
        newChatButton.addEventListener('click', function() {
            createNewSession();
        });
        
        // Handle sidebar toggle
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                toggleSidebar();
            });
        }
        
        /**
         * Send a message to the AI via AJAX
         */
        function sendMessage() {
            const message = messageInput ? messageInput.value.trim() : '';
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
                    if (messageInput) messageInput.value = '';
                    // Reload sessions to update titles
                    reloadSessions();
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
         * Send meal preferences as a chat message
         */
        function sendMealPreferences() {
            // Disable form and show loading
            setLoadingState(true);
            
            // Create form data for AJAX - send all form data to PHP
            const formData = new FormData(mealPreferencesForm);
            formData.append('action', 'send_meal_preferences');
            
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
                    // Hide the meal preferences form and show chat form after successful submission
                    mealPreferencesForm.style.display = 'none';
                    if (chatForm) {
                        chatForm.style.display = 'block';
                    }
                    // Reload sessions to update titles
                    reloadSessions();
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
         * Create a new chat session
         */
        function createNewSession() {
            setLoadingState(true);
            
            const formData = new FormData();
            formData.append('action', 'create_new_session');
            
            fetch('chat_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear chat area
                    chatArea.innerHTML = '<p>Start planleggingen ved å skrive inn dine preferanser nedenfor.</p>';
                    // Show the meal preferences form and hide chat form for new session
                    if (mealPreferencesForm) {
                        mealPreferencesForm.style.display = 'block';
                    }
                    if (chatForm) {
                        chatForm.style.display = 'none';
                    }
                    // Reload sessions
                    reloadSessions();
                } else {
                    showError(data.error || 'Failed to create new session');
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
         * Add click handlers to existing session items
         */
        function addSessionClickHandlers() {
            const sessionItems = document.querySelectorAll('.session-item');
            sessionItems.forEach(item => {
                item.addEventListener('click', function(e) {
                    if (!e.target.classList.contains('session-delete')) {
                        const sessionId = this.dataset.sessionId;
                        loadSession(sessionId);
                    }
                });
            });
        }
        
        /**
         * Reload sessions from server (for dynamic updates)
         */
        function reloadSessions() {
            const formData = new FormData();
            formData.append('action', 'get_sessions');
            
            fetch('chat_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the session list HTML
                    sessionList.innerHTML = '';
                    Object.values(data.data).forEach(session => {
                        const date = new Date(session.updated_at);
                        const formattedDate = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                        
                        const sessionElement = document.createElement('div');
                        sessionElement.className = 'session-item';
                        sessionElement.dataset.sessionId = session.id;
                        sessionElement.innerHTML = `
                            <div class="session-title">${session.title}</div>
                            <div class="session-date">${formattedDate}</div>
                            <button class="session-delete" onclick="deleteSession('${session.id}', event)">×</button>
                        `;
                        
                        sessionElement.addEventListener('click', function(e) {
                            if (!e.target.classList.contains('session-delete')) {
                                loadSession(session.id);
                            }
                        });
                        
                        sessionList.appendChild(sessionElement);
                    });
                }
            })
            .catch(error => {
                console.error('Error reloading sessions:', error);
            });
        }
        
        /**
         * Load a specific session
         */
        function loadSession(sessionId) {
            console.log('Loading session:', sessionId);
            setLoadingState(true);
            
            const formData = new FormData();
            formData.append('action', 'load_session');
            formData.append('session_id', sessionId);
            
            fetch('chat_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Session load response:', data);
                
                if (data.success) {
                    // Clear current chat and load session messages
                    chatArea.innerHTML = '';
                    
                    // Check if we have messages (note: messages are in data.data.messages)
                    const messages = data.data ? data.data.messages : data.messages;
                    if (messages && Array.isArray(messages) && messages.length > 0) {
                        console.log('Loading', messages.length, 'messages');
                        
                        // Create messages container
                        const messagesContainer = document.createElement('div');
                        messagesContainer.className = 'messages-container';
                        
                        // Add all messages at once
                        messages.forEach((message, index) => {
                            if (message && message.role && message.formatted_content) {
                                const messageDiv = document.createElement('div');
                                messageDiv.className = 'message';
                                messageDiv.setAttribute('role', message.role);
                                messageDiv.innerHTML = message.formatted_content;
                                messagesContainer.appendChild(messageDiv);
                            }
                        });
                        
                        // Add the container to chat area
                        chatArea.appendChild(messagesContainer);
                        console.log('Messages loaded successfully');
                    } else {
                        console.log('No messages found, showing welcome message');
                        chatArea.innerHTML = '<p>Start a conversation by entering a message below.</p>';
                    }
                    
                    // Update active session in sidebar
                    updateActiveSession(sessionId);
                    
                    // Re-apply syntax highlighting
                    if (typeof Prism !== 'undefined') {
                        Prism.highlightAll();
                    }
                    
                    // Scroll to bottom
                    setTimeout(() => {
                        chatArea.scrollTop = chatArea.scrollHeight;
                    }, 100);
                    
                    // Update form visibility based on loaded messages
                    checkFormVisibility();
                } else {
                    console.error('Session load failed:', data.error);
                    showError(data.error || 'Failed to load session');
                }
            })
            .catch(error => {
                console.error('Error loading session:', error);
                showError('Network error occurred: ' + error.message);
            })
            .finally(() => {
                setLoadingState(false);
            });
        }
        
        /**
         * Delete a session (global function for onclick)
         */
        window.deleteSession = function(sessionId, event) {
            event.stopPropagation();
            
            if (!confirm('Are you sure you want to delete this chat session?')) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', 'delete_session');
            formData.append('session_id', sessionId);
            
            fetch('chat_ajax.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    reloadSessions();
                } else {
                    showError(data.error || 'Failed to delete session');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showError('Network error occurred');
            });
        };
        
        /**
         * Update active session in sidebar
         */
        function updateActiveSession(sessionId) {
            // Remove active class from all sessions
            document.querySelectorAll('.session-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Add active class to current session
            const currentSession = document.querySelector(`[data-session-id="${sessionId}"]`);
            if (currentSession) {
                currentSession.classList.add('active');
            } else {
                // If session element doesn't exist yet, reload sessions
                console.log('Session element not found, reloading sessions...');
                reloadSessions();
            }
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
            
            // Scroll to the last user message
            scrollToLastUserMessage();
            
            
            // Re-apply syntax highlighting
            if (typeof Prism !== 'undefined') {
                Prism.highlightAll();
            }
        }
        
        /**
         * Set loading state for the form
         */
        function setLoadingState(loading) {
            if (sendButton) sendButton.disabled = loading;
            if (messageInput) messageInput.disabled = loading;
            if (newChatButton) newChatButton.disabled = loading;
            if (sendPreferencesButton) sendPreferencesButton.disabled = loading;
            
            if (loading) {
                loadingIndicator.style.display = 'block';
                if (sendButton) sendButton.textContent = 'Sending...';
                if (sendPreferencesButton) sendPreferencesButton.textContent = 'Sender...';
            } else {
                loadingIndicator.style.display = 'none';
                if (sendButton) sendButton.textContent = 'Send';
                if (sendPreferencesButton) sendPreferencesButton.textContent = 'Send inn preferanser';
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
         * Scroll to the last user message
         */
        function scrollToLastUserMessage() {
            const userMessages = chatArea.querySelectorAll('.message[role="user"]');
            if (userMessages.length > 0) {
                const lastUserMessage = userMessages[userMessages.length - 1];
                
                // Calculate the position of the user message relative to the chat area's content
                const messagesContainer = chatArea.querySelector('.messages-container');
                let targetScrollTop;
                
                if (messagesContainer) {
                    // Get the position of the message relative to the messages container
                    const messageOffsetTop = lastUserMessage.offsetTop - messagesContainer.offsetTop;
                    targetScrollTop = messageOffsetTop;
                } else {
                    // Fallback: use the message's position relative to chat area
                    targetScrollTop = lastUserMessage.offsetTop;
                }
                
                chatArea.scrollTo({
                    top: Math.max(0, targetScrollTop),
                    behavior: 'smooth'
                });
            } else {
                // Fallback to bottom if no user messages
                chatArea.scrollTo({
                    top: chatArea.scrollHeight,
                    behavior: 'smooth'
                });
            }
        }
        
        /**
         * Check if the meal preferences form should be hidden based on chat history
         */
        function checkFormVisibility() {
            if (!mealPreferencesForm) return;
            
            // Check if there are any messages in the chat area (excluding welcome message)
            const messagesContainer = chatArea.querySelector('.messages-container');
            const welcomeMessage = chatArea.querySelector('p');
            
            // If there's a messages container with actual messages, hide the meal form and show chat form
            if (messagesContainer && messagesContainer.children.length > 0) {
                mealPreferencesForm.style.display = 'none';
                if (chatForm) {
                    chatForm.style.display = 'block';
                }
            }
            // If there's only a welcome message, show the meal form and hide chat form
            else if (welcomeMessage && welcomeMessage.textContent.includes('Start en samtale')) {
                mealPreferencesForm.style.display = 'block';
                if (chatForm) {
                    chatForm.style.display = 'none';
                }
            }
        }
        
        /**
         * Toggle sidebar visibility on mobile/tablet
         */
        function toggleSidebar() {
            if (sidebar) {
                sidebar.classList.toggle('show');
                
                // Update button text based on sidebar state
                if (sidebar.classList.contains('show')) {
                    sidebarToggle.textContent = '✕ Skjul Samtaler';
                } else {
                    sidebarToggle.textContent = '☰ Samtaler';
                }
            }
        }
        
    });
</script>

</html>