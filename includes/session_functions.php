<?php
/**
 * Session Management Functions
 * 
 * This file contains all the session management functions used throughout
 * the chat application. These functions handle storing, retrieving, and
 * displaying chat sessions.
 */

/**
 * Create a title for a chat session based on the first user message
 * 
 * This function looks through all messages in a session and finds the
 * first message sent by the user. It uses that message as the session title.
 * If the message is longer than 50 characters, it gets shortened and "..." is added.
 * 
 * @param array $messages Array of message objects, each containing 'role' and 'content'
 * @return string The title for the session
 */
function createSessionTitle($messages) {
    // Loop through each message in the session
    foreach ($messages as $message) {
        // Check if this message was sent by the user (not the AI)
        if ($message['role'] === 'user') {
            // Get the content of the first user message
            $title = $message['content'];
            
            // Check if the title is longer than 50 characters
            if (strlen($title) > 50) {
                // If it's too long, cut it to 50 characters and add "..."
                return substr($title, 0, 50) . '...';
            } else {
                // If it's short enough, use the full message as title
                return $title;
            }
        }
    }
    
    // If no user messages were found, return a default title
    return 'Ny samtale';
}

/**
 * Get all chat sessions sorted by most recent activity
 * 
 * This function finds all stored chat sessions and sorts them
 * so that the most recently updated session appears first.
 * 
 * @return array Array of session objects, sorted by update time (newest first)
 */
function getAllSessions() {
    // Get all sessions from the session storage, or empty array if none exist
    $sessions = $_SESSION['sessions'] ?? [];
    
    // Sort the sessions by their 'updated_at' timestamp
    // The uasort function maintains the array keys while sorting
    uasort($sessions, function($a, $b) {
        // Convert the timestamp strings to Unix timestamps for comparison
        // strtotime() converts a date string to a number of seconds since 1970
        $timeA = strtotime($a['updated_at']);
        $timeB = strtotime($b['updated_at']);
        
        // Return the difference (B - A) to sort newest first
        // If B is newer than A, the result is positive, so B comes first
        return $timeB - $timeA;
    });
    
    // Return the sorted sessions array
    return $sessions;
}

/**
 * Generate HTML code for displaying the session list in the sidebar
 * 
 * This function takes an array of sessions and converts them into HTML
 * that can be displayed in the sidebar. Each session becomes a clickable
 * item with the session title, date, and a delete button.
 * 
 * The function also handles the case where there are no sessions to display.
 * 
 * @param array $sessions Array of session objects to display
 * @param string|null $currentSessionId The ID of the currently active session
 * @return string HTML code for the session list
 */
function renderSessionList($sessions, $currentSessionId = null) {
    // Check if there are any sessions to display
    if (empty($sessions)) {
        // If no sessions exist, return a message telling the user to start chatting
        return '<p style="color: #666; font-style: italic; text-align: center; margin-top: 20px;">Ingen samtaler enda</p>';
    }
    
    // Start building the HTML string
    $html = '';
    
    // Loop through each session and create HTML for it
    foreach ($sessions as $session) {
        // Convert the session's update timestamp to a readable date format
        // DateTime class provides better date handling than basic PHP functions
        $date = new DateTime($session['updated_at']);
        // Format the date as "Year-Month-Day Hour:Minute"
        $formattedDate = $date->format('Y-m-d H:i');
        
        // Determine if this session is currently active
        // If the session ID matches the current session, add the 'active' CSS class
        $activeClass = ($currentSessionId === $session['id']) ? ' active' : '';
        
        // Build the HTML for this session item
        // htmlspecialchars() prevents XSS attacks by converting special characters
        $html .= '<div class="session-item' . $activeClass . '" data-session-id="' . htmlspecialchars($session['id']) . '">';
        
        // Add the session title
        $html .= '<div class="session-title">' . htmlspecialchars($session['title']) . '</div>';
        
        // Add the formatted date
        $html .= '<div class="session-date">' . $formattedDate . '</div>';
        
        // Add the delete button with an onclick event
        // The onclick calls a JavaScript function to delete the session
        $html .= '<button class="session-delete" onclick="deleteSession(\'' . htmlspecialchars($session['id']) . '\', event)">×</button>';
        
        // Close the session item div
        $html .= '</div>';
    }
    
    // Return the complete HTML string
    return $html;
}
?>
