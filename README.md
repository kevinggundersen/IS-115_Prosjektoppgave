# IS-115 Prosjektoppgave - Kunnskapsgryta (AI Meal Planning Assistant)

This is a PHP web application for the IS-115 course project assignment that creates an intelligent meal planning assistant using Google's Gemini AI model and the Norwegian Food Database API.

## 🎯 Project Description

This project demonstrates how to build a specialized AI chat application for meal planning using PHP, Google Gemini API, and the Norwegian Food Database. The application helps students create personalized, budget-friendly meal plans based on their dietary preferences, restrictions, and nutritional goals.

### Features

- **Intelligent Meal Planning**: AI-powered meal plan generation based on user preferences
- **Norwegian Food Database Integration**: Access to complete nutritional data from Matvaretabellen.no
- **Comprehensive Preference Collection**: Detailed form for dietary restrictions, allergies, budget, and cooking constraints
- **Nutritional Analysis**: Automatic calorie and nutrient calculations for all suggested meals
- **Session Management**: Multiple conversation sessions with persistent chat history
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
- Web server using XAMPP/WAMP/MAMP

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

or 
```bash
composer update
```
if updating

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

### 4. Start the Application Using XAMPP/WAMP/MAMP

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
- **Persistent History**: All conversations are saved in your browser and can be resumed later
- **Collapsible Preferences**: Your initial preferences are shown in a collapsible format
- **Rich Formatting**: Meal plans include formatted sections for meal plans, shopping lists, and recipes
- **Nutritional Information**: Each meal includes detailed calorie and nutrient breakdowns
- **Export**: Export your meal plan as a PDF file

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

- `GEMINI_API_KEY`: Your Google Gemini API key

### AI Instruction Sets

Each instruction set in the `config/` directory defines how the AI should behave:

- **instructions_mealplanner.txt**: Specialized meal planning assistant (default mode)
- **instructions_default.txt**: General helpful assistant

The application is currently configured to use the meal planning mode by default. You can modify these files to customize the AI's behavior and personality.

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
- "dompdf/dompdf": HTML to PDF library

### Cache Management

If you experience issues with nutritional data:
- Delete the `cache/` directory to force a fresh download from the Norwegian Food Database API
- Check that the `cache/` directory has proper write permissions (755 or 777)

