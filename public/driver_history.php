<?php
session_start();
require_once '../includes/Itaidbh.inc.php';


if (!isset($_SESSION['user_id']) || $_SESSION['is_driver'] != 1) {
    header("Location: login.php");
    exit();
}

$currentDriverId = $_SESSION['user_id']; 


$stmt = $pdo->prepare("
    SELECT d.*, cs.chat_token,
    (SELECT quote_price FROM chat_messages 
     WHERE chat_token = cs.chat_token AND quote_price > 0 
     ORDER BY id DESC LIMIT 1) as final_price
    FROM deliveries d
    JOIN chat_sessions cs ON d.id = cs.delivery_id
    WHERE cs.chosen_driver_id = ? 
    AND d.status = 'completed'
    ORDER BY d.moving_date DESC
");

$stmt->execute([$currentDriverId]);
$history = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>היסטוריית הובלות - MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&family=Assistant:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', 'Assistant', sans-serif; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-900">

    <div class="max-w-2xl mx-auto p-6 lg:p-12">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">היסטוריה</h1>
                <p class="text-slate-500 text-sm mt-1">צפייה בכל ההובלות שהשלמת</p>
            </div>
            <a href="ItaiRegisteredDriver.php" class="bg-white text-slate-600 h-10 w-10 flex items-center justify-center rounded-full shadow-sm border border-slate-200 hover:text-indigo-600 transition-all">
                <i class="fas fa-arrow-left"></i>
            </a>
        </div>

        <?php if (empty($history)): ?>
            <div class="bg-white p-16 rounded-[2rem] shadow-sm border border-slate-100 text-center">
                <div class="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-history text-slate-300 text-2xl"></i>
                </div>
                <h2 class="text-lg font-bold text-slate-800">אין הובלות קודמות</h2>
                <p class="text-slate-400 text-sm mt-2">הובלות שתסיים יופיעו כאן באופן אוטומטי.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($history as $route): ?>
                    <div class="bg-white p-6 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-50 opacity-90 transition-all hover:opacity-100">
                        
                        <div class="flex items-start justify-between mb-6">
                            <div class="flex gap-4">
                                <div class="bg-slate-100 text-slate-500 w-12 h-12 rounded-2xl flex items-center justify-center shrink-0">
                                    <i class="fas fa-archive text-lg"></i>
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 mb-1">
                                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-400">הובלה הושלמה</p>
                                        <span class="bg-emerald-100 text-emerald-600 text-[9px] px-2 py-0.5 rounded-full font-bold">COMPLETED</span>
                                    </div>
                                    <h3 class="font-bold text-slate-800 leading-tight">
                                        <?php echo htmlspecialchars($route['pickup_location']); ?>
                                        <i class="fas fa-long-arrow-alt-left mx-2 text-slate-300"></i>
                                        <?php echo htmlspecialchars($route['delivery_location']); ?>
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 py-4 border-y border-slate-50 mb-6">
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-1">תאריך</p>
                                <p class="text-xs font-bold text-slate-700"><?php echo date("d/m/Y", strtotime($route['moving_date'])); ?></p>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-1">לקוח</p>
                                <p class="text-xs font-bold text-slate-700"><?php echo htmlspecialchars($route['full_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-[9px] font-black text-slate-400 uppercase mb-1">רווח</p>
                                <p class="text-xs font-bold text-emerald-600">₪<?php echo number_format($route['final_price'] ?? 0); ?></p>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <a href="success.php?token=<?php echo $route['chat_token']; ?>" 
                               class="flex items-center justify-center gap-2 bg-slate-100 text-slate-600 py-3 px-4 rounded-2xl text-xs font-bold hover:bg-slate-200 transition active:scale-95">
                                <i class="fas fa-file-invoice opacity-70"></i>
                                פרטי סיכום
                            </a>

                            <a href="chat.php?token=<?php echo $route['chat_token']; ?>" 
                               class="flex items-center justify-center gap-2 bg-indigo-50 text-indigo-400 py-3 px-4 rounded-2xl text-xs font-bold hover:bg-indigo-100 transition active:scale-95">
                                <i class="fas fa-comment-alt opacity-70"></i>
                                ארכיון צ'אט
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>