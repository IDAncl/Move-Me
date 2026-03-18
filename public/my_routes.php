<?php
session_start();
require_once '../includes/Itaidbh.inc.php';

if (!isset($_SESSION['user_name']) || $_SESSION['user_role'] !== 'driver') {
    header("Location: login.php");
    exit();
}

$currentDriver = $_SESSION['user_name'];

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Routes - MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-900">

    <div class="max-w-2xl mx-auto p-6 lg:p-12">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">My Routes</h1>
                <p class="text-slate-500 text-sm mt-1">Manage your confirmed deliveries</p>
            </div>
            <a href="ItaiRegisteredDriver.php" class="bg-white text-slate-600 h-10 w-10 flex items-center justify-center rounded-full shadow-sm border border-slate-200 hover:text-indigo-600 transition-all">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>

        <?php if (empty($myRoutes)): ?>
            <div class="bg-white p-16 rounded-[2rem] shadow-sm border border-slate-100 text-center">
                <div class="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-map-marked-alt text-slate-300 text-2xl"></i>
                </div>
                <h2 class="text-lg font-bold text-slate-800">No active routes yet</h2>
                <p class="text-slate-400 text-sm mt-2">When you win a bid, your route details will appear here.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($myRoutes as $route): ?>
                    <div class="bg-white p-6 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-50 transition-all hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)]">
                        
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex gap-4">
                                <div class="bg-emerald-500/10 text-emerald-600 w-12 h-12 rounded-2xl flex items-center justify-center shrink-0">
                                    <i class="fas fa-check-double text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-emerald-600 mb-1">Confirmed Delivery</p>
                                    <h3 class="font-bold text-slate-800 leading-tight">
                                        <?php echo htmlspecialchars($route['pickup_location']); ?>
                                        <i class="fas fa-long-arrow-alt-right mx-2 text-slate-300"></i>
                                        <?php echo htmlspecialchars($route['delivery_location']); ?>
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <a href="success.php?token=<?php echo $route['chat_token']; ?>" 
                               class="flex items-center justify-center gap-2 bg-slate-900 text-white py-3 px-4 rounded-2xl text-xs font-bold hover:bg-slate-800 transition active:scale-95">
                                <i class="fas fa-user-circle opacity-70"></i>
                                Client Info
                            </a>

                            <a href="chat.php?token=<?php echo $route['chat_token']; ?>" 
                               class="flex items-center justify-center gap-2 bg-indigo-50 text-indigo-600 py-3 px-4 rounded-2xl text-xs font-bold hover:bg-indigo-100 transition active:scale-95">
                                <i class="fas fa-comment-alt opacity-70"></i>
                                Open Chat
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>