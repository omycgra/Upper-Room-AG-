<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $serviceDate = (string)($service_date ?? date('Y-m-d'));
    $serviceType = (string)($service_type ?? 'Sunday Service');
    $mode = strtolower(trim((string)($attendance_mode ?? 'link')));
    if (!in_array($mode, ['qrcode', 'link'], true)) $mode = 'link';
?>

<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Quick Attendance</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Mode: <span class="text-accent"><?php echo htmlspecialchars($mode === 'qrcode' ? 'QR Code' : 'Link'); ?></span> • <span class="text-accent"><?php echo htmlspecialchars($churchName); ?></span></p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="<?php echo BASE_URL; ?>/attendance" class="glass-card flex items-center px-6 py-3.5 rounded-2xl border-white/10 text-slate-300 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Attendance
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8">
    <div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex items-center gap-4 bg-white/[0.02]">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center border border-accent/20">
                <i class="fas fa-calendar text-accent text-sm"></i>
            </div>
            <div class="min-w-0">
                <h4 class="text-xl font-black text-white tracking-tight">Service</h4>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-1">This screen marks attendance for one service</p>
            </div>
        </div>
        <div class="p-6 sm:p-8 lg:p-10 space-y-5">
            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-5">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Date</p>
                <p class="mt-2 text-sm font-black text-slate-200"><?php echo htmlspecialchars($serviceDate); ?></p>
            </div>
            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-5">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Type</p>
                <p class="mt-2 text-sm font-black text-slate-200"><?php echo htmlspecialchars($serviceType); ?></p>
            </div>
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Tip: use Member Code or Bio ID</p>
        </div>
    </div>

    <div class="lg:col-span-2 glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex items-center gap-4 bg-white/[0.02]">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center border border-accent/20">
                <i class="fas fa-bolt text-accent text-sm"></i>
            </div>
            <div class="min-w-0">
                <h4 class="text-xl font-black text-white tracking-tight">Mark Present</h4>
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-1">Enter code and submit (duplicates are blocked)</p>
            </div>
        </div>

        <form action="<?php echo BASE_URL; ?>/attendance/quickMark" method="POST" class="p-6 sm:p-8 lg:p-10 space-y-8">
            <input type="hidden" name="service_date" value="<?php echo htmlspecialchars($serviceDate); ?>">
            <input type="hidden" name="service_type" value="<?php echo htmlspecialchars($serviceType); ?>">

            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Member Code / Bio ID</label>
                <div class="relative group">
                    <i class="fas fa-id-badge absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input autofocus type="text" name="member_code" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-black text-slate-200 transition-all outline-none" placeholder="Example: MEMABC123 or BIO-001">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="h-12 px-8 rounded-2xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest hover-glow-yellow active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Mark Present
                </button>
            </div>
        </form>
    </div>
</div>

