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
if ($_POST && isset($_POST['name'])) {
    $userInput = $_POST['name'];
    $result = $client->generativeModel(model: 'gemini-2.0-flash')->generateContent($userInput);
    echo $result->text() . "<br>";
} else {
    echo "Please enter some text and submit the form.";
}
?>

<DOCTYPE html>
    <html lang="no">

    <head>
        <title>IS-115 - Prosjektoppgave</title>
    </head>

    <body>
        <h1>IS-115 - Prosjektoppgave</h1>
        <form action="index.php" method="post">
            <input type='text' name="name" value='myName'>
            <button type="submit">Submit</button>
        </form>
    </body>

    </html>