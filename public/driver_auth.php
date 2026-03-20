<?php
session_start();
if (isset($_SESSION['user_id']) && $_SESSION['is_driver'] == 1) {
    header("Location: ItaiRegisteredDriver.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Driver Access | MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-6">

    <div class="max-w-md w-full bg-white rounded-[2.5rem] shadow-xl p-10 border border-slate-100">
        
        <div id="step-identify">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-black text-slate-900">Driver Portal</h1>
                <p class="text-slate-500 font-medium mt-1">Signup or Login with your phone</p>
            </div>

            <div class="space-y-4">
                <div class="flex bg-slate-100 p-1 rounded-2xl mb-6">
                    <button onclick="toggleMode('login')" id="tab-login" class="flex-1 py-2 rounded-xl font-bold text-sm bg-white shadow-sm text-indigo-600 transition-all">Login</button>
                    <button onclick="toggleMode('signup')" id="tab-signup" class="flex-1 py-2 rounded-xl font-bold text-sm text-slate-500 transition-all">Signup</button>
                </div>

                <div id="signup-fields" class="hidden space-y-4">
                    <input type="text" id="reg_name" placeholder="Full Name" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none font-bold">
                    <select id="reg_vehicle" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none font-bold appearance-none">
                        <option value="">Vehicle Type...</option>
                        <option value="Motorcycle">Motorcycle</option>
                        <option value="Car/SUV">Car / SUV</option>
                        <option value="Van">Van / Transporter</option>
                        <option value="Truck">Truck (up to 7.5t)</option>
                    </select>
                </div>

                <input type="tel" id="auth_phone" placeholder="Phone (e.g. 0501234567)" class="w-full px-5 py-4 bg-slate-50 border border-slate-100 rounded-2xl outline-none font-bold">
                
                <button onclick="sendCode()" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-xs tracking-widest uppercase hover:bg-indigo-600 transition active:scale-95 flex items-center justify-center gap-3">
                    Send Code <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <div id="step-verify" class="hidden">
            <div class="text-center mb-8">
                <h1 class="text-2xl font-black text-slate-900">Verify Phone</h1>
                <p class="text-slate-500 font-medium mt-1">Enter the 6-digit code sent to your SMS</p>
            </div>

            <div class="space-y-6">
                <input type="text" id="verify_code" placeholder="000000" maxlength="6" class="w-full text-center text-3xl tracking-[0.5em] px-5 py-6 bg-slate-50 border border-slate-100 rounded-3xl outline-none font-black text-indigo-600">
                
                <button onclick="verifyCode()" class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-black text-xs tracking-widest uppercase hover:bg-slate-900 transition active:scale-95">
                    Confirm & Login
                </button>
                
                <button onclick="location.reload()" class="w-full text-slate-400 font-bold text-xs uppercase tracking-widest">Back</button>
            </div>
        </div>

    </div>

    <script>
        let currentMode = 'login';

        function toggleMode(mode) {
            currentMode = mode;
            document.getElementById('signup-fields').classList.toggle('hidden', mode === 'login');
            document.getElementById('tab-login').className = mode === 'login' ? 'flex-1 py-2 rounded-xl font-bold text-sm bg-white shadow-sm text-indigo-600' : 'flex-1 py-2 rounded-xl font-bold text-sm text-slate-500';
            document.getElementById('tab-signup').className = mode === 'signup' ? 'flex-1 py-2 rounded-xl font-bold text-sm bg-white shadow-sm text-indigo-600' : 'flex-1 py-2 rounded-xl font-bold text-sm text-slate-500';
        }

        async function sendCode() {
            const phone = document.getElementById('auth_phone').value;
            const name = document.getElementById('reg_name').value;
            const vehicle = document.getElementById('reg_vehicle').value;

            if(!phone) return alert("Phone is required");
            if(currentMode === 'signup' && (!name || !vehicle)) return alert("Please fill all fields");

            const res = await fetch('auth_handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=send_code&phone=${phone}&name=${name}&vehicle=${vehicle}&mode=${currentMode}`
            });
            const data = await res.json();
            
            if(data.success) {
                document.getElementById('step-identify').classList.add('hidden');
                document.getElementById('step-verify').classList.remove('hidden');
                alert("Debug: Code is " + data.code); // Remove this line in production
            } else {
                alert(data.message);
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

            const text = await res.text(); // Get raw text first for debugging
            console.log("Raw Server Response:", text); 
            
            const data = JSON.parse(text); // Manually parse it

            if(data.success) {
                window.location.href = 'ItaiRegisteredDriver.php';
            } else {
                alert("Invalid code!");
            }
        } catch (error) {
            console.error("Verification Error:", error);
            alert("Server error! Check the console (F12).");
        }
    }
    </script>
</body>
</html>