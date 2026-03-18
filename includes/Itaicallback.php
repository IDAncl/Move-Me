<?php
require_once 'Itaiconfig.php';
require_once 'Itaidbh.inc.php';


if (isset($_GET['code'])) {
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Get user profile info from Google
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();
    
    $email = $google_account_info->email;
    $name = $google_account_info->name;
    $google_id = $google_account_info->id;

    // Check if user already exists in your DB
    $query = "SELECT * FROM users WHERE email = ?;";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        // If they don't exist, create a new record!
        $query = "INSERT INTO users (username, email, google_id) VALUES (?, ?, ?);";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$name, $email, $google_id]);
    }

    // Log them in via session
    session_start();
    $_SESSION['user_email'] = $email;
    $_SESSION['user_name'] = $name; 
    $_SESSION['user_role']  = 'driver';
    header("Location: ../public/ItaiRegisteredDriver.php");
    exit();
}