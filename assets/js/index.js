/**
 * AJAX Chat Application
 * 
 * This script handles all the AJAX functionality for the chat application,
 * including sending messages, receiving responses, and updating the UI
 * without page reloads.
 */

// Wait for the DOM to be loaded
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
    const chatContainer = document.querySelector('.chat-container');
    const skipFormButton = document.getElementById('skipFormButton');
    const openPreferencesButton = document.getElementById('openPreferencesButton');


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

    if (skipFormButton) {
    skipFormButton.addEventListener('click', function () {
      skipMealForm();
    });
  }

    if (openPreferencesButton) {
      openPreferencesButton.addEventListener('click', function () {
        reopenMealForm();
      });
    }

function skipMealForm() {
  isSkipped = true;
  sessionStorage.setItem('mealFormSkipped', '1');

  // Remove or hide collapsible messages
  const collapsibleMessages = document.querySelectorAll('.collapsible-message');
  collapsibleMessages.forEach(msg => {
    msg.classList.remove('collapsible-message');
    msg.innerHTML = msg.querySelector('.collapsible-content')?.innerHTML || msg.innerHTML;
  });

  // Flip UI to chat mode
  if (mealPreferencesForm) mealPreferencesForm.style.display = 'none';
  if (chatContainer) chatContainer.classList.remove('form-only');
  if (chatForm) chatForm.style.display = 'block';
  if (chatArea) chatArea.style.display = 'block';

  // Show “open prefs” button so user can change mind
  if (openPreferencesButton) openPreferencesButton.style.display = 'inline-block';

  addSystemNotice('Du hoppet over preferanseskjemaet. Du kan chatte nå, eller angi preferanser når som helst.');
}

function reopenMealForm() {
  sessionStorage.removeItem('mealFormSkipped');

  // Flip UI back to form mode
  if (mealPreferencesForm) mealPreferencesForm.style.display = 'block';
  if (chatContainer) chatContainer.classList.add('form-only');
  if (chatForm) chatForm.style.display = 'none';
  if (chatArea) chatArea.style.display = 'none';

  // Hide “open prefs” button again
  if (openPreferencesButton) openPreferencesButton.style.display = 'none';

  // Rebuild collapsible for first user message
  applyCollapsibleToFirstUserMessage();
}

/**
 * Minimal system notice helper (no backend call)
 */
function addSystemNotice(text) {
  if (!chatArea) return;

  let messagesContainer = chatArea.querySelector('.messages-container');
  if (!messagesContainer) {
    messagesContainer = document.createElement('div');
    messagesContainer.className = 'messages-container';
    chatArea.appendChild(messagesContainer);
  }

  const div = document.createElement('div');
  div.className = 'message';
  div.setAttribute('role', 'system');
  const em = document.createElement('em');
  em.textContent = text;
  div.appendChild(em);
  messagesContainer.appendChild(div);

  chatArea.scrollTo({ top: chatArea.scrollHeight, behavior: 'smooth' });
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
                if (chatContainer) {
                    chatContainer.classList.remove('form-only');
                }
                if (chatForm) {
                    chatForm.style.display = 'block';
                }
                // Show chat area after meal preferences are submitted
                chatArea.style.display = 'block';
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
                if (chatContainer) {
                    chatContainer.classList.add('form-only');
                }
                if (chatForm) {
                    chatForm.style.display = 'none';
                }
                // Hide chat area when showing meal preferences form
                chatArea.style.display = 'none';
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
  const mealFormSkipped = sessionStorage.getItem('mealFormSkipped') === '1';
  console.log('Skip flag detected:', mealFormSkipped);
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

          // Add all messages at once (make first user message collapsible)
          let firstUserMessageSkipped = false;
          messages.forEach((message, index) => {
            if (message && message.role && message.formatted_content) {
              // Make the first user message collapsible — only if not skipped
              if (message.role === 'user' && !firstUserMessageSkipped) {
                firstUserMessageSkipped = true;

                const messageDiv = document.createElement('div');
                messageDiv.setAttribute('role', message.role);

                // Use the skip flag read earlier
                if (mealFormSkipped) {
                    // Just show plain message if skipped
                    messageDiv.className = 'message';
                    messageDiv.innerHTML = message.formatted_content;
                } else {
                    // Build collapsible version
                    messageDiv.className = 'message collapsible-message';
                    messageDiv.innerHTML = `
                        <div class="collapsible-header" onclick="toggleCollapsible(this)">
                            <span class="collapsible-icon">▼</span>
                            <span class="collapsible-title">Dine matpreferanser</span>
                        </div>
                        <div class="collapsible-content" style="display: none;">
                            ${message.formatted_content}
                        </div>
                    `;
                }

                messagesContainer.appendChild(messageDiv);
                return; // skip to next message
            }

              // For all other messages
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
        if (welcomeMessage && (welcomeMessage.textContent.includes('Start planleggingen ved å skrive inn dine preferanser nedenfor.') || welcomeMessage.textContent.includes('Start en samtale'))) {
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
        
        const skipped = sessionStorage.getItem('mealFormSkipped') === '1';
            if (!skipped) {
                
            // Apply collapsible functionality to first user message if needed
            applyCollapsibleToFirstUserMessage();

            // Scroll to last user message
            scrollToLastUserMessage();

            // Re-apply syntax highlighting
            if (typeof Prism !== 'undefined') {
                Prism.highlightAll();
            }
        }
        
        // Update form visibility after adding messages
            checkFormVisibility();
    }
    
    /**
     * Apply collapsible functionality to the first user message (meal preferences)
     */
    function applyCollapsibleToFirstUserMessage() {

        const messagesContainer = chatArea.querySelector('.messages-container');
        if (!messagesContainer) return;
        
        const userMessages = messagesContainer.querySelectorAll('.message[role="user"]');
        const firstUserMessage = userMessages[0];
        
        // Check if the first user message exists and is not already collapsible
        if (firstUserMessage && !firstUserMessage.classList.contains('collapsible-message')) {
            const content = firstUserMessage.innerHTML;
            
            // Replace the first user message with collapsible version
            firstUserMessage.className = 'message collapsible-message';
            firstUserMessage.innerHTML = `
                <div class="collapsible-header" onclick="toggleCollapsible(this)">
                    <span class="collapsible-icon">▼</span>
                    <span class="collapsible-title">Dine matpreferanser</span>
                </div>
                <div class="collapsible-content" style="display: none;">
                    ${content}
                </div>
            `;
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
        const messagesContainer = chatArea.querySelector('.messages-container');
        const welcomeMessage = chatArea.querySelector('p');
        const hasConversationMessages = messagesContainer && messagesContainer.children.length > 0;
        const hasWelcomeMessage =
        welcomeMessage &&
        welcomeMessage.textContent.includes('Start planleggingen ved å skrive inn dine preferanser nedenfor.');

        // respect "skipped" (sessionStorage flag)
        const skipped = sessionStorage.getItem('mealFormSkipped') === '1';

        if (hasConversationMessages || skipped) {
            // show chat, hide form
            mealPreferencesForm.style.display = 'none';
            if (chatContainer) chatContainer.classList.remove('form-only');
            if (chatForm) chatForm.style.display = 'block';
            chatArea.style.display = 'block';
            if (openPreferencesButton) openPreferencesButton.style.display = 'inline-block';
        } else if (hasWelcomeMessage || (!hasConversationMessages && !hasWelcomeMessage)) {
            // show form, hide chat
            mealPreferencesForm.style.display = 'block';
            if (chatContainer) chatContainer.classList.add('form-only');
            if (chatForm) chatForm.style.display = 'none';
            chatArea.style.display = 'none';
            if (openPreferencesButton) openPreferencesButton.style.display = 'none';
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
    
    /**
     * Toggle collapsible message content
     */
    window.toggleCollapsible = function(header) {
        const content = header.nextElementSibling;
        const icon = header.querySelector('.collapsible-icon');
        
        if (content.style.display === 'none') {
            content.style.display = 'block';
            icon.textContent = '▲';
        } else {
            content.style.display = 'none';
            icon.textContent = '▼';
        }
    };
    
});