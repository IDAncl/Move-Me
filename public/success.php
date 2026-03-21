<?php
session_start();
require_once '../includes/Itaidbh.inc.php';

$token = $_GET['token'] ?? '';
$status = $_GET['status'] ?? ''; 

$isDriverFlag = (isset($_SESSION['is_driver']) && $_SESSION['is_driver'] == 1);
$userRole = $isDriverFlag ? 'driver' : 'customer';

// --- שליפת נתונים מהדאטה-בייס ---
$stmt = $pdo->prepare("
    SELECT 
        d.full_name AS customer_name, 
        d.phone_number AS customer_phone, 
        d.pickup_location, 
        d.delivery_location,
        d.moving_date,
        d.preferred_time,
        d.status AS delivery_status,
        cs.chosen_driver_id AS session_driver_name,
        u.full_name AS user_table_name,
        u.phone AS driver_phone,
        (SELECT quote_price FROM chat_messages 
         WHERE chat_token = ? AND quote_price > 0 
         ORDER BY id DESC LIMIT 1) as final_price
    FROM chat_sessions cs 
    JOIN deliveries d ON cs.delivery_id = d.id 
    LEFT JOIN users u ON cs.chosen_driver_id = u.full_name  
    WHERE cs.chat_token = ?
");
$stmt->execute([$token, $token]);
$data = $stmt->fetch();

if (!$data) { die("ההזמנה לא נמצאה."); }

$displayPrice = $data['final_price'] ?? '0';
$isCompleted = ($data['delivery_status'] === 'completed');

// עיצוב תאריך ושעה לעברית
$formattedDate = date("d/m/Y", strtotime($data['moving_date']));
$formattedTime = date("H:i", strtotime($data['preferred_time']));

// --- לוגיקת עדכון סטטוס (סגירת הצ'אט) ---
if (!empty($token) && $status === 'paid') {
    try {
        $pdo->beginTransaction();
        $checkStmt = $pdo->prepare("SELECT is_active FROM chat_sessions WHERE chat_token = ?");
        $checkStmt->execute([$token]);
        $session = $checkStmt->fetch();

        if ($session && $session['is_active'] == 1) {
            $update = $pdo->prepare("UPDATE chat_sessions SET is_active = 0 WHERE chat_token = ?");
            $update->execute([$token]);

            $systemMsg = "🤝 ההזמנה אושרה בסכום של ₪$displayPrice.";
            $msgStmt = $pdo->prepare("INSERT INTO chat_messages (chat_token, sender_name, message) VALUES (?, 'System', ?)");
            $msgStmt->execute([$token, $systemMsg]);
            $pdo->commit();
        } else {
            if ($pdo->inTransaction()) $pdo->rollBack();
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
    }
}

// הגדרת פרטי השותף להובלה
$wazePickup = "https://waze.com/ul?q=" . urlencode($data['pickup_location']);
if ($userRole === 'customer') {
    $partnerHeader = "הנהג שלך";
    $partnerName = !empty($data['user_table_name']) ? $data['user_table_name'] : $data['session_driver_name'];
    $partnerPhone = $data['driver_phone'] ?? 'צור קשר עם התמיכה';
} else {
    $partnerHeader = "פרטי הלקוח";
    $partnerName = $data['customer_name'];
    $partnerPhone = $data['customer_phone'];
}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>סיכום הזמנה | MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Assistant:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Assistant', sans-serif; background-color: #f8fafc; }
        .glass-card { background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(8px); }
    </style>
</head>
<body class="min-h-screen flex flex-col items-center">

    <div class="w-full max-w-md bg-slate-900 p-4 text-white flex items-center justify-between shadow-lg">
        <span class="text-[10px] font-black uppercase tracking-widest">סיכום הובלה</span>
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold"><?php echo ($isCompleted) ? 'הובלה הושלמה' : (($status === 'paid') ? 'שולם ומאובטח' : 'ממתין לתשלום'); ?></span>
            <div class="w-2 h-2 rounded-full <?php echo ($isCompleted || $status === 'paid') ? 'bg-emerald-400' : 'bg-orange-400'; ?> animate-pulse"></div>
        </div>
    </div>

    <main class="w-full max-w-md px-5 py-8">
        
        <div class="glass-card rounded-[2.5rem] p-8 shadow-xl border border-white mb-6 text-center">
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-1">סכום סופי לסגירה</p>
            <p class="text-5xl font-black text-slate-900 mb-6 italic">₪<?php echo htmlspecialchars($displayPrice); ?></p>
            
            <div class="grid grid-cols-2 gap-3 border-t border-slate-100 pt-6">
                <div class="text-right border-l border-slate-100 pr-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">תאריך הובלה</p>
                    <p class="text-sm font-extrabold text-slate-700"><?php echo $formattedDate; ?></p>
                </div>
                <div class="text-left pl-2">
                    <p class="text-[10px] font-black text-slate-400 uppercase mb-1">שעת איסוף</p>
                    <p class="text-sm font-extrabold text-slate-700"><?php echo $formattedTime; ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-100 mb-6">
            <h2 class="text-[11px] font-black text-slate-400 uppercase tracking-widest mb-4"><?php echo $partnerHeader; ?></h2>
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-indigo-600 rounded-2xl flex items-center justify-center text-white text-xl font-bold">
                    <?php echo mb_substr($partnerName, 0, 1, 'UTF-8'); ?>
                </div>
                <div class="flex-1">
                    <h3 class="font-bold text-slate-900 leading-tight"><?php echo htmlspecialchars($partnerName); ?></h3>
                    <p class="text-indigo-600 font-bold text-xs"><?php echo htmlspecialchars($partnerPhone); ?></p>
                </div>
                <a href="tel:<?php echo $partnerPhone; ?>" class="w-11 h-11 bg-emerald-50 rounded-xl flex items-center justify-center text-emerald-500 border border-emerald-100 active:scale-90 transition">
                    <i class="fas fa-phone-alt text-sm"></i>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] p-8 shadow-sm border border-slate-100 mb-8">
            <div class="space-y-8 relative">
                <div class="absolute right-[11px] top-2 w-[1px] h-[calc(100%-16px)] bg-slate-100"></div>
                
                <div class="flex gap-4 items-start relative">
                    <div class="w-6 h-6 rounded-full bg-white border-4 border-slate-100 shadow-sm flex items-center justify-center z-10">
                        <div class="w-1.5 h-1.5 rounded-full bg-slate-300"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">כתובת איסוף</p>
                        <p class="text-sm font-bold text-slate-700 leading-snug"><?php echo htmlspecialchars($data['pickup_location']); ?></p>
                        <?php if ($userRole === 'driver'): ?>
                            <a href="<?php echo $wazePickup; ?>" target="_blank" class="mt-3 inline-flex items-center gap-2 bg-[#33CCFF] text-white px-4 py-2 rounded-xl font-black text-[10px] uppercase shadow-md active:scale-95 transition">
                                <i class="fab fa-waze"></i> ניווט ליעד
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="flex gap-4 items-start relative">
                    <div class="w-6 h-6 rounded-full bg-white border-4 border-indigo-50 shadow-sm flex items-center justify-center z-10">
                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-500"></div>
                    </div>
                    <div class="flex-1">
                        <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">כתובת יעד</p>
                        <p class="text-sm font-bold text-slate-700 leading-snug"><?php echo htmlspecialchars($data['delivery_location']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-3 pb-10">
            <?php if ($userRole === 'driver'): ?>
                <?php if (!$isCompleted): ?>
                    <button id="completeBtn" onclick="markAsCompleted()" 
                            class="flex items-center justify-center bg-emerald-600 text-white w-full py-5 rounded-[1.5rem] font-black text-sm tracking-widest uppercase shadow-lg shadow-emerald-100 active:scale-95 transition gap-3">
                        <i class="fas fa-check-double"></i>
                        סיימתי את ההובלה
                    </button>
                <?php else: ?>
                    <div class="flex items-center justify-center bg-slate-100 text-slate-400 w-full py-5 rounded-[1.5rem] font-black text-sm tracking-widest uppercase border border-slate-200">
                        הובלה הושלמה בהצלחה
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <a href="<?php echo ($userRole === 'customer') ? 'index.php' : 'ItaiRegisteredDriver.php'; ?>" 
               class="flex items-center justify-center bg-slate-900 text-white w-full py-5 rounded-[1.5rem] font-black text-sm tracking-widest uppercase shadow-xl active:scale-95 transition">
                חזרה למסך הבית
            </a>
        </div>

    </main>

    <p class="text-[10px] text-slate-300 font-bold uppercase tracking-[0.3em] pb-10">MoveMe - הובלה חכמה</p>

    <script>
    async function markAsCompleted() {
        if (!confirm('האם אתה בטוח שההובלה הסתיימה? הפעולה תעביר אותה להיסטוריה.')) return;

        const btn = document.getElementById('completeBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner animate-spin"></i> מעדכן...';

        const formData = new FormData();
        formData.append('token', '<?php echo $token; ?>');

        try {
            const res = await fetch('../includes/complete_delivery.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();

            if (data.status === 'success') {
                alert('ההובלה הושלמה בהצלחה! עובר להיסטוריה.');
                window.location.href = 'driver_history.php';
            } else {
                alert('שגיאה: ' + data.message);
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-double"></i> סיימתי את ההובלה';
            }
        } catch (e) {
            console.error(e);
            alert('תקלה בתקשורת עם השרת');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check-double"></i> סיימתי את ההובלה';
        }
    }
    </script>
</body>
</html>