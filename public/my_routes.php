<?php
session_start();
require_once '../includes/Itaidbh.inc.php';

if (!isset($_SESSION['user_name']) || $_SESSION['is_driver'] != 1) {
    header("Location: login.php");
    exit();
}

$currentDriverId = $_SESSION['user_id']; 

$stmt = $pdo->prepare("
    SELECT d.*, cs.chat_token, cs.driver_notified 
    FROM deliveries d
    JOIN chat_sessions cs ON d.id = cs.delivery_id
    WHERE cs.chosen_driver_id = ? AND d.status != 'completed'
    ORDER BY cs.driver_notified ASC, d.created_at DESC
");
// שימוש ב-ID במקום בשם
$stmt->execute([$currentDriverId]); 
$myRoutes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>המסלולים שלי - MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Assistant:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Assistant', sans-serif; }
        .notification-pulse {
            animation: pulse-red 2s infinite;
        }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
            100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
    </style>
</head>
<body class="bg-slate-50 min-h-screen text-slate-900">

    <div class="max-w-2xl mx-auto p-6 lg:p-12">
        <div class="flex items-center justify-between mb-10">
            <div>
                <h1 class="text-3xl font-extrabold tracking-tight text-slate-900">המסלולים שלי</h1>
                <p class="text-slate-500 text-sm mt-1">נהל את ההובלות המאושרות שלך</p>
            </div>
            
            <div class="flex items-center gap-3">
                <a href="driver_history.php" class="flex items-center gap-2 bg-white text-slate-600 px-4 py-2 rounded-2xl text-xs font-bold shadow-sm border border-slate-200 hover:text-indigo-600 transition-all">
                    <i class="fas fa-history"></i>
                    היסטוריה
                </a>
                
                <button onclick="history.go(-1)" class="bg-white text-slate-600 h-10 w-10 flex items-center justify-center rounded-full shadow-sm border border-slate-200 hover:text-indigo-600 transition-all cursor-pointer">
                    <i class="fas fa-arrow-left"></i>
                </button>
            </div>
        </div>

        <?php if (empty($myRoutes)): ?>
            <div class="bg-white p-16 rounded-[2rem] shadow-sm border border-slate-100 text-center">
                <div class="bg-slate-50 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-map-marked-alt text-slate-300 text-2xl"></i>
                </div>
                <h2 class="text-lg font-bold text-slate-800">אין מסלולים פעילים כרגע</h2>
                <p class="text-slate-400 text-sm mt-2">כאשר תזכה בהצעה, פרטי המסלול יופיעו כאן.</p>
            </div>
        <?php else: ?>
            <div class="space-y-6">
                <?php foreach ($myRoutes as $route): ?>
                    <?php $isNew = ($route['driver_notified'] == 0); ?>
                    
                    <div class="relative bg-white p-6 rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border <?php echo $isNew ? 'border-red-100 bg-red-50/30' : 'border-slate-50'; ?> transition-all hover:shadow-[0_8px_30px_rgb(0,0,0,0.08)]">
                        
                        <?php if ($isNew): ?>
                            <div class="absolute -top-2 -left-2 bg-red-500 text-white text-[10px] font-black px-3 py-1 rounded-full shadow-lg notification-pulse">
                                משלוח חדש
                            </div>
                        <?php endif; ?>

                        <div class="flex items-start justify-between mb-6 text-right">
                            <div class="flex gap-4">
                                <div class="<?php echo $isNew ? 'bg-red-500 text-white' : 'bg-emerald-500/10 text-emerald-600'; ?> w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 transition-colors">
                                    <i class="fas <?php echo $isNew ? 'fa-bell animate-bounce' : 'fa-check-double'; ?> text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest <?php echo $isNew ? 'text-red-500' : 'text-emerald-600'; ?> mb-1">
                                        <?php echo $isNew ? 'היכנס כדי לסגור משלוח' : 'משלוח ממתין'; ?>
                                    </p>
                                    <h3 class="font-bold text-slate-800 leading-tight">
                                        <?php echo htmlspecialchars($route['pickup_location']); ?>
                                        <i class="fas fa-long-arrow-alt-left mx-2 text-slate-300"></i>
                                        <?php echo htmlspecialchars($route['delivery_location']); ?>
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <a href="../includes/mark_as_read.php?token=<?php echo $route['chat_token']; ?>&redirect=../public/success.php" 
                               class="flex items-center justify-center gap-2 <?php echo $isNew ? 'bg-red-600 hover:bg-red-700' : 'bg-slate-900 hover:bg-slate-800'; ?> text-white py-3 px-4 rounded-2xl text-xs font-bold transition active:scale-95">
                                <i class="fas fa-user-circle opacity-70"></i>
                                פרטי לקוח
                            </a>

                            <a href="chat.php?token=<?php echo $route['chat_token']; ?>" 
                               class="flex items-center justify-center gap-2 bg-indigo-50 text-indigo-600 py-3 px-4 rounded-2xl text-xs font-bold hover:bg-indigo-100 transition active:scale-95">
                                <i class="fas fa-comment-alt opacity-70"></i>
                                פתח צ'אט
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

</body>
</html>