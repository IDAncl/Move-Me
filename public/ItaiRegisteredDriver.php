<?php
require_once '../includes/Itaidbh.inc.php'; 

// הגדרת סשן ל-30 יום
$timeout = 60 * 60 * 24 * 30; 
ini_set('session.gc_maxlifetime', $timeout);
session_set_cookie_params($timeout);
session_start();

// בדיקת הרשאות נהג
if (!isset($_SESSION['user_id']) || $_SESSION['is_driver'] != 1) {
    header("Location: driver_auth.php");
    exit();
}

// פונקציית עזר לזמן נסיעה (Google Maps)
function getTravelTime($origin, $destination, $moveDate, $moveTime) {
    $apiKey = 'YOUR_GOOGLE_MAPS_API_KEY'; 
    $departureTimestamp = strtotime("$moveDate $moveTime");
    if ($departureTimestamp < time()) $departureTimestamp = time();

    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . urlencode($origin) . 
           "&destinations=" . urlencode($destination) . 
           "&departure_time=" . $departureTimestamp . 
           "&traffic_model=best_guess&key=" . $apiKey;

    $response = @file_get_contents($url);
    $data = json_decode($response, true);
    return ($data && $data['status'] === 'OK') ? ($data['rows'][0]['elements'][0]['duration_in_traffic']['text'] ?? 'N/A') : 'N/A';
}

// --- לוגיקת חיפוש וסינון ---
$whereClauses = ["(cs.is_active = 1 OR cs.is_active IS NULL)"];
$params = [];

if (!empty($_GET['search_date'])) {
    $whereClauses[] = "d.moving_date = :search_date";
    $params[':search_date'] = $_GET['search_date'];
}

if (!empty($_GET['search_time'])) {
    $searchTime = $_GET['search_time'];
    $startTime = date('H:i:s', strtotime($searchTime . ' -15 minutes'));
    $endTime = date('H:i:s', strtotime($searchTime . ' +15 minutes'));
    $whereClauses[] = "d.preferred_time BETWEEN :start_time AND :end_time";
    $params[':start_time'] = $startTime;
    $params[':end_time'] = $endTime;
}

if (!empty($_GET['region'])) {
    $whereClauses[] = "d.pickup_location LIKE :region";
    $params[':region'] = '%' . $_GET['region'] . '%';
}

$whereSql = implode(" AND ", $whereClauses);

try {
    $sql = "SELECT d.*, cs.chat_token 
            FROM deliveries d 
            LEFT JOIN chat_sessions cs ON d.id = cs.delivery_id 
            WHERE $whereSql
            ORDER BY d.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $deliveries = [];
}

// --- לוגיקת רענון AJAX ---
if (isset($_GET['fetch_only'])) {
    if (empty($deliveries)) {
        echo '<div class="bg-white p-12 rounded-[2.5rem] text-center border-2 border-dashed border-slate-200 col-span-full">
                <i class="fas fa-search text-4xl text-slate-300 mb-4"></i>
                <p class="text-slate-500 font-bold italic">לא נמצאו הובלות שתואמות לחיפוש שלך.</p>
              </div>';
    } else {
        foreach ($deliveries as $job): 
            $wazeUrl = "https://waze.com/ul?q=" . urlencode($job['pickup_location']) . "&navigate=yes";
            $chatUrl = "chat.php?token=" . urlencode($job['chat_token']); 
            $formattedDate = date('d/m/Y', strtotime($job['moving_date']));
            $formattedTime = date('H:i', strtotime($job['preferred_time']));
            ?>
            <div class="bg-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-slate-50 overflow-hidden p-6 hover:shadow-lg transition-all duration-300">
                <div class="flex justify-between items-start mb-6">
                    <span class="bg-indigo-50 text-indigo-600 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                        <?php echo htmlspecialchars($job['object_type'] ?? 'הובלה כללית'); ?>
                    </span>
                    <div class="flex flex-col items-end gap-1">
                        <div class="flex items-center gap-2 text-slate-400">
                            <i class="far fa-calendar text-[10px]"></i>
                            <span class="text-[11px] font-bold text-slate-600"><?php echo $formattedDate; ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-indigo-600">
                            <i class="far fa-clock text-[10px]"></i>
                            <span class="text-[11px] font-black"><?php echo $formattedTime; ?></span>
                        </div>
                    </div>
                </div>

                <div class="relative space-y-8 mb-8">
                    <div class="absolute right-[11px] top-3 w-[2px] h-[calc(100%-24px)] bg-slate-100"></div>
                    
                    <div class="flex gap-4 items-start relative">
                        <div class="w-6 h-6 rounded-full bg-white border-4 border-slate-100 shadow-sm flex items-center justify-center z-10">
                            <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                        </div>
                        <div class="flex-1">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">איסוף מ-</p>
                            <h3 class="text-base font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($job['pickup_location']); ?></h3>
                            <a href="<?php echo $wazeUrl; ?>" target="_blank" class="inline-flex items-center gap-1.5 mt-2 text-indigo-600 text-[11px] font-black uppercase hover:opacity-70">
                                <i class="fab fa-waze text-sm"></i> ניווט ליעד
                            </a>
                        </div>
                    </div>

                    <div class="flex gap-4 items-start relative">
                        <div class="w-6 h-6 rounded-full bg-white border-4 border-indigo-50 shadow-sm flex items-center justify-center z-10">
                            <div class="w-2 h-2 rounded-full bg-indigo-600"></div>
                        </div>
                        <div class="flex-1">
                            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest mb-1">יעד סופי</p>
                            <h3 class="text-base font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($job['delivery_location']); ?></h3>
                        </div>
                    </div>
                </div>

                <a href="<?php echo $chatUrl; ?>" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-xs tracking-[0.2em] uppercase hover:bg-indigo-600 transition active:scale-95 flex items-center justify-center gap-2">
                    הצעת מחיר וצ'אט <i class="fas fa-chevron-left text-[10px]"></i>
                </a>
            </div>
        <?php endforeach;
    }
    exit; 
}

$driverName = $_SESSION['user_name'] ?? 'נהג';
$profilePic = "https://ui-avatars.com/api/?name=" . urlencode($driverName) . "&background=6366f1&color=fff&bold=true"; 
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>לוח בקרה נהג | MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Assistant:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Assistant', sans-serif; background-color: #f8fafc; }
        .glass-sidebar { background: #1e293b; }
        .active-nav { background: rgba(255, 255, 255, 0.1); border-right: 4px solid #6366f1; }
    </style>
</head>
<body class="min-h-screen">

    <div class="md:hidden bg-white/80 backdrop-blur-md border-b px-6 py-4 flex justify-between items-center sticky top-0 z-50">
        <div class="flex items-center gap-2 text-xl font-black text-indigo-600">
            <i class="fas fa-truck-fast"></i> MoveMe
        </div>
        <button id="menuBtn" class="w-10 h-10 flex items-center justify-center bg-slate-100 rounded-xl text-slate-600">
            <i class="fas fa-bars-staggered"></i>
        </button>
    </div>

    <aside id="sidebar" class="glass-sidebar w-72 text-white flex flex-col fixed h-full z-40 transform translate-x-full transition-all duration-300 md:translate-x-0 shadow-2xl">
        <div class="p-8">
            <div class="flex items-center gap-3 text-2xl font-black mb-12">
                <i class="fas fa-truck-fast text-indigo-400"></i> MoveMe
            </div>
            <nav class="space-y-2">
                <a href="#" class="active-nav flex items-center gap-4 p-4 rounded-2xl transition-all">
                    <i class="fas fa-th-large w-5 text-indigo-400"></i> 
                    <span class="font-bold text-sm">לוח בקרה</span>
                </a>
                <a href="my_routes.php" class="flex items-center gap-4 p-4 hover:bg-white/5 rounded-2xl group transition-all">
                    <i class="fas fa-route w-5 text-slate-400 group-hover:text-white"></i> 
                    <span class="font-bold text-sm text-slate-300 group-hover:text-white">המסלולים שלי</span>
                </a>
                <a href="#" class="flex items-center gap-4 p-4 hover:bg-white/5 rounded-2xl group transition-all">
                    <i class="fas fa-envelope w-5 text-slate-400 group-hover:text-white"></i> 
                    <span class="font-bold text-sm text-slate-300 group-hover:text-white">הודעות</span>
                </a>
            </nav>
        </div>
        <div class="mt-auto p-8">
            <a href="logout.php" class="flex items-center gap-4 p-4 bg-red-500/10 text-red-400 rounded-2xl hover:bg-red-500 hover:text-white transition-all font-bold text-sm">
                <i class="fas fa-power-off"></i> התנתקות מהמערכת
            </a>
        </div>
    </aside>

    <div id="overlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-30 hidden"></div>

    <main class="md:mr-72 p-6 md:p-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-10 gap-6 text-right">
            <div>
                <h1 class="text-3xl font-black text-slate-900 tracking-tight italic">שלום, <?php echo htmlspecialchars($driverName); ?>! 👋</h1>
                <p class="text-slate-500 font-medium">הנה ההובלות הזמינות עבורך היום</p>
            </div>
            <div class="flex items-center gap-4 bg-white p-2 pl-6 rounded-3xl shadow-sm border border-slate-100">
                <img src="<?php echo $profilePic; ?>" class="w-10 h-10 rounded-2xl" alt="פרופיל">
                <div class="text-right">
                    <p class="text-[10px] font-black uppercase text-slate-400 leading-none">סטטוס: מאומת</p>
                    <p class="text-sm font-bold text-slate-800"><?php echo htmlspecialchars($driverName); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-[2.5rem] shadow-sm border border-slate-100 mb-10">
            <form id="filterForm" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block mr-1">תאריך</label>
                    <input type="date" name="search_date" value="<?php echo htmlspecialchars($_GET['search_date'] ?? ''); ?>" 
                           class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 text-right">
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block mr-1">שעה</label>
                    <input type="time" name="search_time" value="<?php echo htmlspecialchars($_GET['search_time'] ?? ''); ?>"
                           class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 text-right">
                </div>
                <div>
                    <label class="text-[10px] font-black uppercase text-slate-400 mb-2 block mr-1">אזור</label>
                    <select name="region" class="w-full bg-slate-50 border-none rounded-2xl p-4 text-sm font-bold focus:ring-2 focus:ring-indigo-500 appearance-none">
                        <option value="">כל הארץ</option>
                        <option value="North" <?php echo ($_GET['region'] ?? '') == 'North' ? 'selected' : ''; ?>>צפון</option>
                        <option value="Center" <?php echo ($_GET['region'] ?? '') == 'Center' ? 'selected' : ''; ?>>מרכז</option>
                        <option value="South" <?php echo ($_GET['region'] ?? '') == 'South' ? 'selected' : ''; ?>>דרום</option>
                    </select>
                </div>
                <button type="submit" class="bg-indigo-600 text-white font-black text-xs uppercase py-4 px-6 rounded-2xl hover:bg-slate-900 transition-all shadow-xl active:scale-95">
                    <i class="fas fa-sliders ml-2"></i> החל סינון
                </button>
            </form>
        </div>

        <div id="jobs-container" class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            </div>
        
        <div class="mt-8 text-center">
            <span id="refresh-status" class="text-[10px] font-black text-slate-300 uppercase tracking-widest italic">מעקב אחר הובלות חדשות...</span>
        </div>
    </main>

    <script>
        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        menuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('translate-x-full');
            overlay.classList.toggle('hidden');
        });

        overlay.addEventListener('click', () => {
            sidebar.classList.add('translate-x-full');
            overlay.classList.add('hidden');
        });

        async function refreshJobs() {
            const container = document.getElementById('jobs-container');
            const status = document.getElementById('refresh-status');
            
            try {
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('fetch_only', '1');
                
                const response = await fetch('ItaiRegisteredDriver.php?' + urlParams.toString());
                const html = await response.text();
                
                if (container.innerHTML !== html) {
                    container.innerHTML = html;
                }
                status.innerText = "מעקב חי פעיל • עודכן ב-" + new Date().toLocaleTimeString('he-IL', {hour: '2-digit', minute:'2-digit'});
            } catch (error) {
                status.innerText = "שגיאה בסנכרון. מנסה שוב...";
            }
        }

        setInterval(refreshJobs, 8000);
        refreshJobs();
    </script>
</body>
</html>