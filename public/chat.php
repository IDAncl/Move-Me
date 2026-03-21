<?php
session_start();
require_once '../includes/Itaidbh.inc.php';

// 1. Get Token and Session Data
$token = $_GET['token'] ?? '';
$userName = $_SESSION['user_name'] ?? 'Guest';

// Fix: Check both possible session keys for the role
$isDriverFlag = (isset($_SESSION['is_driver']) && $_SESSION['is_driver'] == 1);
$userRole = $isDriverFlag ? 'driver' : 'customer';

// 2. Database Verification with detailed error handling
if (empty($token)) {
    die("Error: No chat token provided in the URL.");
}

$stmt = $pdo->prepare("
    SELECT d.pickup_location, d.delivery_location, cs.is_active, cs.chosen_driver_id 
    FROM chat_sessions cs 
    JOIN deliveries d ON cs.delivery_id = d.id 
    WHERE cs.chat_token = ?
");
$stmt->execute([$token]);
$session = $stmt->fetch();

if (!$session) {
    // Debug: Check if the token exists but the delivery is missing
    $check = $pdo->prepare("SELECT id FROM chat_sessions WHERE chat_token = ?");
    $check->execute([$token]);
    if ($check->fetch()) {
        die("Error: Chat exists, but the linked delivery record was not found.");
    }
    die("Error: This chat link is no longer valid or does not exist in our system.");
}

// 3. Handle closed sessions
if ((int)$session['is_active'] === 0 && !empty($session['chosen_driver_id'])) {
    echo "<script>alert('This job has been assigned. The chat is now closed.'); window.location.href='index.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat - MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>.no-scrollbar::-webkit-scrollbar { display: none; }</style>
</head>
<body class="bg-gray-50 h-screen flex flex-col font-sans">

    <div class="bg-indigo-600 text-white p-4 shadow-lg flex justify-between items-center sticky top-0 z-10">
        <div>
            <h1 class="font-bold text-lg leading-tight">Delivery Chat</h1>
            <p class="text-[10px] opacity-90 uppercase tracking-widest font-bold">
                <?php echo htmlspecialchars($session['pickup_location'] . " → " . $session['delivery_location']); ?>
            </p>
        </div>
        <a href="<?php echo ($userRole === 'driver') ? 'ItaiRegisteredDriver.php' : 'index.php'; ?>" class="hover:bg-white/20 p-2 rounded-full transition">
            <i class="fas fa-times text-xl"></i>
        </a>
    </div>

    <div id="chat-box" class="flex-grow overflow-y-auto p-4 space-y-6 no-scrollbar"></div>

    <div class="bg-white p-4 border-t border-gray-100 shadow-[0_-4px_10px_rgba(0,0,0,0.03)]">
        <form id="chat-form" class="max-w-4xl mx-auto flex flex-col gap-3">
            <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" id="sender" value="<?php echo htmlspecialchars($userName); ?>">
            
            <?php if ($userRole === 'driver'): ?>
            <div class="flex items-center gap-2 bg-indigo-50/50 p-2 rounded-xl border border-indigo-100">
                <span class="text-[10px] font-black text-indigo-500 pl-2 uppercase">Your Price</span>
                <input type="number" id="quote_price" placeholder="₪ 0.00" 
                       class="w-full bg-transparent font-bold text-indigo-700 focus:outline-none placeholder:text-indigo-300">
            </div>
            <?php endif; ?>

            <div class="flex items-center gap-2 bg-gray-100 rounded-full p-1 pl-4 pr-1 focus-within:ring-2 focus-within:ring-indigo-500 transition-all">
                <input type="text" id="message" placeholder="Type a message..." 
                       class="flex-grow bg-transparent py-2 text-sm focus:outline-none">
                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white w-10 h-10 rounded-full flex items-center justify-center shadow-md active:scale-90 transition">
                    <i class="fas fa-paper-plane text-xs"></i>
                </button>
            </div>
        </form>
    </div>

    <script>
        const chatBox = document.getElementById('chat-box');
        const chatForm = document.getElementById('chat-form');
        const token = document.getElementById('token').value;
        const userRole = '<?php echo $userRole; ?>';
        const currentUserName = '<?php echo $userName; ?>';

        let isLocked = false; // משתנה גלובלי למניעת רינדור מיותר

        async function loadMessages() {
            try {
                // 1. בדיקה אם הסשן נסגר
                const statusRes = await fetch(`get_session_status.php?token=${token}`);
                const statusData = await statusRes.json();

                // אם הסשן נסגר ועדיין לא נעלנו את הממשק אצלנו
                if (statusData && statusData.is_active == 0 && !isLocked) {
                    isLocked = true;
                    const chatForm = document.getElementById('chat-form');
                    if (chatForm) {
                        chatForm.innerHTML = `
                            <div class="w-full text-center p-6 bg-gray-100 rounded-2xl border-2 border-dashed border-gray-200">
                                <i class="fas fa-lock text-gray-400 mb-2 text-xl"></i>
                                <p class="text-gray-500 font-bold text-sm">המכרז הסתיים. הצ'אט נעול לקריאה בלבד.</p>
                                ${statusData.chosen_driver_id ? `<p class="text-[10px] text-gray-400 uppercase mt-1">נבחר נהג לביצוע העבודה</p>` : ''}
                            </div>
                        `;
                    }
                }

                // 2. טעינת ההודעות הרגילה
                const res = await fetch(`get_messages.php?token=${token}`);
                const messages = await res.json();
                
                chatBox.innerHTML = messages.map(msg => {
                    if (msg.sender_name === 'System') {
                        return `<div class="flex justify-center my-6">
                                    <div class="bg-indigo-50 border border-indigo-100 text-indigo-700 px-6 py-2 rounded-full text-[11px] font-black uppercase tracking-wider shadow-sm flex items-center gap-2">
                                        <i class="fas fa-check-circle"></i> ${msg.message}
                                    </div>
                                </div>`;
                    }

                    const isMe = msg.sender_name === currentUserName;
                    let quoteHtml = '';
                    if (msg.quote_price) {
                        // כפתור ה-Accept יופיע רק ללקוח ורק אם הצ'אט לא נעול
                        const showAcceptBtn = userRole === 'customer' && !isLocked;

                        quoteHtml = `<div class="mt-3 overflow-hidden rounded-xl border border-emerald-100 bg-white shadow-sm">
                                        <div class="bg-emerald-50 px-3 py-1.5 text-[10px] font-bold uppercase text-emerald-700">Driver Offer</div>
                                        <div class="p-3 text-center">
                                            <div class="text-xl font-black text-gray-900">₪${msg.quote_price}</div>
                                            ${showAcceptBtn ? 
                                                `<button onclick="acceptOffer('${msg.sender_name}', ${msg.quote_price})" class="mt-2 w-full rounded-lg bg-emerald-600 py-2 text-xs font-bold text-white hover:bg-emerald-700 active:scale-95 transition">Accept This Driver</button>` : 
                                                (isLocked && statusData.chosen_driver_id === msg.sender_name ? '<p class="text-emerald-600 font-bold text-[10px] mt-2">נבחר לביצוע!</p>' : '<p class="text-[10px] text-gray-400 mt-1 uppercase font-bold tracking-tighter">Bidding Closed</p>')}
                                        </div>
                                    </div>`;
                    }

                    const text = msg.message ? msg.message.trim() : '';
                    const bubble = text !== '' ? `<div class="max-w-[85%] px-4 py-2.5 shadow-sm text-sm leading-relaxed ${isMe ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-none' : 'bg-white text-gray-800 rounded-2xl rounded-tl-none border border-gray-200'}">${msg.message}</div>` : '';

                    return `<div class="flex flex-col mb-4 ${isMe ? 'items-end' : 'items-start'}">
                                <span class="text-[10px] text-gray-400 mb-1 px-2 font-bold uppercase tracking-tighter">${isMe ? 'You' : msg.sender_name}</span>
                                ${bubble}
                                <div class="w-48">${quoteHtml}</div>
                            </div>`;
                }).join('');
                
                chatBox.scrollTop = chatBox.scrollHeight;
            } catch (e) { console.error("Error refreshing chat:", e); }
        }

        chatForm.onsubmit = async (e) => {
            e.preventDefault();
            const messageInput = document.getElementById('message');
            const quoteInput = document.getElementById('quote_price');
            const messageValue = messageInput.value.trim();
            const quoteValue = quoteInput ? quoteInput.value.trim() : '';

            // אם אין הודעה ואין מחיר - אל תעשה כלום
            if (!messageValue && !quoteValue) return;

            // --- וולידציה: קפיצות של 10 שקלים ---
            if (quoteValue !== '') {
                const priceNum = parseFloat(quoteValue);
                
                // בדיקה אם המספר מתחלק ב-10 ללא שארית
                if (priceNum % 10 !== 0) {
                    alert("אפשר להזין רק סכומים בעשרות של 10 (למשל: 100, 150, 210 וכו').");
                    quoteInput.focus();
                    return;
                }
                
                // בדיקה שהמחיר חיובי
                if (priceNum <= 0) {
                    alert("נא להזין מחיר תקין הגדול מ-0.");
                    quoteInput.focus();
                    return;
                }
            }

            const formData = new FormData();
            formData.append('token', token);
            formData.append('sender', currentUserName);
            formData.append('message', messageValue);
            if (quoteValue) formData.append('quote_price', quoteValue);

            try {
                const response = await fetch('send_message.php', { 
                    method: 'POST', 
                    body: formData 
                });
                const result = await response.json();
                
                if (result.status === 'success') {
                    // ניקוי השדות לאחר הצלחה
                    messageInput.value = '';
                    if (quoteInput) quoteInput.value = '';
                    
                    // טעינה מיידית של ההודעות כדי שהמשתמש יראה את ההודעה שלו
                    loadMessages();
                } else {
                    alert("שגיאה בשליחת ההודעה: " + result.message);
                }
            } catch (err) {
                console.error("Send message failed:", err);
            }
        };

        async function acceptOffer(driverName, price) {
            if (!confirm(`לאשר את ${driverName} עבור ₪${price}? המכרז יסתיים.`)) return;
            
            const formData = new FormData();
            formData.append('token', token);
            formData.append('driver_name', driverName);

            try {
                const res = await fetch('accept_offer.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.status === 'success') {
                    
                    
                    // 2. נציג הודעת תודה יפה על המסך
                    const chatBox = document.getElementById('chat-box');
                    const thankYouDiv = document.createElement('div');
                    thankYouDiv.className = "fixed inset-0 flex items-center justify-center z-50 bg-black/50 backdrop-blur-sm";
                    thankYouDiv.innerHTML = `
                        <div class="bg-white p-8 rounded-3xl shadow-2xl text-center max-w-sm mx-4 animate-bounce-in">
                            <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                                <i class="fas fa-check"></i>
                            </div>
                            <h2 class="text-2xl font-black text-gray-900 mb-2">תודה רבה!</h2>
                            <p class="text-gray-600 mb-6 text-sm">המכרז נסגר בהצלחה</p>
                            <div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent"></div>
                        </div>
                    `;
                    document.body.appendChild(thankYouDiv);

                    // 3. אחרי 3 שניות - רידירקט לדף ההצלחה
                    setTimeout(() => {
                        window.location.href = `success.php?token=${token}&amount=${price}`;
                    }, 3000);

                } else {
                    alert("שגיאה: " + data.message);
                }
            } catch (e) {
            console.error("Full Error Details:", e);
            // This will show you exactly what went wrong in the browser console
            alert("חלה שגיאה: " + e.message); 
        }
                }

        setInterval(loadMessages, 3000);
        loadMessages();
    </script>
</body>
</html>