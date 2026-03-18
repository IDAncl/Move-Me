<?php
// 1. Load the library first
require_once __DIR__ . '/../vendor/autoload.php';

// 2. Load the .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// 3. Create the Google Client object
$client = new Google_Client();
$client->setClientId($_ENV['GOOGLE_CLIENT_ID']);
$client->setClientSecret($_ENV['GOOGLE_CLIENT_SECRET']);
$client->setRedirectUri('http://localhost/itaiTalStartup/includes/Itaicallback.php');
$client->addScope("email");
$client->addScope("profile");
$client->setPrompt('select_account');

