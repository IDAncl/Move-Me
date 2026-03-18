<?php
session_start();
require_once '../includes/Itaidbh.inc.php';

// Ensure the user is a driver
if (!isset($_SESSION['user_name']) || $_SESSION['user_role'] !== 'driver') {
    header("Location: login.php");
    exit();
}

$currentDriver = $_SESSION['user_name'];

// Fetch only the routes this driver has WON
$stmt = $pdo->prepare("
    SELECT d.*, cs.chat_token 
    FROM deliveries d
    JOIN chat_sessions cs ON d.id = cs.delivery_id
    WHERE cs.chosen_driver_id = ? AND cs.is_active = 0
    ORDER BY d.created_at DESC
");
$stmt->execute([$currentDriver]);
$myRoutes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Routes - MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 font-sans">
    <div class="max-w-4xl mx-auto p-6">
        <div class="flex items-center justify-between mb-8">
            <h1 class="text-2xl font-bold text-gray-800">My Confirmed Routes</h1>
            <a href="ItaiRegisteredDriver.php" class="text-indigo-600 hover:underline text-sm font-bold">Back to Dashboard</a>
        </div>

        <?php if (empty($myRoutes)): ?>
            <div class="bg-white p-12 rounded-2xl shadow-sm text-center">
                <div class="text-gray-300 mb-4"><i class="fas fa-route fa-3x"></i></div>
                <p class="text-gray-500">You haven't won any bids yet. Start bidding to see routes here!</p>
            </div>
        <?php else: ?>
            <div class="grid gap-4">
                <?php foreach ($myRoutes as $route): ?>
                    <div class="bg-white p-5 rounded-2xl shadow-sm border border-emerald-100 flex justify-between items-center transition hover:shadow-md">
                        <div class="flex items-center gap-4">
                            <div class="bg-emerald-100 text-emerald-600 w-12 h-12 rounded-full flex items-center justify-center">
                                <i class="fas fa-check"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-gray-800">
                                    <?php echo htmlspecialchars($route['pickup_location']); ?> → 
                                    <?php echo htmlspecialchars($route['delivery_location']); ?>
                                </h3>
                                <p class="text-xs text-gray-400 font-medium uppercase tracking-tighter">
                                    Confirmed Job #<?php echo $route['id']; ?>
                                </p>
                            </div>
                        </div>
                        
                        <div class="text-right">
                            <a href="chat.php?token=<?php echo $route['chat_token']; ?>" 
                               class="inline-block bg-indigo-50 text-indigo-600 px-4 py-2 rounded-lg text-xs font-black hover:bg-indigo-100 transition">
                                VIEW CHAT LOG
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>