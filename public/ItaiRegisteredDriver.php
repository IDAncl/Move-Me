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
    $whereClauses[] = "d.region = :region";
    $params[':region'] = $_GET['region'];
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
        echo '<div class="bg-white p-8 rounded-3xl text-center border-2 border-dashed border-slate-200 col-span-full">
                <i class="fas fa-search text-3xl text-slate-300 mb-4"></i>
                <p class="text-slate-500 font-bold text-sm italic">לא נמצאו הובלות שתואמות לחיפוש שלך.</p>
              </div>';
    } else {
        foreach ($deliveries as $job): 
            $wazeUrl = "https://waze.com/ul?q=" . urlencode($job['pickup_location']) . "&navigate=yes";
            $chatUrl = "chat.php?token=" . urlencode($job['chat_token']); 
            $formattedDate = date('d/m/Y', strtotime($job['moving_date']));
            $formattedTime = date('H:i', strtotime($job['preferred_time']));
            ?>
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden p-5 hover:shadow-md transition-all duration-300">
                <div class="flex justify-between items-center mb-4">
                    <span class="bg-indigo-50 text-indigo-600 text-[10px] font-black px-3 py-1 rounded-lg uppercase tracking-wider">
                        <?php echo htmlspecialchars($job['object_type'] ?? 'הובלה כללית'); ?>
                    </span>
                    <div class="text-left">
                        <div class="flex items-center gap-1 text-slate-400">
                            <i class="far fa-calendar text-[10px]"></i>
                            <span class="text-[11px] font-bold"><?php echo $formattedDate; ?></span>
                        </div>
                        <div class="flex items-center gap-1 text-indigo-600">
                            <i class="far fa-clock text-[10px]"></i>
                            <span class="text-[11px] font-black"><?php echo $formattedTime; ?></span>
                        </div>
                    </div>
                </div>

                <div class="relative space-y-6 mb-6">
                    <div class="absolute right-[11px] top-3 w-[2px] h-[calc(100%-24px)] bg-slate-100"></div>
                    
                    <div class="flex gap-4 items-start relative">
                        <div class="w-6 h-6 rounded-full bg-white border-4 border-slate-100 shadow-sm flex items-center justify-center z-10">
                            <div class="w-1.5 h-1.5 rounded-full bg-slate-400"></div>
                        </div>
                        <div class="flex-1">
                            <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest mb-0.5">איסוף</p>
                            <h3 class="text-sm font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($job['pickup_location']); ?></h3>
                            <a href="<?php echo $wazeUrl; ?>" target="_blank" class="inline-flex items-center gap-1.5 mt-2 text-indigo-500 text-[10px] font-black uppercase">
                                <i class="fab fa-waze"></i> ניווט בוויז
                            </a>
                        </div>
                    </div>

                    <div class="flex gap-4 items-start relative">
                        <div class="w-6 h-6 rounded-full bg-white border-4 border-indigo-50 shadow-sm flex items-center justify-center z-10">
                            <div class="w-1.5 h-1.5 rounded-full bg-indigo-600"></div>
                        </div>
                        <div class="flex-1">
                            <p class="text-[9px] font-black text-indigo-400 uppercase tracking-widest mb-0.5">יעד</p>
                            <h3 class="text-sm font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($job['delivery_location']); ?></h3>
                        </div>
                    </div>
                </div>

                <a href="<?php echo $chatUrl; ?>" class="w-full bg-slate-900 text-white py-3.5 rounded-xl font-bold text-[11px] uppercase hover:bg-indigo-600 transition active:scale-95 flex items-center justify-center gap-2">
                    הצעת מחיר וצ'אט <i class="fas fa-chevron-left text-[9px]"></i>
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
    <title>לוח בקרה | MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Assistant', sans-serif; -webkit-tap-highlight-color: transparent; }
        .sidebar-transition { transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        input, select { font-size: 16px !important; } /* מונע זום ב-iOS */
    </style>
</head>
<body class="bg-slate-50 min-h-[100dvh]">

    <div class="md:hidden bg-white/90 backdrop-blur-md border-b px-5 py-4 flex justify-between items-center sticky top-0 z-40">
        <div class="text-xl font-black text-indigo-600 italic">MoveMe</div>
        <button id="menuBtn" class="w-10 h-10 flex items-center justify-center bg-slate-100 rounded-xl text-slate-600 active:scale-90 transition">
            <i class="fas fa-bars-staggered"></i>
        </button>
    </div>

    <aside id="sidebar" class="fixed inset-y-0 right-0 w-72 bg-slate-900 text-white z-50 transform translate-x-full md:translate-x-0 sidebar-transition flex flex-col shadow-2xl md:shadow-none">
        <div class="p-8">
            <div class="text-2xl font-black mb-12 flex items-center gap-3">
                <i class="fas fa-truck-fast text-indigo-400"></i> MoveMe
            </div>
            <nav class="space-y-2">
                <a href="#" class="flex items-center gap-4 p-4 rounded-2xl bg-white/10 border-r-4 border-indigo-400 font-bold text-sm">
                    <i class="fas fa-th-large w-5 text-indigo-400"></i> לוח בקרה
                </a>
                <a href="my_routes.php" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-white/5 font-bold text-sm text-slate-300 transition-all">
                    <i class="fas fa-route w-5"></i> המסלולים שלי
                </a>
            </nav>
        </div>
        <div class="mt-auto p-8">
            <a href="logout.php" class="flex items-center gap-3 p-4 bg-red-500/10 text-red-400 rounded-2xl font-bold text-sm">
                <i class="fas fa-power-off"></i> התנתקות
            </a>
        </div>
    </aside>

    <div id="overlay" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden md:hidden"></div>

    <main class="md:mr-72 p-5 md:p-10">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 italic">שלום, <?php echo htmlspecialchars($driverName); ?>! 👋</h1>
                <p class="text-slate-500 text-sm font-medium">הנה ההובלות המחכות לך</p>
            </div>
            <div class="flex items-center gap-3 bg-white p-2 pl-5 rounded-2xl shadow-sm border border-slate-100">
                <img src="<?php echo $profilePic; ?>" class="w-9 h-9 rounded-xl shadow-inner">
                <div class="text-right">
                    <p class="text-[8px] font-black uppercase text-indigo-500 leading-none mb-1">נהג מאומת</p>
                    <p class="text-xs font-bold text-slate-700"><?php echo htmlspecialchars($driverName); ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-100 mb-8">
            <form id="filterForm" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 mb-1.5 block mr-1">תאריך</label>
                        <input type="date" name="search_date" value="<?php echo htmlspecialchars($_GET['search_date'] ?? ''); ?>" 
                               class="w-full bg-slate-50 border-none rounded-xl p-3.5 text-sm font-bold focus:ring-2 focus:ring-indigo-500 text-right">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 mb-1.5 block mr-1">שעה</label>
                        <input type="time" name="search_time" value="<?php echo htmlspecialchars($_GET['search_time'] ?? ''); ?>"
                               class="w-full bg-slate-50 border-none rounded-xl p-3.5 text-sm font-bold focus:ring-2 focus:ring-indigo-500 text-right">
                    </div>
                    <div>
                        <label class="text-[10px] font-black uppercase text-slate-400 mb-1.5 block mr-1">אזור</label>
                        <select name="region" class="w-full bg-slate-50 border-none rounded-xl p-3.5 text-sm font-bold focus:ring-2 focus:ring-indigo-500 appearance-none">
                            <option value="">כל הארץ</option>
                            <option value="North" <?php echo ($_GET['region'] ?? '') == 'North' ? 'selected' : ''; ?>>צפון</option>
                            <option value="Center" <?php echo ($_GET['region'] ?? '') == 'Center' ? 'selected' : ''; ?>>מרכז</option>
                            <option value="East" <?php echo ($_GET['region'] ?? '') == 'East' ? 'selected' : ''; ?>>שפלה</option>
                            <option value="South" <?php echo ($_GET['region'] ?? '') == 'South' ? 'selected' : ''; ?>>דרום</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white font-black text-[10px] uppercase py-2.5 rounded-xl shadow-md shadow-indigo-100 active:scale-95 transition-all">
                    <i class="fas fa-filter ml-1 text-[9px]"></i> עדכן תוצאות
                </button>
            </form>
        </div>

        <div id="jobs-container" class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            </div>
        
        <div class="mt-8 text-center">
            <span id="refresh-status" class="text-[9px] font-black text-slate-300 uppercase tracking-widest italic flex items-center justify-center gap-2">
                <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span>
                מחפש הובלות חדשות בזמן אמת...
            </span>
        </div>
    </main>

    <script>
        // Sidebar Logic
        const menuBtn = document.getElementById('menuBtn');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');

        const toggleSidebar = () => {
            sidebar.classList.toggle('translate-x-full');
            overlay.classList.toggle('hidden');
            document.body.classList.toggle('overflow-hidden');
        };

        menuBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

        // AJAX Refresh
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
                status.innerHTML = `<span class="w-1.5 h-1.5 bg-emerald-400 rounded-full"></span> עדכון אחרון: ${new Date().toLocaleTimeString('he-IL', {hour: '2-digit', minute:'2-digit'})}`;
            } catch (error) {
                status.innerText = "שגיאת חיבור...";
            }
        }

        setInterval(refreshJobs, 10000);
        refreshJobs();
    </script>
</body>
</html>