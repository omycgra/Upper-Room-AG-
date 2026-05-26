<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $mode = strtolower(trim((string)($attendance_mode ?? 'manual')));
    if (!in_array($mode, ['manual', 'biotime', 'qrcode', 'link'], true)) $mode = 'manual';
    $modeLabel = $mode === 'biotime' ? 'BioTime' : ($mode === 'qrcode' ? 'QR Code' : ($mode === 'link' ? 'Link' : 'Manual'));
    $serviceTypes = ['Sunday Service', 'Mid-week Service', 'Youth Meeting', 'Special Event'];
    $canManage = !empty($can_manage_attendance);
    $canDownload = !empty($can_download_attendance);
    $dailyDate = (string)($service_date ?? date('Y-m-d'));
    $dailyType = (string)($service_type ?? 'Sunday Service');
    $dailyReport = is_array($daily_report ?? null) ? $daily_report : [];
    $dailyCounts = is_array($dailyReport['counts'] ?? null) ? $dailyReport['counts'] : ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0];
    $dailyRows = is_array($dailyReport['rows'] ?? null) ? $dailyReport['rows'] : [];
?>

<div class="flex flex-col sm:flex-row justify-between items-start mb-10 gap-4">
    <div>
        <h2 class="text-4xl sm:text-5xl font-black text-white tracking-tighter">Attendance</h2>
        <p class="text-slate-400 font-bold mt-2 uppercase tracking-widest text-xs">Mode: <span class="text-accent"><?php echo htmlspecialchars($modeLabel); ?></span> • <span class="text-accent"><?php echo htmlspecialchars($churchName); ?></span></p>
    </div>
    <div class="flex flex-wrap gap-3">
        <?php if ($mode === 'manual' && $canManage): ?>
            <a href="<?php echo BASE_URL; ?>/attendance/mark" class="glass-card flex items-center px-6 py-3.5 rounded-2xl bg-accent text-slate-900 font-black text-xs uppercase tracking-widest hover:scale-[1.03] transition-all shadow-xl shadow-yellow-500/20">
                <i class="fas fa-check-double mr-2"></i> Mark Attendance
            </a>
        <?php elseif ($mode === 'biotime' && $canManage): ?>
            <a href="<?php echo BASE_URL; ?>/settings" class="glass-card flex items-center px-6 py-3.5 rounded-2xl border-white/10 text-slate-300 font-black text-xs uppercase tracking-widest hover:bg-white/5 transition-all">
                <i class="fas fa-gear mr-2"></i> Settings
            </a>
        <?php elseif (in_array($mode, ['qrcode', 'link'], true) && $canManage): ?>
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
                    <?php if ($canManage): ?>
                        <a href="<?php echo BASE_URL; ?>/attendance/mark" class="h-12 px-6 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 inline-flex items-center justify-center">
                            <i class="fas fa-check-double text-[12px] mr-2"></i> Open
                        </a>
                    <?php endif; ?>
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
                            <?php if ($canManage): ?>
                                <button type="submit" class="h-12 px-6 rounded-2xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest hover-glow-yellow active:scale-95 transition-all">
                                    <i class="fas fa-fingerprint text-[12px] mr-2"></i> Sync
                                </button>
                                <button type="submit" formaction="<?php echo BASE_URL; ?>/attendance/pushOnline" class="h-12 px-6 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 inline-flex items-center justify-center">
                                    <i class="fas fa-cloud-arrow-up text-[12px] mr-2"></i> Push
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                    <p class="mt-5 text-[10px] font-black uppercase tracking-widest text-slate-500">BioTime URL: <span class="text-slate-300"><?php echo htmlspecialchars((string)($biotime_url ?? '')); ?></span></p>

                    <?php if ($canManage): ?>
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
                        <p class="mt-4 text-[10px] font-black uppercase tracking-widest text-slate-500">Open this link on a phone to mark attendance</p>
                    </div>
                    <?php if ($mode === 'qrcode'): ?>
                        <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6 sm:p-8 flex flex-col items-center">
                            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">QR Code</p>
                            <img id="quick-qr-img" alt="Attendance QR" class="mt-4 w-44 h-44 rounded-2xl bg-white p-2">
                            <p class="mt-4 text-[10px] font-black uppercase tracking-widest text-slate-500 text-center">Requires internet to render QR image</p>
                        </div>
                    <?php endif; ?>
                </div>

                <?php
                    $lanIp = '';
                    $candidates = [];
                    $serverAddr = trim((string)($_SERVER['SERVER_ADDR'] ?? ''));
                    if ($serverAddr !== '') $candidates[] = $serverAddr;
                    $host = @gethostname();
                    if (is_string($host) && $host !== '') {
                        $ips = @gethostbynamel($host);
                        if (is_array($ips)) {
                            foreach ($ips as $ip) {
                                $candidates[] = (string)$ip;
                            }
                        }
                    }
                    $candidates = array_values(array_unique(array_filter(array_map('trim', $candidates))));
                    foreach ($candidates as $ip) {
                        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) continue;
                        if (str_starts_with($ip, '127.')) continue;
                        if ($ip === '0.0.0.0') continue;
                        $lanIp = $ip;
                        break;
                    }
                ?>

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
                            const base = '<?php echo rtrim((string)BASE_URL, '/'); ?>';
                            let origin = window.location.origin;
                            try {
                                const host = (window.location.hostname || '').toLowerCase();
                                if ((host === 'localhost' || host === '127.0.0.1') && '<?php echo $lanIp; ?>' !== '') {
                                    origin = window.location.protocol + '//' + '<?php echo $lanIp; ?>' + (window.location.port ? (':' + window.location.port) : '');
                                }
                            } catch (e) {}
                            const baseAbs = (base.startsWith('http://') || base.startsWith('https://')) ? base : (origin + base);
                            return baseAbs + '/attendance/quick?' + qs;
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
                                <?php
                                    $s = strtolower(trim((string)($record['computed_status'] ?? $record['status'] ?? 'present')));
                                    if (!in_array($s, ['present', 'late', 'absent'], true)) $s = 'present';
                                ?>
                                <?php if ($s === 'present'): ?>
                                    <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">PRESENT</span>
                                <?php elseif ($s === 'late'): ?>
                                    <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border bg-amber-500/10 text-amber-300 border-amber-500/20">LATE</span>
                                <?php else: ?>
                                    <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border bg-rose-500/10 text-rose-300 border-rose-500/20">ABSENT</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden card-interaction mb-8">
    <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 bg-white/[0.02]">
        <div class="flex items-center">
            <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center mr-4 border border-accent/20">
                <i class="fas fa-clipboard-list text-accent text-sm"></i>
            </div>
            <div>
                <h4 class="text-xl font-black text-white tracking-tight">Service Attendance</h4>
                <p class="text-slate-500 font-black mt-2 uppercase tracking-widest text-[10px]">Present (7:00–10:30) • Late (10:31–12:00) • Absent (after 12:00)</p>
            </div>
        </div>
        <?php if ($canDownload): ?>
            <div class="flex flex-wrap gap-2 items-center">
                <select id="att-download-filter" class="h-12 px-5 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest outline-none">
                    <option value="all">All</option>
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                </select>
                <a id="att-download-btn" href="<?php echo BASE_URL; ?>/attendance/download?<?php echo http_build_query(['service_date' => $dailyDate, 'service_type' => $dailyType, 'status' => 'all']); ?>" class="h-12 px-6 rounded-2xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest hover-glow-yellow inline-flex items-center justify-center">
                    <i class="fas fa-download text-[12px] mr-2"></i> Download CSV
                </a>
            </div>
        <?php endif; ?>
    </div>
    <div class="p-6 sm:p-8 lg:p-10 space-y-8">
        <form method="GET" action="<?php echo BASE_URL; ?>/attendance" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Service Date</label>
                <input type="date" name="service_date" required value="<?php echo htmlspecialchars($dailyDate); ?>" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl px-5 py-4 text-xs font-black text-slate-200 transition-all outline-none">
            </div>
            <div class="space-y-2">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Service Type</label>
                <select name="service_type" required class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl px-5 py-4 text-xs font-black text-slate-200 transition-all outline-none appearance-none cursor-pointer">
                    <?php foreach ($serviceTypes as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo $t === $dailyType ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex justify-start md:justify-end">
                <button type="submit" class="h-12 px-6 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 inline-flex items-center justify-center">
                    <i class="fas fa-search text-[12px] mr-2"></i> View
                </button>
            </div>
        </form>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="rounded-[2rem] border border-white/10 bg-white/5 p-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Total Members</p>
                <div class="mt-3 text-3xl font-black text-white tracking-tight"><?php echo (int)($dailyCounts['total'] ?? 0); ?></div>
            </div>
            <div class="rounded-[2rem] border border-emerald-500/20 bg-emerald-500/10 p-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-emerald-300">Present</p>
                <div class="mt-3 text-3xl font-black text-emerald-100 tracking-tight"><?php echo (int)($dailyCounts['present'] ?? 0); ?></div>
            </div>
            <div class="rounded-[2rem] border border-amber-500/20 bg-amber-500/10 p-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-amber-300">Late</p>
                <div class="mt-3 text-3xl font-black text-amber-100 tracking-tight"><?php echo (int)($dailyCounts['late'] ?? 0); ?></div>
            </div>
            <div class="rounded-[2rem] border border-rose-500/20 bg-rose-500/10 p-6">
                <p class="text-[10px] font-black uppercase tracking-widest text-rose-300">Absent</p>
                <div class="mt-3 text-3xl font-black text-rose-100 tracking-tight"><?php echo (int)($dailyCounts['absent'] ?? 0); ?></div>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <button type="button" class="att-filter h-10 px-4 rounded-2xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10" data-status="all">All</button>
            <button type="button" class="att-filter h-10 px-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-200 font-black text-[10px] uppercase tracking-widest hover:bg-emerald-500/20" data-status="present">Present</button>
            <button type="button" class="att-filter h-10 px-4 rounded-2xl bg-amber-500/10 border border-amber-500/20 text-amber-200 font-black text-[10px] uppercase tracking-widest hover:bg-amber-500/20" data-status="late">Late</button>
            <button type="button" class="att-filter h-10 px-4 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-200 font-black text-[10px] uppercase tracking-widest hover:bg-rose-500/20" data-status="absent">Absent</button>
        </div>

        <div class="overflow-x-auto rounded-[2rem] border border-white/10 bg-white/5">
            <table class="w-full text-left">
                <thead class="bg-white/[0.02] border-b border-white/10">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Member</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Code</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Bio ID</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Check-in</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Source</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-500 uppercase tracking-widest">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/10" id="att-rows">
                    <?php if (empty($dailyRows)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-10 text-center text-slate-500 font-bold">No members found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($dailyRows as $row): ?>
                            <?php
                                $m = is_array($row['member'] ?? null) ? $row['member'] : [];
                                $name = trim((string)($m['first_name'] ?? '') . ' ' . (string)($m['last_name'] ?? ''));
                                $code = trim((string)($m['member_code'] ?? ''));
                                $bio = trim((string)($m['bio_id'] ?? ''));
                                $source = trim((string)($row['source'] ?? ''));
                                $checkIn = trim((string)($row['check_in'] ?? ''));
                                $status = strtolower(trim((string)($row['status'] ?? 'absent')));
                                if (!in_array($status, ['present', 'late', 'absent'], true)) $status = 'absent';
                            ?>
                            <tr class="hover:bg-white/[0.03]" data-status="<?php echo htmlspecialchars($status); ?>">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-black text-slate-200"><?php echo htmlspecialchars($name !== '' ? $name : ('#' . (int)($m['id'] ?? 0))); ?></div>
                                </td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-300"><?php echo htmlspecialchars($code !== '' ? $code : '—'); ?></td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-300"><?php echo htmlspecialchars($bio !== '' ? $bio : '—'); ?></td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-300"><?php echo $checkIn !== '' ? htmlspecialchars(date('H:i:s', strtotime($checkIn))) : '—'; ?></td>
                                <td class="px-6 py-4 text-sm font-bold text-slate-300"><?php echo htmlspecialchars($source !== '' ? $source : '—'); ?></td>
                                <td class="px-6 py-4">
                                    <?php if ($status === 'present'): ?>
                                        <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border bg-emerald-500/10 text-emerald-400 border-emerald-500/20">PRESENT</span>
                                    <?php elseif ($status === 'late'): ?>
                                        <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border bg-amber-500/10 text-amber-300 border-amber-500/20">LATE</span>
                                    <?php else: ?>
                                        <span class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border bg-rose-500/10 text-rose-300 border-rose-500/20">ABSENT</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
            (function () {
                const btns = document.querySelectorAll('.att-filter');
                const rows = document.querySelectorAll('#att-rows tr[data-status]');
                if (!btns.length || !rows.length) return;
                const apply = (status) => {
                    rows.forEach(r => {
                        const s = (r.getAttribute('data-status') || '').toLowerCase();
                        r.style.display = (status === 'all' || s === status) ? '' : 'none';
                    });
                };
                btns.forEach(b => b.addEventListener('click', () => apply((b.getAttribute('data-status') || 'all').toLowerCase())));
                apply('all');
            })();
        </script>

        <script>
            (function () {
                const filter = document.getElementById('att-download-filter');
                const btn = document.getElementById('att-download-btn');
                if (!filter || !btn) return;
                const base = '<?php echo rtrim((string)BASE_URL, '/'); ?>';
                const qsBase = <?php echo json_encode(['service_date' => $dailyDate, 'service_type' => $dailyType]); ?>;
                const build = () => {
                    const status = (filter.value || 'all').toLowerCase();
                    const qs = new URLSearchParams(Object.assign({}, qsBase, { status })).toString();
                    btn.href = base + '/attendance/download?' + qs;
                };
                filter.addEventListener('change', build);
                build();
            })();
        </script>
    </div>
</div>
