# IS-115 Prosjektoppgave - Plan for Filstruktur og Klassebibliotek

## 📋 Prosjektoversikt
**Prosjekt:** AI Chat Application med Google Gemini API  
**Språk:** PHP 7.4+  
**Arkitektur:** Web-basert chat-applikasjon med session management  

---

## 🗂️ Eksisterende Filstruktur

```
IS-115_Prosjektoppgave/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── images/
├── config/
│   ├── instructions_default.txt
│   ├── instructions_tutor.txt
│   ├── instructions_debugger.txt
│   ├── instructions_casual.txt
│   └── instructions_mealplanner.txt
├── includes/
│   └── session_functions.php
├── src/ (tom for øyeblikket)
├── vendor/ (Composer dependencies)
├── index.php
├── index_meal.php
├── chat_ajax.php
├── composer.json
└── README.md
```

---

## 📁 Plan for Include-filer

### **Eksisterende Include-filer (beholdes):**

#### 1. `includes/session_functions.php`
- **Formål:** Session management funksjoner
- **Inneholder:** 
  - `initializeSessions()`
  - `createSessionTitle()`
  - `getAllSessions()`
  - `renderSessionList()`
- **Status:** ✅ Implementert og fungerer

#### 2. `vendor/autoload.php`
- **Formål:** Composer autoloader
- **Inneholder:** Automatisk lasting av alle dependencies
- **Status:** ✅ Implementert

### **Foreslåtte nye Include-filer:**

#### 3. `includes/config.php`
```php
<?php
// Konfigurasjonsinnstillinger
define('APP_NAME', 'IS-115 Chat Application');
define('DEFAULT_INSTRUCTION_TYPE', 'default');
define('MAX_MESSAGE_LENGTH', 1000);
define('SESSION_TIMEOUT', 3600); // 1 time
?>
```

#### 4. `includes/security.php`
```php
<?php
// Sikkerhetsfunksjoner
function sanitizeInput($input) { /* ... */ }
function validateMessage($message) { /* ... */ }
function preventXSS($data) { /* ... */ }
?>
```

#### 5. `includes/helpers.php`
```php
<?php
// Hjelpefunksjoner
function formatTimestamp($timestamp) { /* ... */ }
function truncateText($text, $length) { /* ... */ }
function generateUniqueId() { /* ... */ }
?>
```

#### 6. `includes/ai_client.php`
```php
<?php
// AI-klient wrapper
function initializeAIClient() { /* ... */ }
function sendMessageToAI($message, $history) { /* ... */ }
function formatAIResponse($response) { /* ... */ }
?>
```

#### 7. `includes/response_handler.php`
```php
<?php
// Response formatting
function sendJSONResponse($success, $data, $error) { /* ... */ }
function formatChatMessage($message, $role) { /* ... */ }
function handleError($error) { /* ... */ }
?>
```

---

## 🏗️ Plan for Eget Klassebibliotek

### **Core Classes (src/ directory):**

#### 1. `src/ChatSession.php`
```php
<?php
class ChatSession {
    private $id;
    private $title;
    private $createdAt;
    private $updatedAt;
    private $messages;
    
    public function __construct($id = null) { /* ... */ }
    public function addMessage($role, $content) { /* ... */ }
    public function getMessages() { /* ... */ }
    public function updateTitle() { /* ... */ }
    public function toArray() { /* ... */ }
}
?>
```

#### 2. `src/Message.php`
```php
<?php
class Message {
    private $role;
    private $content;
    private $timestamp;
    private $formattedContent;
    
    public function __construct($role, $content) { /* ... */ }
    public function formatContent() { /* ... */ }
    public function toArray() { /* ... */ }
    public function isValid() { /* ... */ }
}
?>
```

#### 3. `src/AIClient.php`
```php
<?php
class AIClient {
    private $client;
    private $instructionType;
    
    public function __construct($apiKey, $instructionType = 'default') { /* ... */ }
    public function sendMessage($message, $history = []) { /* ... */ }
    public function setInstructionType($type) { /* ... */ }
    public function getAvailableInstructions() { /* ... */ }
}
?>
```

#### 4. `src/SessionManager.php`
```php
<?php
class SessionManager {
    private $sessions;
    private $currentSessionId;
    
    public function __construct() { /* ... */ }
    public function createNewSession() { /* ... */ }
    public function loadSession($sessionId) { /* ... */ }
    public function deleteSession($sessionId) { /* ... */ }
    public function getAllSessions() { /* ... */ }
    public function getCurrentSession() { /* ... */ }
}
?>
```

#### 5. `src/ResponseFormatter.php`
```php
<?php
class ResponseFormatter {
    private $parsedown;
    
    public function __construct() { /* ... */ }
    public function formatMessage($message, $role) { /* ... */ }
    public function formatChatHistory($history) { /* ... */ }
    public function formatSessionList($sessions) { /* ... */ }
}
?>
```

### **Utility Classes:**

#### 6. `src/Validator.php`
```php
<?php
class Validator {
    public static function validateMessage($message) { /* ... */ }
    public static function validateSessionId($id) { /* ... */ }
    public static function sanitizeInput($input) { /* ... */ }
    public static function isValidInstructionType($type) { /* ... */ }
}
?>
```

#### 7. `src/Logger.php`
```php
<?php
class Logger {
    private $logFile;
    
    public function __construct($logFile = 'logs/app.log') { /* ... */ }
    public function log($level, $message, $context = []) { /* ... */ }
    public function info($message, $context = []) { /* ... */ }
    public function error($message, $context = []) { /* ... */ }
}
?>
```

#### 8. `src/Config.php`
```php
<?php
class Config {
    private static $config = [];
    
    public static function load($file) { /* ... */ }
    public static function get($key, $default = null) { /* ... */ }
    public static function set($key, $value) { /* ... */ }
    public static function has($key) { /* ... */ }
}
?>
```

---

## 🔄 Foreslått Ny Filstruktur

```
IS-115_Prosjektoppgave/
├── assets/
│   ├── css/
│   │   └── style.css
│   └── images/
├── config/
│   ├── instructions_*.txt
│   └── app.php (ny konfigurasjonsfil)
├── includes/
│   ├── session_functions.php (eksisterende)
│   ├── config.php (ny)
│   ├── security.php (ny)
│   ├── helpers.php (ny)
│   ├── ai_client.php (ny)
│   └── response_handler.php (ny)
├── src/
│   ├── ChatSession.php
│   ├── Message.php
│   ├── AIClient.php
│   ├── SessionManager.php
│   ├── ResponseFormatter.php
│   ├── Validator.php
│   ├── Logger.php
│   └── Config.php
├── logs/ (ny mappe for logging)
├── vendor/
├── index.php
├── chat_ajax.php
└── composer.json
```

---

## 🎯 Fordeler med denne strukturen:

### **Include-filer:**
- ✅ **Modularitet:** Hver fil har et spesifikt formål
- ✅ **Gjenbruk:** Funksjoner kan brukes på tvers av filer
- ✅ **Vedlikehold:** Lettere å finne og oppdatere kode
- ✅ **Sikkerhet:** Sentralisert sikkerhetshåndtering

### **Klassebibliotek:**
- ✅ **OOP-prinsipper:** Objektorientert design
- ✅ **Encapsulation:** Data og metoder er kapslet sammen
- ✅ **Extensibility:** Lett å utvide funksjonalitet
- ✅ **Testing:** Lettere å teste individuelle klasser
- ✅ **Code Reuse:** Klasser kan gjenbrukes

---

## 📝 Implementeringsrekkefølge:

1. **Fase 1:** Opprett nye include-filer
2. **Fase 2:** Implementer core classes (ChatSession, Message)
3. **Fase 3:** Implementer AIClient og SessionManager
4. **Fase 4:** Implementer utility classes
5. **Fase 5:** Refaktor eksisterende kode til å bruke nye klasser
6. **Fase 6:** Testing og optimalisering

---

## 🔧 Tekniske Detaljer:

### **Autoloading:**
```php
// I composer.json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

### **Namespace-struktur:**
```php
namespace App;

class ChatSession {
    // ...
}
```

### **Dependency Injection:**
```php
$sessionManager = new SessionManager();
$aiClient = new AIClient($apiKey);
$responseFormatter = new ResponseFormatter();
```

---

**Dato:** $(date)  
**Forfatter:** IS-115 Student  
**Versjon:** 1.0