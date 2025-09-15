<?php
require_once 'vendor/autoload.php';

use Gemini\Enums\ModelVariation;
use Gemini\GeminiHelper;
use Gemini\Factory;
use Dotenv\Dotenv;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$yourApiKey = $_ENV['GEMINI_API_KEY'];
$client = (new Factory())->withApiKey($yourApiKey)->make();

// Check if form was submitted and get the input value
$userInput = '';
$result = '';

if ($_POST && isset($_POST['name'])) {
    $userInput = $_POST['name'];
    $result = $client->generativeModel(model: 'gemini-2.0-flash')->generateContent($userInput);
    
    // Store result in session and redirect to prevent resubmission
    session_start();
    $_SESSION['result'] = $result->text();
    $_SESSION['userInput'] = $userInput;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit();
}

// Check if we have a result from session (after redirect)
session_start();
if (isset($_SESSION['result'])) {
    $result = $_SESSION['result'];
    $userInput = $_SESSION['userInput'];
    unset($_SESSION['result']);
    unset($_SESSION['userInput']);
}
?>

<DOCTYPE html>
    <html lang="no">

    <head>
        <title>IS-115 - Prosjektoppgave</title>
    </head>

    <body>
        <h1>IS-115 - Prosjektoppgave</h1>
        
        <?php if ($result): ?>
            <div style="background-color: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px;">
                <strong>You:</strong> <?php echo htmlspecialchars($userInput); ?><br><br>
                <strong>Response:</strong><br>
                <?php echo nl2br(htmlspecialchars($result)); ?>
            </div>
        <?php else: ?>
            <p>Please enter some text and submit the form.</p>
        <?php endif; ?>
        
        <form action="index.php" method="post">
            <input type='text' name="name" placeholder='Enter your text here...'>
            <button type="submit">Submit</button>
        </form>
    </body>

    </html>