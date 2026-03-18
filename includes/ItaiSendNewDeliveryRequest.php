<?php
session_start();
require_once 'Itaidbh.inc.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pickup      = $_POST['pickup'] ?? '';
    $delivery    = $_POST['delivery'] ?? '';
    $moveDate    = $_POST['moveDate'] ?? '';
    $moveTime    = $_POST['moveTime'] ?? '';
    $objectType  = $_POST['objectType'] ?? '';
    $description = $_POST['description'] ?? '';
    $fullName    = $_POST['fullName'] ?? '';
    $phoneNumber = $_POST['phoneNumber'] ?? '';  

    try {
        $pdo->beginTransaction();

        // 1. Insert the Delivery Request
        $sql = "INSERT INTO deliveries (full_name, phone_number, pickup_location, delivery_location, moving_date, preferred_time, items_description, object_type) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$fullName, $phoneNumber, $pickup, $delivery, $moveDate, $moveTime, $description, $objectType]);
        
        // Get the ID of the request we just created
        $deliveryId = $pdo->lastInsertId();

        // 2. Create the Chat Session (This makes it visible on the dashboard)
        $chatToken = bin2hex(random_bytes(16)); // Creates the unique link
        $chatSql = "INSERT INTO chat_sessions (delivery_id, chat_token, is_active) VALUES (?, ?, 1)";
        $chatStmt = $pdo->prepare($chatSql);
        $chatStmt->execute([$deliveryId, $chatToken]);

        $pdo->commit();

        // 3. Set session roles so the user can be the "Spectator"
        $_SESSION['user_role'] = 'customer';
        $_SESSION['user_name'] = $fullName;

        // 4. Redirect to the Chat Page (The Spectator View)
        header("Location: ../public/chat.php?token=" . $chatToken);
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Database Error: " . $e->getMessage());
    }
}