<?php
session_start();
require_once '../includes/Itaidbh.inc.php';


$token = $_GET['token'] ?? '';
$userName = $_SESSION['user_name'] ?? 'Guest';

$isDriverFlag = (isset($_SESSION['is_driver']) && $_SESSION['is_driver'] == 1);
$userRole = $isDriverFlag ? 'driver' : 'customer';


if (empty($token)) {
    die("Error: No chat token provided.");
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
    die("Error: Chat link is no longer valid.");
}

// 3. Handle closed sessions
//if ((int)$session['is_active'] === 0 && !empty($session['chosen_driver_id'])) {
 //   echo "<script>alert('This job has been assigned. The chat is now closed.'); window.location.href='index.php';</script>";
//    exit;
//}
?>

<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Chat - MoveMe</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        body { touch-action: manipulation; } 
        #chat-box { -webkit-overflow-scrolling: touch; }
    </style>
</head>
<body class="bg-slate-50 h-[100dvh] flex flex-col font-sans overflow-hidden">

    <div class="bg-indigo-600 text-white px-4 py-3 shadow-md flex justify-between items-center sticky top-0 z-20">
        <div class="flex-1 min-w-0 ml-4">
            <h1 class="font-bold text-base leading-tight truncate">צ'אט הובלה</h1>
            <p class="text-[10px] opacity-80 uppercase tracking-wider font-medium truncate">
                <?php echo htmlspecialchars($session['pickup_location'] . " ← " . $session['delivery_location']); ?>
            </p>
        </div>
        <button onclick="if(window.history.length > 2) { window.history.back(); } else { window.location.href='<?php echo ($userRole === 'driver') ? 'ItaiRegisteredDriver.php' : 'index.php'; ?>'; }" 
            class="bg-white/10 hover:bg-white/20 w-10 h-10 rounded-full flex items-center justify-center transition shrink-0 cursor-pointer border-none outline-none active:scale-90">
            <i class="fas fa-times text-lg text-white"></i>
        </button>
    </div>

    <div id="chat-box" class="flex-grow overflow-y-auto p-4 space-y-4 no-scrollbar bg-[url('https://www.transparenttextures.com/patterns/cubes.png')]">
        </div>

    <div class="bg-white p-3 pb-safe border-t border-gray-100 shadow-[0_-4px_15px_rgba(0,0,0,0.05)] z-20">
        <form id="chat-form" class="max-w-2xl mx-auto flex flex-col gap-2">
            <input type="hidden" id="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" id="sender" value="<?php echo htmlspecialchars($userName); ?>">
            
            <div class="flex items-center gap-2 bg-gray-100 rounded-2xl p-1.5 focus-within:ring-2 focus-within:ring-indigo-500 transition-all">
                <?php if ($userRole === 'driver'): ?>
                    <div class="flex items-center flex-grow gap-2 bg-white px-4 py-2 rounded-xl border border-gray-200">
                        <span class="text-xs font-black text-indigo-600 uppercase whitespace-nowrap">הצעת מחיר:</span>
                        <input type="number" id="quote_price" placeholder="₪ 0" 
                            class="w-full bg-transparent font-bold text-indigo-700 focus:outline-none placeholder:text-gray-300 text-base"
                            required>
                        <input type="hidden" id="message" value="הצעת מחיר">
                    </div>
                <?php else: ?>
                    <div class="flex-grow py-3 px-4 text-sm text-gray-500 italic">
                        ממתין להצעת מחיר מהנהג...
                    </div>
                <?php endif; ?>

                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white w-12 h-12 rounded-xl flex items-center justify-center shadow-md active:scale-95 transition shrink-0">
                    <i class="fas fa-paper-plane text-sm"></i>
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

        let isLocked = false;

        async function loadMessages() {
            try {
                const statusRes = await fetch(`get_session_status.php?token=${token}`);
                const statusData = await statusRes.json();

                if (statusData && statusData.is_active == 0 && !isLocked) {
                    isLocked = true;
                    if (chatForm) {
                        chatForm.innerHTML = `
                            <div class="w-full text-center py-4 bg-gray-50 rounded-xl border border-gray-200">
                                <p class="text-gray-500 font-bold text-xs"><i class="fas fa-lock ml-1"></i> המכרז הסתיים. הצ'אט נעול.</p>
                            </div>
                        `;
                    }
                }

                const res = await fetch(`get_messages.php?token=${token}`);
                const messages = await res.json();
                
                chatBox.innerHTML = messages.map(msg => {
                    if (msg.sender_name === 'System') {
                        return `<div class="flex justify-center my-4">
                                    <div class="bg-indigo-50 border border-indigo-100 text-indigo-700 px-4 py-1.5 rounded-full text-[10px] font-bold uppercase shadow-sm">
                                        <i class="fas fa-info-circle ml-1"></i> ${msg.message}
                                    </div>
                                </div>`;
                    }

                    const isMe = msg.sender_name === currentUserName;
                    let quoteHtml = '';
                    if (msg.quote_price) {
                        const showAcceptBtn = userRole === 'customer' && !isLocked;
                        quoteHtml = `<div class="mt-2 overflow-hidden rounded-xl border border-emerald-100 bg-white shadow-sm w-full max-w-[200px]">
                                        <div class="bg-emerald-50 px-2 py-1 text-[9px] font-bold uppercase text-emerald-700 text-center">הצעת מחיר נהג</div>
                                        <div class="p-3 text-center">
                                            <div class="text-lg font-black text-gray-900 italic">₪${msg.quote_price}</div>
                                            ${showAcceptBtn ? 
                                                `<button onclick="acceptOffer('${msg.sender_name}', ${msg.quote_price})" class="mt-2 w-full rounded-lg bg-emerald-600 py-2 text-[10px] font-bold text-white active:scale-95 transition shadow-sm">אשר הצעה</button>` : 
                                                (isLocked && statusData.chosen_driver_id === msg.sender_name ? '<p class="text-emerald-600 font-bold text-[10px] mt-1 italic">נבחר!</p>' : '<p class="text-[9px] text-gray-400 mt-1 uppercase font-bold tracking-tighter">נסגר</p>')}
                                        </div>
                                    </div>`;
                    }

                    const text = msg.message ? msg.message.trim() : '';
                    const bubble = text !== '' ? `<div class="max-w-full px-4 py-2 shadow-sm text-sm ${isMe ? 'bg-indigo-600 text-white rounded-2xl rounded-tr-none' : 'bg-white text-gray-800 rounded-2xl rounded-tl-none border border-gray-100'}">${msg.message}</div>` : '';

                    return `<div class="flex flex-col ${isMe ? 'items-end ml-8' : 'items-start mr-8'}">
                                <span class="text-[9px] text-gray-400 mb-1 px-1 font-bold uppercase">${isMe ? 'אתה' : msg.sender_name}</span>
                                ${bubble}
                                ${quoteHtml}
                            </div>`;
                }).join('');
                
                chatBox.scrollTop = chatBox.scrollHeight;
            } catch (e) { console.error("Error:", e); }
        }

        chatForm.onsubmit = async (e) => {
            e.preventDefault();
            const messageInput = document.getElementById('message');
            const quoteInput = document.getElementById('quote_price');
            const messageValue = messageInput.value.trim();
            const quoteValue = quoteInput ? quoteInput.value.trim() : '';

            if (!messageValue && !quoteValue) return;

            if (quoteValue !== '') {
                const priceNum = parseFloat(quoteValue);
                if (priceNum % 10 !== 0 || priceNum <= 0) {
                    alert("נא להזין סכומים בעשרות של 10 בלבד.");
                    return;
                }
            }

            const formData = new FormData();
            formData.append('token', token);
            formData.append('sender', currentUserName);
            formData.append('message', messageValue);
            if (quoteValue) formData.append('quote_price', quoteValue);

            try {
                const response = await fetch('send_message.php', { method: 'POST', body: formData });
                const result = await response.json();
                if (result.status === 'success') {
                    messageInput.value = '';
                    if (quoteInput) quoteInput.value = '';
                    loadMessages();
                }
            } catch (err) { console.error(err); }
        };

        async function acceptOffer(driverName, price) {
            if (!confirm(`לאשר את ${driverName} עבור ₪${price}?`)) return;
            
            const formData = new FormData();
            formData.append('token', token);
            formData.append('driver_name', driverName);

            try {

                const res = await fetch('accept_offer.php', { method: 'POST', body: formData });
                const data = await res.json();
                
                if (data.status === 'success') {
                    const thankYouDiv = document.createElement('div');
                    thankYouDiv.className = "fixed inset-0 flex items-center justify-center z-[100] bg-black/60 backdrop-blur-sm px-6";
                    thankYouDiv.innerHTML = `
                        <div class="bg-white p-8 rounded-3xl shadow-2xl text-center w-full max-w-xs scale-in">
                            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                                <i class="fas fa-check"></i>
                            </div>
                            <h2 class="text-xl font-black text-gray-900 mb-1">תודה רבה!</h2>
                            <p class="text-gray-500 mb-6 text-xs font-bold">המכרז נסגר בהצלחה</p>
                            <div class="inline-block h-6 w-6 animate-spin rounded-full border-4 border-solid border-indigo-600 border-r-transparent"></div>
                        </div>
                    `;
                    document.body.appendChild(thankYouDiv);
                    setTimeout(() => {
                        window.location.href = `success.php?token=${token}&amount=${price}`;
                    }, 2500);
                }
            } catch (e) { console.error(e); }
        }

        setInterval(loadMessages, 3000);
        loadMessages();
    </script>
</body>
</html>