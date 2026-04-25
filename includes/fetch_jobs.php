<?php

session_start();
require_once 'Itaidbh.inc.php';



if (empty($deliveries)): ?>
    <div class="bg-white p-12 rounded-[2.5rem] text-center border-2 border-dashed border-slate-200">
        <i class="fas fa-search text-4xl text-slate-300 mb-4"></i>
        <p class="text-slate-500 font-bold">No jobs match your search criteria.</p>
    </div>
<?php else: ?>
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <?php foreach ($deliveries as $job): 
            $wazeUrl = "https://waze.com/ul?q=" . urlencode($job['pickup_location']) . "&navigate=yes";
            $estDriveTime = "Calculating..."; 
            $chatUrl = "chat.php?token=" . urlencode($job['chat_token']); 
        ?>
            <div class="bg-white rounded-[2.5rem] shadow-[0_20px_50px_rgba(0,0,0,0.04)] border border-slate-50 overflow-hidden p-6 hover:shadow-lg transition-all duration-300">
                <div class="flex justify-between items-center mb-6">
                    <span class="bg-indigo-50 text-indigo-600 text-[10px] font-black px-3 py-1 rounded-full uppercase tracking-widest">
                        <?php echo htmlspecialchars($job['object_type'] ?? 'General'); ?>
                    </span>
                    <div class="flex items-center gap-2 text-slate-400">
                        <i class="far fa-calendar text-xs"></i>
                        <span class="text-xs font-bold uppercase tracking-tighter text-slate-600">
                            <?php echo date('M d, Y', strtotime($job['moving_date'])); ?>
                        </span>
                    </div>
                </div>

                <div class="relative space-y-8 mb-8">
                    <div class="absolute left-[11px] top-3 w-[2px] h-[calc(100%-24px)] bg-slate-100"></div>
                    <div class="flex gap-4 items-start relative">
                        <div class="w-6 h-6 rounded-full bg-white border-4 border-slate-100 shadow-sm flex items-center justify-center z-10">
                            <div class="w-2 h-2 rounded-full bg-slate-400"></div>
                        </div>
                        <div class="flex-1">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest leading-none mb-1">Pickup</p>
                            <h3 class="text-base font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($job['pickup_location']); ?></h3>
                        </div>
                    </div>
                    <div class="flex gap-4 items-start relative">
                        <div class="w-6 h-6 rounded-full bg-white border-4 border-indigo-50 shadow-sm flex items-center justify-center z-10">
                            <div class="w-2 h-2 rounded-full bg-indigo-600"></div>
                        </div>
                        <div class="flex-1">
                            <p class="text-[10px] font-black text-indigo-400 uppercase tracking-widest leading-none mb-1">Destination</p>
                            <h3 class="text-base font-bold text-slate-800 leading-tight"><?php echo htmlspecialchars($job['delivery_location']); ?></h3>
                        </div>
                    </div>
                </div>

                <a href="<?php echo $chatUrl; ?>" class="w-full bg-slate-900 text-white py-4 rounded-2xl font-black text-xs tracking-[0.2em] uppercase hover:bg-indigo-600 transition active:scale-95 shadow-xl shadow-slate-100 flex items-center justify-center gap-2">
                    Contact & Quote <i class="fas fa-chevron-right text-[10px]"></i>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>