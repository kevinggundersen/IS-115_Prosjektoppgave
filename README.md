# IS-115 Prosjektoppgave - AI Chat Application

This is a PHP web application for the IS-115 course project assignment that creates an interactive chat interface using Google's Gemini AI model.

## 🎯 Project Description

This project demonstrates how to build a web-based AI chat application using PHP and the Google Gemini API. The application allows students to have conversations with an AI assistant that can be configured with different personalities and instruction sets.

### Key Features

- **Interactive Chat Interface**: Clean, modern web interface for chatting with AI
- **Multiple AI Personalities**: Switch between different AI instruction sets (tutor, debugger, casual, default)
- **Persistent Conversation History**: Chat history is maintained across page sessions
- **Markdown Support**: AI responses support rich formatting with headers, code blocks, lists, etc.
- **Syntax Highlighting**: Code examples are beautifully formatted with syntax highlighting
- **Session Management**: Secure session-based chat history storage
- **Post-Redirect-Get Pattern**: Prevents form resubmission issues
- **Responsive Design**: Works well on different screen sizes

## 🛠️ Technical Stack

- **Backend**: PHP 7.4+
- **AI Integration**: Google Gemini API (gemini-2.0-flash model)
- **Dependencies**: Composer for package management
- **Frontend**: HTML5, CSS3, JavaScript (Prism.js for syntax highlighting)
- **Markdown Processing**: Parsedown library
- **Environment Management**: PHP Dotenv

## 📋 Requirements

- PHP 7.4 or higher
- Composer (for dependency management)
- Google Gemini API key
- Web server (Apache/Nginx) or PHP built-in server

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd IS-115_Prosjektoppgave
```

### 2. Install Dependencies

```bash
composer install
```

This will install all required packages including:
- Google Gemini PHP client
- HTTP client libraries
- Markdown parser
- Environment variable loader

### 3. Environment Configuration

1. Copy the example environment file:
   ```bash
   cp example.env .env
   ```

2. Edit the `.env` file and add your Google Gemini API key:
   ```
   GEMINI_API_KEY=your_api_key_here
   ```

   **Note**: You can get a free API key from [Google AI Studio](https://makersuite.google.com/app/apikey)

### 4. Start the Application

#### Option A: Using PHP Built-in Server (Development)
```bash
php -S localhost:8000
```

#### Option B: Using XAMPP/WAMP/MAMP
Place the project folder in your web server's document root and access it via your browser.

## 💻 Usage

### Basic Usage

1. Open your web browser and navigate to the application URL
2. Start typing a message in the input field
3. Click "Send" to send your message to the AI
4. The AI will respond with a formatted message
5. Continue the conversation - your chat history will be maintained

### AI Personality Modes

You can change the AI's personality by modifying the `$instructionType` variable in `index.php`:

```php
$instructionType = 'default'; // Options: 'default', 'tutor', 'debugger', 'casual'
```

#### Available Modes:

- **Default**: General helpful assistant with educational focus
- **Tutor**: Programming tutor that provides step-by-step explanations
- **Debugger**: Specialized in helping with code debugging and error fixing
- **Casual**: Friendly, approachable coding buddy with a casual tone

### Features

- **Clear Chat**: Click the "Clear Chat" button to start a new conversation
- **Code Formatting**: AI responses support markdown formatting including code blocks
- **Syntax Highlighting**: Code examples are automatically highlighted
- **Responsive Design**: Works on desktop and mobile devices

## 📁 Project Structure

```
IS-115_Prosjektoppgave/
├── assest/
│   └── css/
│       └── style.css          # Application styles
├── config/
│   ├── instructions_default.txt   # Default AI instructions
│   ├── instructions_tutor.txt     # Tutor mode instructions
│   ├── instructions_debugger.txt  # Debugger mode instructions
│   └── instructions_casual.txt    # Casual mode instructions
├── src/                        # Source code directory (currently empty)
├── vendor/                     # Composer dependencies
├── composer.json              # Project dependencies
├── example.env                # Environment variables template
├── index.php                  # Main application file
└── README.md                  # This file
```

## 🔧 Configuration

### Environment Variables

The application uses the following environment variables:

- `GEMINI_API_KEY`: Your Google Gemini API key (required)

### AI Instruction Sets

Each instruction set in the `config/` directory defines how the AI should behave:

- **instructions_default.txt**: General helpful assistant
- **instructions_tutor.txt**: Educational programming tutor
- **instructions_debugger.txt**: Code debugging specialist
- **instructions_casual.txt**: Friendly coding buddy

You can modify these files to customize the AI's behavior and personality.

## 📚 Dependencies

The project uses the following main dependencies:

- **google-gemini-php/client**: Official Google Gemini API client for PHP
- **symfony/http-client**: HTTP client for API communication
- **vlucas/phpdotenv**: Environment variable management
- **erusev/parsedown**: Markdown to HTML conversion
- **guzzlehttp/guzzle**: HTTP client library
- **nyholm/psr7**: PSR-7 HTTP message implementation

## 🎓 Educational Value

This project demonstrates several important programming concepts:

- **API Integration**: How to integrate with external AI services
- **Session Management**: Maintaining state across web requests
- **Form Handling**: Processing user input and preventing resubmission
- **Security**: Input sanitization and XSS prevention
- **Frontend Integration**: Combining PHP backend with modern frontend technologies
- **Configuration Management**: Using environment variables for sensitive data
- **Dependency Management**: Using Composer for package management

## 🐛 Troubleshooting

### Common Issues

1. **API Key Error**: Make sure your `GEMINI_API_KEY` is correctly set in the `.env` file
2. **Composer Dependencies**: Run `composer install` to ensure all dependencies are installed
3. **File Permissions**: Ensure the web server has read access to all project files
4. **PHP Version**: Verify you're using PHP 7.4 or higher

### Debug Mode

To enable debug mode, uncomment the debug line in `index.php`:

```php
echo "<pre>Session data: "; print_r($_SESSION); echo "</pre>";
```

## 📝 License

This project is created for educational purposes as part of the IS-115 course. Please check with your institution for specific licensing requirements.

## 🤝 Contributing

This is a student project for the IS-115 course. If you're a fellow student, feel free to use this as a reference or starting point for your own projects.

## 📞 Support

For questions about this project or the IS-115 course, please contact your course instructor or teaching assistant.
