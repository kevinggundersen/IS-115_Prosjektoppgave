<?php
require_once 'vendor/autoload.php';

use Gemini\Enums\ModelVariation;
use Gemini\GeminiHelper;
use Gemini\Factory;

$yourApiKey = 'AIzaSyDM0T3-cozZ5nWeUqdU04cOcnl93zWnBHU';
$client = (new Factory())->withApiKey($yourApiKey)->make();

$result = $client->generativeModel(model: 'gemini-2.0-flash')->generateContent('Hello');
echo $result->text() . "<br>"; // Hello! How can I assist you today?
?>