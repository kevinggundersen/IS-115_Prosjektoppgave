# IS-115 Prosjektoppgave - Kunnskapsgryta (AI Meal Planning Assistant)

This is a PHP web application for the IS-115 course project assignment that creates an intelligent meal planning assistant using Google's Gemini AI model and the Norwegian Food Database API.

## 🎯 Project Description

This project demonstrates how to build a specialized AI chat application for meal planning using PHP, Google Gemini API, and the Norwegian Food Database. The application helps students create personalized, budget-friendly meal plans based on their dietary preferences, restrictions, and nutritional goals.

### Key Features

- **Intelligent Meal Planning**: AI-powered meal plan generation based on user preferences
- **Norwegian Food Database Integration**: Access to complete nutritional data from Matvaretabellen.no
- **Comprehensive Preference Collection**: Detailed form for dietary restrictions, allergies, budget, and cooking constraints
- **Nutritional Analysis**: Automatic calorie and nutrient calculations for all suggested meals
- **Session Management**: Multiple conversation sessions with persistent chat history
- **Interactive Chat Interface**: Clean, modern web interface for ongoing meal planning discussions
- **Responsive Design**: Works well on desktop and mobile devices
- **Markdown Support**: Rich formatting for meal plans, recipes, and shopping lists
- **Collapsible Preferences**: User-friendly display of meal preferences in chat history

## 🛠️ Technical Stack

- **Backend**: PHP 7.4+
- **AI Integration**: Google Gemini API (gemini-2.0-flash model)
- **Nutritional Data**: Norwegian Food Database API (Matvaretabellen.no)
- **Dependencies**: Composer for package management
- **Frontend**: HTML5, CSS3, JavaScript (Prism.js for syntax highlighting)
- **Markdown Processing**: Parsedown library
- **Environment Management**: PHP Dotenv
- **Data Caching**: JSON file-based caching for nutritional data
- **Session Management**: PHP sessions with multiple conversation support

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

### Getting Started

1. Open your web browser and navigate to the application URL
2. Fill out the meal preferences form with your dietary requirements, budget, and cooking constraints
3. Click "Send inn preferanser" to submit your preferences
4. The AI will generate a personalized meal plan based on your input
5. Continue the conversation to ask questions, request modifications, or get additional meal suggestions

### Meal Preferences Form

The application starts with a comprehensive form to collect your meal planning needs:

- **Diet Type**: Vegetarian, vegan, pescetarian, gluten-free, lactose-free, or custom
- **Allergies**: Checkboxes for common allergens (nuts, eggs, milk, gluten, shellfish, soy, fish)
- **Food Preferences**: Foods you like and dislike
- **Budget**: Weekly food budget in Norwegian Kroner
- **Kitchen Equipment**: Available cooking equipment
- **Time Constraints**: Daily cooking time available
- **Meal Frequency**: Number of meals per day
- **Group Size**: Number of people to cook for
- **Nutritional Goals**: Optional calorie limits and protein targets

### Chat Features

- **Session Management**: Create multiple conversation sessions using the sidebar
- **Persistent History**: All conversations are saved and can be resumed later
- **Collapsible Preferences**: Your initial preferences are shown in a collapsible format
- **Rich Formatting**: Meal plans include formatted sections for meal plans, shopping lists, and recipes
- **Nutritional Information**: Each meal includes detailed calorie and nutrient breakdowns

### AI Capabilities

The meal planning AI can:
- Generate weekly meal plans based on your preferences
- Calculate accurate nutritional information using the Norwegian Food Database
- Provide detailed shopping lists with quantities
- Offer cooking tips and budget-saving strategies
- Suggest alternatives for dietary restrictions
- Modify meal plans based on your feedback

## 📁 Project Structure

```
IS-115_Prosjektoppgave/
├── assets/
│   ├── css/
│   │   └── style.css          # Application styles
│   ├── images/
│   │   └── Kunnskapsgryta_*.png  # Application branding images
│   └── js/
│       └── index.js           # Frontend JavaScript functionality
├── cache/
│   ├── food_groups.json       # Cached food groups data
│   └── nutritional_data.json  # Cached nutritional data
├── config/
│   ├── instructions_default.txt   # Default AI instructions
│   ├── instructions_tutor.txt     # Tutor mode instructions
│   ├── instructions_debugger.txt  # Debugger mode instructions
│   ├── instructions_casual.txt    # Casual mode instructions
│   └── instructions_mealplanner.txt # Meal planning AI instructions
├── includes/
│   ├── API_matvaretabellen.php    # Norwegian Food Database API service
│   └── session_functions.php      # Session management functions
├── vendor/                     # Composer dependencies
├── chat_ajax.php              # AJAX endpoint for chat functionality
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

- **instructions_mealplanner.txt**: Specialized meal planning assistant (default mode)
- **instructions_default.txt**: General helpful assistant
- **instructions_tutor.txt**: Educational programming tutor
- **instructions_debugger.txt**: Code debugging specialist
- **instructions_casual.txt**: Friendly coding buddy

The application is currently configured to use the meal planning mode by default. You can modify these files to customize the AI's behavior and personality. Use the old.index.php file for a general chat UI.

### Nutritional Data Configuration

The application automatically integrates with the Norwegian Food Database API:
- **API Endpoint**: https://www.matvaretabellen.no/api/
- **Caching**: Nutritional data is cached locally for 1 year to improve performance
- **Food Groups**: Complete categorization of foods by official Norwegian food groups
- **Nutritional Information**: Calories, protein, fat, carbohydrates, fiber, and sugar data

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

- **AI Integration**: How to integrate with Google's Gemini AI API for intelligent responses
- **External API Integration**: Connecting to the Norwegian Food Database API for nutritional data
- **Data Caching**: Implementing efficient caching strategies for external API data
- **Session Management**: Maintaining state across web requests with multiple conversation sessions
- **Form Handling**: Processing complex user input forms with validation and AJAX submission
- **Security**: Input sanitization, XSS prevention, and secure API key management
- **Frontend Integration**: Combining PHP backend with modern JavaScript for dynamic user interactions
- **Configuration Management**: Using environment variables for sensitive data and AI personality configuration
- **Dependency Management**: Using Composer for package management and external library integration
- **Responsive Design**: Creating mobile-friendly interfaces with CSS and JavaScript

## 🐛 Troubleshooting

### Common Issues

1. **API Key Error**: Make sure your `GEMINI_API_KEY` is correctly set in the `.env` file
2. **Composer Dependencies**: Run `composer install` to ensure all dependencies are installed
3. **File Permissions**: Ensure the web server has read/write access to the `cache/` directory for nutritional data caching
4. **PHP Version**: Verify you're using PHP 7.4 or higher
5. **Norwegian Food Database API**: If nutritional data isn't loading, check your internet connection and API availability
6. **Session Issues**: Clear browser cookies if you experience session-related problems
7. **Form Submission**: Ensure JavaScript is enabled for the meal preferences form to work properly

### Debug Mode

To enable debug mode, uncomment the debug line in `index.php`:

```php
echo "<pre>Session data: "; print_r($_SESSION); echo "</pre>";
```

### Cache Management

If you experience issues with nutritional data:
- Delete the `cache/` directory to force a fresh download from the Norwegian Food Database API
- Check that the `cache/` directory has proper write permissions (755 or 777)

## 📝 License

This project is created for educational purposes as part of the IS-115 course. Please check with your institution for specific licensing requirements.

## 🤝 Contributing

This is a student project for the IS-115 course. If you're a fellow student, feel free to use this as a reference or starting point for your own projects.

## 📞 Support

For questions about this project or the IS-115 course, please contact your course instructor or teaching assistant.
