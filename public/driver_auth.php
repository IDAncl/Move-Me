<?php
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['is_driver'] == 1) {
    header("Location: ItaiRegisteredDriver.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>כניסת נהגים | MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Assistant:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Assistant', sans-serif; }
        .tab-active { background: white; box-shadow: 0 4px 12px rgba(0,0,0,0.05); color: #4f46e5; }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">

    <div class="max-w-md w-full">
        <div class="bg-white rounded-[2.5rem] shadow-xl p-10 border border-slate-100 mb-6 transition-all duration-500">
            
            <div id="step-identify">
                <div class="text-center mb-8">
                    <div class="bg-indigo-600 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-indigo-100">
                        <i class="fas fa-truck-moving text-white text-2xl"></i>
                    </div>
                    <h1 class="text-2xl font-black text-slate-900">פורטל נהגים</h1>
                    <p class="text-slate-500 font-medium mt-1">התחבר או הירשם עם מספר הטלפון</p>
                </div>

                <div class="space-y-4">
                    <div class="flex bg-slate-100 p-1.5 rounded-2xl mb-6">
                        <button onclick="toggleMode('login')" id="tab-login" class="flex-1 py-2.5 rounded-xl font-bold text-sm transition-all tab-active">התחברות</button>
                        <button onclick="toggleMode('signup')" id="tab-signup" class="flex-1 py-2.5 rounded-xl font-bold text-sm text-slate-500 transition-all">הרשמה</button>
                    </div>

                    <div id="signup-fields" class="hidden space-y-4">
                        <input type="text" id="reg_name" placeholder="שם מלא" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none font-bold text-right focus:border-indigo-500 focus:bg-white transition-all">
                        <div class="relative">
                            <select id="reg_vehicle" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none font-bold appearance-none text-right focus:border-indigo-500 focus:bg-white transition-all">
                                <option value="">סוג הרכב שלך...</option>
                                <option value="Motorcycle">אופנוע</option>
                                <option value="Car/SUV">רכב פרטי / SUV</option>
                                <option value="Van">מסחרית / טרנזיט</option>
                                <option value="Truck">משאית (עד 7.5 טון)</option>
                            </select>
                            <i class="fas fa-chevron-down absolute left-5 top-1/2 -translate-y-1/2 text-slate-400 text-xs pointer-events-none"></i>
                        </div>
                    </div>

                    <div class="relative">
                        <input type="tel" id="auth_phone" placeholder="מספר טלפון (לדוגמה: 0501234567)" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none font-bold text-right focus:border-indigo-500 focus:bg-white transition-all">
                        <i class="fas fa-phone absolute left-5 top-1/2 -translate-y-1/2 text-slate-200"></i>
                    </div>
                    
                    <button id="send-btn" onclick="sendCode()" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-xs tracking-widest uppercase hover:bg-indigo-600 transition active:scale-95 flex items-center justify-center gap-3">
                        שלח לי קוד <i class="fas fa-paper-plane text-[10px]"></i>
                    </button>
                </div>
            </div>

            <div id="step-verify" class="hidden">
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-black text-slate-900">אימות טלפון</h1>
                    <p class="text-slate-500 font-medium mt-1 italic">הזן את הקוד ששלחנו לך לוואטסאפ/SMS</p>
                </div>

                <div class="space-y-6">
                    <input type="text" id="verify_code" placeholder="000000" maxlength="6" class="w-full text-center text-3xl tracking-[0.5em] px-5 py-6 bg-slate-50 border border-slate-100 rounded-3xl outline-none font-black text-indigo-600 focus:bg-white transition-all">
                    
                    <button onclick="verifyCode()" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black text-xs tracking-widest uppercase hover:bg-slate-900 transition active:scale-95 shadow-lg shadow-indigo-100">
                        אישור וכניסה למערכת
                    </button>
                    
                    <button onclick="location.reload()" class="w-full text-slate-400 font-bold text-xs uppercase tracking-widest hover:text-slate-600 transition">חזור אחורה</button>
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="index.php" class="inline-flex items-center gap-2 text-slate-400 hover:text-indigo-600 font-bold text-xs uppercase tracking-widest transition">
                 חזרה לדף הבית
                <i class="fas fa-house"></i>
            </a>
        </div>
    </div>

    <script>
        let currentMode = 'login';

        function toggleMode(mode) {
            currentMode = mode;
            const signupFields = document.getElementById('signup-fields');
            const tabLogin = document.getElementById('tab-login');
            const tabSignup = document.getElementById('tab-signup');

            if(mode === 'signup') {
                signupFields.classList.remove('hidden');
                tabSignup.classList.add('tab-active');
                tabSignup.classList.remove('text-slate-500');
                tabLogin.classList.remove('tab-active');
                tabLogin.classList.add('text-slate-500');
            } else {
                signupFields.classList.add('hidden');
                tabLogin.classList.add('tab-active');
                tabLogin.classList.remove('text-slate-500');
                tabSignup.classList.remove('tab-active');
                tabSignup.classList.add('text-slate-500');
            }
        }

        async function sendCode() {
            const btn = document.getElementById('send-btn');
            const phone = document.getElementById('auth_phone').value;
            const name = document.getElementById('reg_name').value;
            const vehicle = document.getElementById('reg_vehicle').value;

            if(!phone) return alert("אנא הזן מספר טלפון");
            if(currentMode === 'signup' && (!name || !vehicle)) return alert("אנא מלא את כל השדות");

            btn.innerHTML = '<i class="fas fa-circle-notch animate-spin"></i> שולח...';
            btn.disabled = true;

            try {
                const res = await fetch('auth_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=send_code&phone=${phone}&name=${name}&vehicle=${vehicle}&mode=${currentMode}`
                });
                const data = await res.json();
                
                if(data.success) {
                    document.getElementById('step-identify').classList.add('hidden');
                    document.getElementById('step-verify').classList.remove('hidden');
                } else {
                    alert(data.message);
                    btn.innerHTML = 'שלח לי קוד <i class="fas fa-paper-plane text-[10px]"></i>';
                    btn.disabled = false;
                }
            } catch(e) {
                alert("שגיאת שרת, נסה שוב");
                btn.disabled = false;
            }
        }

        async function verifyCode() {
            const phone = document.getElementById('auth_phone').value;
            const code = document.getElementById('verify_code').value;

            try {
                const res = await fetch('auth_handler.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: `action=verify_code&phone=${phone}&code=${code}`
                });

                const data = await res.json();

                if(data.success) {
                    window.location.href = 'ItaiRegisteredDriver.php';
                } else {
                    alert("קוד לא תקין, נסה שוב");
                }
            } catch (error) {
                alert("שגיאה באימות");
            }
        }
    </script>
</body>
</html>