<?php
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $serviceDate = (string)($service_date ?? date('Y-m-d'));
    $serviceType = (string)($service_type ?? 'Sunday Service');
    $mode = strtolower(trim((string)($attendance_mode ?? 'link')));
    if (!in_array($mode, ['qrcode', 'link'], true)) $mode = 'link';
    
    // Get available service types
    $serviceTypes = ['Sunday Service', 'Midweek Service', 'Youth Service', 'Children Service'];

    // Get base URL
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $scriptName = dirname($_SERVER['SCRIPT_NAME']);
    $baseUrl = rtrim($protocol . $host . $scriptName, '/');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($churchName); ?> - Quick Attendance</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-accent: #fbbf24;
            --body-bg: radial-gradient(circle at top right, #1e293b, #0f172a);
            --card-bg: rgba(15, 23, 42, 0.88);
            --border-color: rgba(255, 255, 255, 0.1);
            --text-color: #f8fafc;
        }
        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--body-bg);
            background-attachment: fixed;
            color: var(--text-color);
            min-height: 100vh;
        }
        .glass-card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--border-color);
        }
        .btn-primary {
            background: var(--primary-accent);
            color: #0f172a;
            font-weight: 800;
            letter-spacing: 0.05em;
        }
        .btn-primary:hover {
            box-shadow: 0 0 25px rgba(251, 191, 36, 0.4);
            transform: translateY(-2px);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        .floating-icon {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="p-4 sm:p-6 lg:p-8">
    <!-- Background decoration -->
    <div class="fixed inset-0 pointer-events-none z-0">
        <div class="absolute top-20 left-10 w-64 h-64 bg-yellow-500/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-10 w-80 h-80 bg-blue-500/10 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-lg mx-auto relative z-10">
        <!-- Flash Messages -->
        <?php if (Session::has('success')): ?>
            <div class="mb-6 glass-card rounded-2xl p-4 border border-green-500/20 bg-green-500/10">
                <div class="flex items-center gap-3">
                    <i class="fas fa-check-circle text-green-400 text-2xl"></i>
                    <p class="text-sm font-bold text-white"><?php echo htmlspecialchars(Session::flash('success')); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (Session::has('error')): ?>
            <div class="mb-6 glass-card rounded-2xl p-4 border border-rose-500/20 bg-rose-500/10">
                <div class="flex items-center gap-3">
                    <i class="fas fa-exclamation-circle text-rose-400 text-2xl"></i>
                    <p class="text-sm font-bold text-white"><?php echo htmlspecialchars(Session::flash('error')); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (Session::has('warning')): ?>
            <div class="mb-6 glass-card rounded-2xl p-4 border border-yellow-500/20 bg-yellow-500/10">
                <div class="flex items-center gap-3">
                    <i class="fas fa-info-circle text-yellow-400 text-2xl"></i>
                    <p class="text-sm font-bold text-white"><?php echo htmlspecialchars(Session::flash('warning')); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-yellow-500/10 border border-yellow-500/20 mb-4 floating-icon">
                <i class="fas fa-check-square text-yellow-400 text-4xl"></i>
            </div>
            <h1 class="text-3xl sm:text-4xl font-black tracking-tight mb-2">Quick Attendance</h1>
            <p class="text-slate-400 font-semibold"><?php echo htmlspecialchars($churchName); ?></p>
        </div>

        <!-- Service Info -->
        <div class="glass-card rounded-[2rem] p-6 mb-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center">
                    <i class="fas fa-calendar text-yellow-400"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-black text-slate-500 uppercase tracking-widest">Service Date</h3>
                    <p class="text-lg font-bold text-white"><?php echo htmlspecialchars($serviceDate); ?></p>
                </div>
            </div>
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-white/5 rounded-xl flex items-center justify-center">
                    <i class="fas fa-church text-yellow-400"></i>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-black text-slate-500 uppercase tracking-widest">Service Type</h3>
                    <p class="text-lg font-bold text-white"><?php echo htmlspecialchars($serviceType); ?></p>
                </div>
            </div>
        </div>

        <!-- QR Scanner Section -->
        <div id="qa-scanner-wrap" class="hidden glass-card rounded-[2rem] p-6 mb-6">
            <div class="space-y-4">
                <div class="rounded-2xl border border-white/10 bg-black/30 overflow-hidden">
                    <video id="qa-video" class="w-full h-64 sm:h-80 object-cover" playsinline></video>
                </div>
                <p class="text-xs font-black uppercase tracking-widest text-slate-500 text-center">Point your camera at the member QR code</p>
                <p id="qa-scan-err" class="text-xs font-black uppercase tracking-widest text-rose-400 text-center hidden"></p>
            </div>
        </div>

        <!-- Attendance Form -->
        <form id="qa-form" action="<?php echo htmlspecialchars($baseUrl); ?>/attendance/quickMark" method="POST" class="glass-card rounded-[2rem] p-6">
            <input type="hidden" name="service_date" value="<?php echo htmlspecialchars($serviceDate); ?>">

            <div class="space-y-3 mb-6">
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Service Type</label>
                <div class="relative group">
                    <i class="fas fa-church absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-yellow-400 transition-colors"></i>
                    <select id="qa-service-type" name="service_type" class="w-full bg-white/5 border border-white/10 focus:border-yellow-400 rounded-2xl pl-12 pr-10 py-4 text-sm font-bold text-white placeholder:text-slate-500 transition-all outline-none appearance-none cursor-pointer">
                        <?php foreach ($serviceTypes as $st): ?>
                            <option value="<?php echo htmlspecialchars($st); ?>" <?php echo $serviceType === $st ? 'selected' : ''; ?>><?php echo htmlspecialchars($st); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <i class="fas fa-chevron-down absolute right-4 top-1/2 -translate-y-1/2 text-slate-500 text-[10px] pointer-events-none"></i>
                </div>
            </div>

            <div class="space-y-3 mb-6">
                <label class="block text-xs font-black text-slate-500 uppercase tracking-widest ml-1">Search Member or Enter Code</label>
                <div class="relative group">
                    <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-500 group-focus-within:text-yellow-400 transition-colors"></i>
                    <input id="qa-code" autofocus type="text" name="member_code" class="w-full bg-white/5 border border-white/10 focus:border-yellow-400 rounded-2xl pl-12 pr-4 py-4 text-sm font-bold text-white placeholder:text-slate-500 transition-all outline-none" placeholder="Search for your name or enter code...">
                </div>
                <div id="qa-member-list" class="max-h-60 overflow-y-auto rounded-2xl border border-white/10 bg-white/5 hidden">
                    <!-- Member list will be populated here -->
                </div>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" id="qa-scan-btn" class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-white/5 border border-white/10 text-slate-300 hover:bg-white/10 hover:border-white/20 transition-all text-xs font-black uppercase tracking-widest">
                        <i class="fas fa-camera text-yellow-400"></i>
                        Scan QR
                    </button>
                    <button type="button" id="qa-stop-btn" class="hidden flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-rose-500/15 border border-rose-500/20 text-rose-300 hover:bg-rose-500/20 transition-all text-xs font-black uppercase tracking-widest">
                        <i class="fas fa-stop"></i>
                        Stop
                    </button>
                </div>
                <button type="submit" class="w-full btn-primary py-4 rounded-2xl transition-all active:scale-95">
                    <i class="fas fa-paper-plane mr-2"></i>
                    Mark Attendance
                </button>
            </div>
        </form>
    </div>

    <script>
        (function () {
            const scanBtn = document.getElementById('qa-scan-btn');
            const stopBtn = document.getElementById('qa-stop-btn');
            const wrap = document.getElementById('qa-scanner-wrap');
            const video = document.getElementById('qa-video');
            const codeInput = document.getElementById('qa-code');
            const memberList = document.getElementById('qa-member-list');
            const form = document.getElementById('qa-form');
            const errEl = document.getElementById('qa-scan-err');
            const baseUrl = '<?php echo rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/'); ?>';

            if (!scanBtn || !stopBtn || !wrap || !video || !codeInput || !memberList || !form) return;

            let stream = null;
            let detector = null;
            let running = false;
            let tick = 0;
            let debounceTimer = null;

            function showError(msg) {
                if (!errEl) return;
                const t = (msg || '').trim();
                if (t === '') {
                    errEl.classList.add('hidden');
                    errEl.textContent = '';
                    return;
                }
                errEl.textContent = t;
                errEl.classList.remove('hidden');
            }

            function extractCode(raw) {
                const s = (raw || '').trim();
                if (s === '') return '';
                try {
                    if (s.startsWith('http://') || s.startsWith('https://')) {
                        const u = new URL(s);
                        const v = (u.searchParams.get('code') || u.searchParams.get('member_code') || u.searchParams.get('bio_id') || '').trim();
                        if (v !== '') return v;
                        const last = u.pathname.split('/').filter(Boolean).pop() || '';
                        return last.trim();
                    }
                } catch (e) {}
                return s;
            }

            async function stop() {
                running = false;
                showError('');
                stopBtn.classList.add('hidden');
                scanBtn.classList.remove('hidden');
                wrap.classList.add('hidden');
                if (stream) {
                    try {
                        stream.getTracks().forEach(t => t.stop());
                    } catch (e) {}
                }
                stream = null;
                try {
                    video.srcObject = null;
                } catch (e) {}
            }

            async function loop() {
                if (!running) return;
                tick++;
                if (tick % 2 !== 0) {
                    requestAnimationFrame(loop);
                    return;
                }
                try {
                    const codes = await detector.detect(video);
                    if (Array.isArray(codes) && codes.length > 0) {
                        const raw = (codes[0].rawValue || '').trim();
                        const code = extractCode(raw);
                        if (code !== '') {
                            codeInput.value = code.toUpperCase();
                            await stop();
                            form.submit();
                            return;
                        }
                    }
                } catch (e) {}
                requestAnimationFrame(loop);
            }

            async function start() {
                showError('');
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    showError('Camera is not available in this browser. Please type your member code.');
                    return;
                }
                if (!('BarcodeDetector' in window)) {
                    showError('QR scanning is not supported on this browser. Please type your member code.');
                    return;
                }
                try {
                    const supported = await window.BarcodeDetector.getSupportedFormats();
                    if (!Array.isArray(supported) || supported.indexOf('qr_code') === -1) {
                        showError('QR scanning is not supported on this device. Please type your member code.');
                        return;
                    }
                    detector = new window.BarcodeDetector({ formats: ['qr_code'] });
                } catch (e) {
                    showError('QR scanner failed to start. Please type your member code.');
                    return;
                }

                try {
                    stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                    video.srcObject = stream;
                    await video.play();
                    running = true;
                    tick = 0;
                    wrap.classList.remove('hidden');
                    stopBtn.classList.remove('hidden');
                    scanBtn.classList.add('hidden');
                    requestAnimationFrame(loop);
                } catch (e) {
                    showError('Camera permission denied. Please allow camera access or type your member code.');
                }
            }

            function renderMemberList(members) {
                memberList.innerHTML = '';
                if (!members || members.length === 0) {
                    memberList.classList.add('hidden');
                    return;
                }
                memberList.classList.remove('hidden');
                
                members.forEach(m => {
                    const name = [m.first_name, m.last_name].filter(Boolean).join(' ').trim();
                    const code = m.member_code || m.bio_id || '';
                    
                    const div = document.createElement('div');
                    div.className = 'p-3 border-b border-white/10 hover:bg-white/10 cursor-pointer transition-colors';
                    div.innerHTML = `
                        <div class="flex justify-between items-center">
                            <span class="font-bold text-white">${name}</span>
                            <span class="text-xs font-bold text-yellow-400">${code}</span>
                        </div>
                    `;
                    div.addEventListener('click', () => {
                        codeInput.value = code;
                        memberList.classList.add('hidden');
                    });
                    memberList.appendChild(div);
                });
            }

            async function searchMembers(query) {
                if (query.trim().length < 2) {
                    renderMemberList([]);
                    return;
                }
                try {
                    const res = await fetch(`${baseUrl}/attendance/searchMembers?q=${encodeURIComponent(query)}`);
                    const members = await res.json();
                    renderMemberList(members);
                } catch (e) {
                    console.error('Search failed:', e);
                }
            }

            codeInput.addEventListener('input', (e) => {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => searchMembers(e.target.value), 300);
            });

            scanBtn.addEventListener('click', start);
            stopBtn.addEventListener('click', stop);
            window.addEventListener('pagehide', stop);
        })();
    </script>
</body>
</html>
