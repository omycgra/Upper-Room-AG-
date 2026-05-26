<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $mode = strtolower(trim((string)($attendance_mode ?? 'manual')));
    if (!in_array($mode, ['manual', 'biotime', 'qrcode', 'link'], true)) $mode = 'manual';
    $modeLabel = $mode === 'biotime' ? 'BioTime' : ($mode === 'qrcode' ? 'QR Code' : ($mode === 'link' ? 'Link' : 'Manual'));
    $serviceTypes = ['Sunday Service', 'Mid-week Service', 'Youth Meeting', 'Special Event'];
?>

<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Attendance</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Mode: <span class="text-accent"><?php echo htmlspecialchars($modeLabel); ?></span> • <span class="text-accent"><?php echo htmlspecialchars($churchName); ?></span></p>
    </div>
    <div class="flex flex-wrap gap-3">
        <?php if ($mode === 'manual'): ?>
            <a href="<?php echo BASE_URL; ?>/attendance/mark" class="glass-card flex items-center px-6 py-3.5 rounded-2xl bg-accent text-slate-900 font-black text-xs uppercase tracking-widest hover:scale-[1.03] transition-all shadow-xl shadow-yellow-500/20">
                <i class="fas fa-check-double mr-2"></i> Mark Attendance
            </a>
        <?php elseif ($mode === 'biotime'): ?>
            <a href="<?php echo BASE_URL; ?>/settings" class="glass-card flex items-center px-6 py-3.5 rounded-2xl border-white/10 text-slate-300 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">
                <i class="fas fa-gear mr-2"></i> Settings
            </a>
        <?php else: ?>
            <a id="quick-open-btn" href="<?php echo BASE_URL; ?>/attendance/quick?<?php echo http_build_query(['service_date' => date('Y-m-d'), 'service_type' => 'Sunday Service']); ?>" class="glass-card flex items-center px-6 py-3.5 rounded-2xl bg-accent text-slate-900 font-black text-xs uppercase tracking-widest hover:scale-[1.03] transition-all shadow-xl shadow-yellow-500/20">
                <i class="fas fa-bolt mr-2"></i> Quick Mark
            </a>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 lg:gap-8 mb-8">
    <div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] p-6 sm:p-8 border-white/5 card-interaction">
        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Avg. Attendance Rate</p>
        <div class="mt-4 text-4xl font-black text-white tracking-tight"><?php echo htmlspecialchars((string)($attendance_rate ?? '0%')); ?></div>
        <p class="mt-3 text-xs font-black uppercase tracking-widest text-slate-500">Last 30 days</p>
    </div>

    <div class="lg:col-span-2 glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 bg-white/[0.02]">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                    <i class="fas <?php echo $mode === 'biotime' ? 'fa-fingerprint' : ($mode === 'manual' ? 'fa-hand' : 'fa-qrcode'); ?> text-accent text-sm"></i>
                </div>
                <div>
                    <h4 class="text-xl font-black text-white tracking-tight">
                        <?php echo $mode === 'biotime' ? 'BioTime Sync' : ($mode === 'manual' ? 'Manual Attendance' : 'Quick Attendance'); ?>
                    </h4>
                    <p class="text-slate-500 font-black mt-2 uppercase tracking-widest text-[10px]">
                        <?php echo $mode === 'biotime' ? 'Imports device punches and marks members present (BIO ID ↔ emp_code).' : ($mode === 'manual' ? 'Select members present and submit once.' : 'Use a link/QR to open the quick marking screen.'); ?>
                    </p>
                </div>
            </div>
            <?php if ($mode === 'biotime'): ?>
                <?php if (!empty($biotime_configured)): ?>
                    <span class="px-4 py-2 text-[9px] font-black rounded-full uppercase tracking-widest border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">Connected</span>
                <?php else: ?>
                    <span class="px-4 py-2 text-[9px] font-black rounded-full uppercase tracking-widest border bg-amber-500/10 text-amber-300 border-amber-500/20">Not Configured</span>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="p-6 sm:p-8 lg:p-10">
            <?php if ($mode === 'manual'): ?>
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                    <div>
                        <p class="text-sm font-black text-slate-200">Mark attendance by selecting members present.</p>
                        <p class="mt-2 text-[10px] font-black uppercase tracking-widest text-slate-500">Best for small services or when no device is connected</p>
                    </div>
                    <a href="<?php echo BASE_URL; ?>/attendance/mark" class="h-12 px-6 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 inline-flex items-center justify-center">
                        <i class="fas fa-check-double text-[12px] mr-2"></i> Open
                    </a>
                </div>
            <?php elseif ($mode === 'biotime'): ?>
                <?php if (!empty($biotime_configured)): ?>
                    <form action="<?php echo BASE_URL; ?>/attendance/syncBioTime" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <div class="space-y-2">
                            <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Service Date</label>
                            <input type="date" name="service_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl px-5 py-4 text-xs font-black text-slate-200 transition-all outline-none">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Service Type</label>
                            <select name="service_type" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl px-5 py-4 text-xs font-black text-slate-200 transition-all outline-none appearance-none cursor-pointer">
                                <?php foreach ($serviceTypes as $t): ?>
                                    <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex justify-start md:justify-end gap-2">
                            <button type="submit" class="h-12 px-6 rounded-2xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest hover-glow-yellow active:scale-95 transition-all">
                                <i class="fas fa-fingerprint text-[12px] mr-2"></i> Sync
                            </button>
                            <?php if (Auth::isAdmin()): ?>
                                <button type="submit" formaction="<?php echo BASE_URL; ?>/attendance/pushOnline" class="h-12 px-6 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 inline-flex items-center justify-center">
                                    <i class="fas fa-cloud-arrow-up text-[12px] mr-2"></i> Push
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                    <p class="mt-5 text-[10px] font-black uppercase tracking-widest text-slate-500">BioTime URL: <span class="text-slate-300"><?php echo htmlspecialchars((string)($biotime_url ?? '')); ?></span></p>

                    <?php if (Auth::isAdmin()): ?>
                        <div class="mt-6 rounded-[2rem] border border-white/10 bg-white/5 p-6 sm:p-8">
                            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Push To Online</p>
                                    <p class="mt-2 text-sm font-black text-slate-200">After syncing locally, push the same service attendance to the online system.</p>
                                    <?php if (!empty($cloud_last_result)): ?>
                                        <p class="mt-3 text-[10px] font-black uppercase tracking-widest text-slate-500">Last result: <span class="text-slate-300"><?php echo htmlspecialchars((string)$cloud_last_result); ?></span></p>
                                    <?php endif; ?>
                                    <?php if (!empty($cloud_last_pushed_at)): ?>
                                        <p class="mt-2 text-[10px] font-black uppercase tracking-widest text-slate-500">Last pushed: <span class="text-slate-300"><?php echo htmlspecialchars((string)$cloud_last_pushed_at); ?></span></p>
                                    <?php endif; ?>
                                </div>
                                <a href="<?php echo BASE_URL; ?>/settings" class="h-12 px-6 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 inline-flex items-center justify-center shrink-0">
                                    <i class="fas fa-gear text-[12px] mr-2"></i> Settings
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 sm:p-8">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">BioTime is not configured</p>
                        <p class="mt-3 text-sm font-black text-slate-200">Go to Settings → Attendance and enter BioTime URL + token or username/password.</p>
                        <a href="<?php echo BASE_URL; ?>/settings" class="mt-5 inline-flex h-12 px-6 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 items-center justify-center">
                            <i class="fas fa-gear text-[12px] mr-2"></i> Open Settings
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                    <div class="space-y-2">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Service Date</label>
                        <input id="quick-service-date" type="date" value="<?php echo date('Y-m-d'); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl px-5 py-4 text-xs font-black text-slate-200 transition-all outline-none">
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Service Type</label>
                        <select id="quick-service-type" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl px-5 py-4 text-xs font-black text-slate-200 transition-all outline-none appearance-none cursor-pointer">
                            <?php foreach ($serviceTypes as $t): ?>
                                <option value="<?php echo htmlspecialchars($t); ?>"><?php echo htmlspecialchars($t); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex justify-start md:justify-end gap-2">
                        <button type="button" id="quick-copy-btn" class="h-12 px-6 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 inline-flex items-center justify-center">
                            <i class="fas fa-copy text-[12px] mr-2"></i> Copy Link
                        </button>
                        <a id="quick-open-link" href="<?php echo BASE_URL; ?>/attendance/quick" class="h-12 px-6 rounded-2xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest hover-glow-yellow inline-flex items-center justify-center">
                            <i class="fas fa-bolt text-[12px] mr-2"></i> Open
                        </a>
                    </div>
                </div>
                <div class="mt-6 grid grid-cols-1 <?php echo $mode === 'qrcode' ? 'md:grid-cols-3' : 'md:grid-cols-1'; ?> gap-6 items-start">
                    <div class="<?php echo $mode === 'qrcode' ? 'md:col-span-2' : ''; ?> rounded-[2rem] border border-white/10 bg-white/5 p-6 sm:p-8">
                        <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Attendance Link</p>
                        <p class="mt-3 text-sm font-black text-slate-200 break-words" id="quick-link-text"></p>
                        <p class="mt-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Open this link on a phone (admin account) for fast marking</p>
                    </div>
                    <?php if ($mode === 'qrcode'): ?>
                        <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 sm:p-8 flex flex-col items-center">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">QR Code</p>
                            <img id="quick-qr-img" alt="Attendance QR" class="mt-4 w-44 h-44 rounded-2xl bg-white p-2">
                            <p class="mt-4 text-[10px] font-black uppercase tracking-widest text-slate-500 text-center">Requires internet to render QR image</p>
                        </div>
                    <?php endif; ?>
                </div>

                <script>
                    (function () {
                        const dateEl = document.getElementById('quick-service-date');
                        const typeEl = document.getElementById('quick-service-type');
                        const openEl = document.getElementById('quick-open-link');
                        const openBtn = document.getElementById('quick-open-btn');
                        const linkText = document.getElementById('quick-link-text');
                        const copyBtn = document.getElementById('quick-copy-btn');
                        const qrImg = document.getElementById('quick-qr-img');
                        if (!dateEl || !typeEl || !openEl || !linkText) return;

                        const buildUrl = () => {
                            const d = dateEl.value || '<?php echo date('Y-m-d'); ?>';
                            const t = typeEl.value || 'Sunday Service';
                            const qs = new URLSearchParams({ service_date: d, service_type: t }).toString();
                            return '<?php echo rtrim((string)BASE_URL, '/'); ?>' + '/attendance/quick?' + qs;
                        };

                        const apply = () => {
                            const u = buildUrl();
                            openEl.href = u;
                            if (openBtn) openBtn.href = u;
                            linkText.textContent = u;
                            if (qrImg) {
                                qrImg.src = 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' + encodeURIComponent(u);
                            }
                        };

                        dateEl.addEventListener('change', apply);
                        typeEl.addEventListener('change', apply);
                        apply();

                        if (copyBtn) {
                            copyBtn.addEventListener('click', async () => {
                                const u = buildUrl();
                                try {
                                    await navigator.clipboard.writeText(u);
                                } catch (e) {
                                    try {
                                        const ta = document.createElement('textarea');
                                        ta.value = u;
                                        ta.style.position = 'fixed';
                                        ta.style.opacity = '0';
                                        document.body.appendChild(ta);
                                        ta.focus();
                                        ta.select();
                                        document.execCommand('copy');
                                        document.body.removeChild(ta);
                                    } catch (e2) {}
                                }
                            });
                        }
                    })();
                </script>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden card-interaction">
    <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex items-center justify-between gap-4 bg-white/[0.02]">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-list text-accent text-sm"></i>
            </div>
            <h4 class="text-xl font-black text-white tracking-tight">Recent Records</h4>
        </div>
        <span class="text-[10px] font-black uppercase tracking-widest text-slate-500"><?php echo count($recent_records ?? []); ?> items</span>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead class="bg-white/[0.02] border-b border-white/5">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Service Date</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Service Type</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Member</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Bio ID</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Source</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Device Time</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/5">
                <?php if (empty($recent_records)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-10 text-center text-slate-500 font-bold">No attendance records found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach (($recent_records ?? []) as $record): ?>
                        <?php
                            $name = trim((string)($record['first_name'] ?? '') . ' ' . (string)($record['last_name'] ?? ''));
                            $code = trim((string)($record['member_code'] ?? ''));
                            $bio = trim((string)($record['bio_id'] ?? ''));
                            $source = strtolower(trim((string)($record['source'] ?? 'manual')));
                            $deviceTime = trim((string)($record['device_time'] ?? ''));
                        ?>
                        <tr class="hover:bg-white/[0.03]">
                            <td class="px-6 py-4 text-sm font-bold text-slate-200"><?php echo !empty($record['service_date']) ? htmlspecialchars(date('M d, Y', strtotime((string)$record['service_date']))) : 'N/A'; ?></td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-300"><?php echo htmlspecialchars((string)($record['service_type'] ?? '')); ?></td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($name !== '' ? $name : ('#' . (int)($record['member_id'] ?? 0))); ?></div>
                                <?php if ($code !== ''): ?>
                                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-1"><?php echo htmlspecialchars($code); ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-300"><?php echo htmlspecialchars($bio !== '' ? $bio : '—'); ?></td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-300"><?php echo htmlspecialchars($source !== '' ? $source : 'manual'); ?></td>
                            <td class="px-6 py-4 text-sm font-bold text-slate-300"><?php echo $deviceTime !== '' ? htmlspecialchars(date('M d, Y H:i', strtotime($deviceTime))) : '—'; ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">PRESENT</span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
