<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
?>

<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Mark Attendance</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Manual mode • <span class="text-accent"><?php echo htmlspecialchars($churchName); ?></span></p>
    </div>
    <div class="flex flex-wrap gap-3">
        <a href="<?php echo BASE_URL; ?>/attendance" class="glass-card flex items-center px-6 py-3.5 rounded-2xl border-white/10 text-slate-300 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">
            <i class="fas fa-arrow-left mr-2"></i> Attendance
        </a>
    </div>
</div>

<div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden card-interaction">
    <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex items-center justify-between gap-4 bg-white/[0.02]">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-check-double text-accent text-sm"></i>
            </div>
            <div>
                <h4 class="text-xl font-black text-white tracking-tight">Service Details</h4>
                <p class="text-slate-500 font-black mt-2 uppercase tracking-widest text-[10px]">Select a service date/type, then choose members present</p>
            </div>
        </div>
        <span class="px-4 py-2 text-[9px] font-black rounded-full uppercase tracking-widest border bg-white/5 text-slate-300 border-white/10 shrink-0">
            <?php echo count($members ?? []); ?> members
        </span>
    </div>

    <form action="<?php echo BASE_URL; ?>/attendance/store" method="POST" class="p-6 sm:p-8 lg:p-10 space-y-8">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Service Date</label>
                <div class="relative group">
                    <i class="fas fa-calendar absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input type="date" name="service_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-xs font-black text-slate-200 transition-all outline-none">
                </div>
            </div>
            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Service Type</label>
                <div class="relative group">
                    <i class="fas fa-church absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <select name="service_type" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-10 py-4 text-xs font-black text-slate-200 transition-all outline-none appearance-none cursor-pointer">
                        <option value="Sunday Service">Sunday Service</option>
                        <option value="Mid-week Service">Mid-week Service</option>
                        <option value="Youth Meeting">Youth Meeting</option>
                        <option value="Special Event">Special Event</option>
                    </select>
                    <i class="fas fa-chevron-down absolute right-5 top-1/2 -translate-y-1/2 text-slate-600 text-[10px] pointer-events-none"></i>
                </div>
            </div>
        </div>

        <div class="glass-card rounded-[2rem] border-white/10 overflow-hidden">
            <div class="px-6 py-5 border-b border-white/10 flex items-center justify-between gap-3 bg-white/[0.02]">
                <div class="flex items-center">
                    <div class="w-9 h-9 bg-white/5 rounded-xl flex items-center justify-center border border-white/10 mr-3">
                        <i class="fas fa-users text-slate-300 text-[12px]"></i>
                    </div>
                    <h4 class="text-sm font-black text-slate-200 uppercase tracking-widest">Members Present</h4>
                </div>
                <label class="inline-flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-400 cursor-pointer select-none">
                    <input type="checkbox" id="select-all" class="rounded border-white/10 bg-white/5 text-accent focus:ring-0">
                    Select all
                </label>
            </div>
            <div class="max-h-[520px] overflow-y-auto custom-scrollbar">
                <table class="w-full text-left">
                    <thead class="sticky top-0 bg-slate-950/70 backdrop-blur border-b border-white/5">
                        <tr>
                            <th class="px-6 py-4 w-14"></th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Member</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Code</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <?php foreach (($members ?? []) as $member): ?>
                            <?php
                                $name = trim((string)($member['first_name'] ?? '') . ' ' . (string)($member['last_name'] ?? ''));
                                $code = trim((string)($member['member_code'] ?? ''));
                                $photoUrl = Branding::mediaUrl((string)($member['photo_path'] ?? ''));
                                $firstInitial = mb_substr(trim((string)($member['first_name'] ?? '')), 0, 1);
                                $lastInitial = mb_substr(trim((string)($member['last_name'] ?? '')), 0, 1);
                                $initials = trim(strtoupper($firstInitial . $lastInitial));
                                if ($initials === '') $initials = '—';
                            ?>
                            <tr class="hover:bg-white/[0.03] cursor-pointer" onclick="const c=this.querySelector('input'); if(c) c.click();">
                                <td class="px-6 py-4">
                                    <input type="checkbox" name="member_ids[]" value="<?php echo (int)($member['id'] ?? 0); ?>" class="rounded border-white/10 bg-white/5 text-accent focus:ring-0 member-check" onclick="event.stopPropagation()">
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <?php if (trim($photoUrl) !== ''): ?>
                                            <img src="<?php echo htmlspecialchars($photoUrl); ?>" alt="" class="w-9 h-9 rounded-full object-cover border border-white/10 bg-white/5">
                                        <?php else: ?>
                                            <div class="w-9 h-9 rounded-full bg-white/5 border border-white/10 flex items-center justify-center text-[10px] font-black text-slate-300">
                                                <?php echo htmlspecialchars($initials); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($name !== '' ? $name : ('#' . (int)($member['id'] ?? 0))); ?></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-300"><?php echo htmlspecialchars($code !== '' ? $code : '—'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="h-12 px-8 rounded-2xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest hover-glow-yellow active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                Submit Attendance
            </button>
        </div>
    </form>
</div>

<script>
    (function () {
        const all = document.getElementById('select-all');
        if (!all) return;
        all.addEventListener('change', function() {
            const checks = document.querySelectorAll('.member-check');
            checks.forEach(check => check.checked = all.checked);
        });
    })();
</script>
