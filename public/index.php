<?php 
    require_once '../includes/Itaidbh.inc.php';
    session_start();

    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_role'] = 'guest'; 
        $_SESSION['user_name'] = 'אורח_' . substr(uniqid(), -4);
    }
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>MoveMe - הזמנת הובלה</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Assistant:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Assistant', 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; overflow-x: hidden; }
        .gradient-bg { background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); }
        .glass-effect { background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(20px); }
        
        
        input, select, textarea { font-size: 16px !important; } 
        
        .floating-label-input:focus-within label { color: #4f46e5; }
        .custom-shadow { box-shadow: 0 20px 50px rgba(79, 70, 229, 0.1); }
        
        @keyframes subtle-float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-5px); }
            100% { transform: translateY(0px); }
        }
        .float-icon { animation: subtle-float 3s ease-in-out infinite; }
    </style>
</head>
<body class="min-h-screen">

    <div id="mobileOverlay" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] hidden transition-opacity duration-300 opacity-0"></div>

    <div id="mobileMenu" class="fixed top-0 left-0 w-[280px] h-full bg-white z-[70] -translate-x-full transition-transform duration-300 p-8 shadow-2xl">
        <div class="flex justify-between items-center mb-10">
            <div class="flex items-center gap-2 text-xl font-black text-indigo-600 tracking-tighter">
                <i class="fas fa-truck-fast"></i> MoveMe
            </div>
            <button id="closeMenu" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <nav class="space-y-6">
            <a href="driver_auth.php" class="flex items-center gap-4 p-4 bg-indigo-50 text-indigo-600 rounded-2xl font-extrabold text-sm transition-all active:scale-95">
                <i class="fas fa-id-card"></i> 
                כניסת נהגים
            </a>
            <hr class="border-slate-100">
        </nav>
    </div>

    <header class="bg-white/80 backdrop-blur-md sticky top-0 z-50 border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-6 h-16 flex justify-between items-center">
            <div class="flex items-center gap-2">
                <div class="bg-indigo-600 p-1.5 rounded-lg shadow-lg shadow-indigo-200">
                    <i data-lucide="truck" class="w-5 h-5 text-white"></i>
                </div>
                <span class="text-xl font-extrabold tracking-tighter text-slate-900">MoveMe</span>
            </div>
            
            <button id="menuBtn" class="md:hidden bg-slate-100 p-2 rounded-xl text-slate-600 hover:bg-slate-200 transition-colors">
                <i data-lucide="menu" class="w-5 h-5"></i>
            </button>

            <nav class="hidden md:flex items-center gap-8 text-sm font-bold text-slate-500">
                <a href="ItaiRegisteredDriver.php" class="bg-slate-900 text-white px-5 py-2 rounded-full hover:bg-indigo-600 transition shadow-lg shadow-slate-100">
                    פורטל נהגים
                </a>
            </nav>
        </div>
    </header>

    <div class="gradient-bg pt-16 pb-32 px-6 text-right">
        <div class="max-w-4xl mx-auto text-center text-white">
            <div class="inline-flex items-center gap-2 bg-white/10 px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest mb-6 border border-white/20">
                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                זמין באזור שלך
            </div>
            <h1 class="text-4xl md:text-6xl font-black mb-4 tracking-tight leading-tight">מעבירים הכל,<br class="md:hidden"> לכל מקום.</h1>
            <p class="text-indigo-100 text-lg font-medium opacity-90 max-w-xl mx-auto">תשכחו מהלחץ. נהגים מאומתים מוכנים להציע לכם מחיר על ההובלה ברגע זה.</p>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 -mt-20 pb-20">
        <div class="glass-effect rounded-[2.5rem] custom-shadow p-6 md:p-10 border border-white">
            <form id="bookingForm" action="../includes/ItaiSendNewDeliveryRequest.php" method="POST" class="space-y-8">
                
                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i data-lucide="map" class="w-4 h-4"></i>
                    </div>
                    <h2 class="font-black text-slate-800 uppercase text-[11px] tracking-widest">פרטי מסלול</h2>
                </div>

                <div class="grid md:grid-cols-2 gap-5">
                    <div class="space-y-1.5 floating-label-input">
                        <label class="block text-[11px] font-black text-slate-400 uppercase mr-1 text-right">נקודת איסוף</label>
                        <div class="relative group">
                            <i data-lucide="map-pin" class="absolute right-4 top-4 w-5 h-5 text-slate-300 group-focus-within:text-indigo-500 transition-colors"></i>
                            <input type="text" id="pickup" name="pickup" placeholder="בניין, רחוב, עיר" required
                                class="w-full pr-12 pl-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700 text-right">
                        </div>
                    </div>

                    <div class="space-y-1.5 floating-label-input">
                        <label class="block text-[11px] font-black text-slate-400 uppercase mr-1 text-right">יעד למסירה</label>
                        <div class="relative group">
                            <i data-lucide="navigation" class="absolute right-4 top-4 w-5 h-5 text-slate-300 group-focus-within:text-indigo-500 transition-colors"></i>
                            <input type="text" id="delivery" name="delivery" placeholder="לאן זה הולך?" required
                                class="w-full pr-12 pl-4 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700 text-right">
                        </div>
                    </div>
                </div>

                <hr class="border-slate-50">

                <div class="flex items-center gap-3 mb-2">
                    <div class="w-8 h-8 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600">
                        <i data-lucide="box" class="w-4 h-4"></i>
                    </div>
                    <h2 class="font-black text-slate-800 uppercase text-[11px] tracking-widest">לוגיסטיקה ופריטים</h2>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-black text-slate-400 uppercase mr-1 text-right">תאריך</label>
                        <input type="date" id="moveDate" name="moveDate" required
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700 text-right">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-black text-slate-400 uppercase mr-1 text-right">שעה</label>
                        <input type="time" id="moveTime" name="moveTime" required
                            class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700 text-right">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-[11px] font-black text-slate-400 uppercase mr-1 text-right">מה מובילים?</label>
                        <div class="relative group">
                            <input type="text" id="objectType" name="objectType" placeholder="למשל: ספה 3 מושבים, מקרר..." required
                                class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl focus:bg-white focus:ring-4 focus:ring-indigo-500/5 focus:border-indigo-500 outline-none transition-all font-bold text-slate-700 text-right">
                        </div>
                    </div>
                </div>

                <div class="p-6 bg-indigo-600 rounded-[2rem] text-white shadow-xl shadow-indigo-200">
                    <div class="grid md:grid-cols-2 gap-5 text-right">
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-indigo-200 uppercase tracking-widest">שם מלא</label>
                            <input type="text" id="fullName" name="fullName" placeholder="ישראל ישראלי" required
                                class="w-full px-5 py-4 bg-white/10 border border-white/20 rounded-xl focus:bg-white focus:text-slate-900 outline-none transition-all font-bold text-white placeholder:text-indigo-200 text-right">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-[10px] font-black text-indigo-200 uppercase tracking-widest">מספר טלפון</label>
                            <input type="tel" id="phoneNumber" name="phoneNumber" placeholder="050-000-0000" required
                                class="w-full px-5 py-4 bg-white/10 border border-white/20 rounded-xl focus:bg-white focus:text-slate-900 outline-none transition-all font-bold text-white placeholder:text-indigo-200 text-right">
                        </div>
                    </div>
                </div>

                <button type="submit" 
                    class="w-full bg-slate-900 text-white py-5 rounded-[2rem] font-black text-sm tracking-[0.2em] uppercase hover:bg-indigo-600 transition-all shadow-xl active:scale-95 flex items-center justify-center gap-3">
                    מצא לי נהג <i data-lucide="arrow-left" class="w-5 h-5"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        const today = new Date().toISOString().split('T')[0];
        document.getElementById('moveDate').setAttribute('min', today);

        const menuBtn = document.getElementById('menuBtn');
        const closeMenu = document.getElementById('closeMenu');
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileOverlay = document.getElementById('mobileOverlay');

        function toggleMenu() {
            const isHidden = mobileMenu.classList.contains('-translate-x-full');
            
            if (isHidden) {
                mobileOverlay.classList.remove('hidden');
                setTimeout(() => {
                    mobileOverlay.classList.add('opacity-100');
                    mobileMenu.classList.remove('-translate-x-full');
                    document.body.style.overflow = 'hidden';
                }, 10);
            } else {
                mobileMenu.classList.add('-translate-x-full');
                mobileOverlay.classList.remove('opacity-100');
                document.body.style.overflow = '';
                setTimeout(() => {
                    mobileOverlay.classList.add('hidden');
                }, 300);
            }
        }

        menuBtn.addEventListener('click', toggleMenu);
        closeMenu.addEventListener('click', toggleMenu);
        mobileOverlay.addEventListener('click', toggleMenu);
    </script>
</body>
</html>