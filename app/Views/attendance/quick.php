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
            <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Present: 7:00–10:30 • Late: 10:31–12:00 • Absent: after 12:00</p>
        </div>
    </div>

    <div class="lg:col-span-2 glass-card rounded-[2.5rem] sm:rounded-[3rem] border-white/5 overflow-hidden card-interaction">
        <div class="px-6 sm:px-8 lg:px-10 py-6 sm:py-8 border-b border-white/5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white/[0.02]">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-accent/10 rounded-xl flex items-center justify-center border border-accent/20">
                    <i class="fas fa-bolt text-accent text-sm"></i>
                </div>
                <div class="min-w-0">
                    <h4 class="text-xl font-black text-white tracking-tight">Mark Attendance</h4>
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500 mt-1">Scan your member QR or enter code (duplicates are blocked)</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2 justify-end">
                <button type="button" id="qa-scan-btn" class="h-10 px-4 rounded-xl bg-white/5 border border-white/10 text-slate-200 font-black text-[10px] uppercase tracking-widest hover:bg-white/10 transition-all inline-flex items-center">
                    <i class="fas fa-camera mr-2 text-accent text-[10px]"></i> Scan QR
                </button>
                <button type="button" id="qa-stop-btn" class="h-10 px-4 rounded-xl bg-rose-500/15 text-rose-300 hover:bg-rose-500/20 transition-all border border-rose-500/20 text-[10px] font-black uppercase tracking-widest hidden">
                    Stop
                </button>
            </div>
        </div>

        <div id="qa-scanner-wrap" class="hidden px-6 sm:px-8 lg:px-10 py-6 bg-slate-900/30 border-b border-white/5">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 items-start">
                <div class="lg:col-span-2">
                    <div class="rounded-[2rem] border border-white/10 bg-black/30 overflow-hidden">
                        <video id="qa-video" class="w-full h-[320px] object-cover" playsinline></video>
                    </div>
                    <p class="mt-3 text-[10px] font-black uppercase tracking-widest text-slate-500">Point your camera at your member QR code</p>
                    <p id="qa-scan-err" class="mt-2 text-[10px] font-black uppercase tracking-widest text-rose-300 hidden"></p>
                </div>
                <div class="rounded-[2rem] border border-white/10 bg-white/5 p-5">
                    <p class="text-[10px] font-black uppercase tracking-widest text-slate-500">Tip</p>
                    <p class="mt-2 text-xs font-bold text-slate-300 leading-relaxed">If your phone does not support in-browser scanning, type your member code or bio ID below.</p>
                </div>
            </div>
        </div>

        <form id="qa-form" action="<?php echo BASE_URL; ?>/attendance/quickMark" method="POST" class="p-6 sm:p-8 lg:p-10 space-y-8">
            <input type="hidden" name="service_date" value="<?php echo htmlspecialchars($serviceDate); ?>">
            <input type="hidden" name="service_type" value="<?php echo htmlspecialchars($serviceType); ?>">

            <div class="space-y-3">
                <label class="block text-[9px] font-black text-slate-500 uppercase tracking-widest ml-1">Member Code / Bio ID</label>
                <div class="relative group">
                    <i class="fas fa-id-badge absolute left-5 top-1/2 -translate-y-1/2 text-slate-600 group-focus-within:text-accent transition-colors"></i>
                    <input id="qa-code" autofocus type="text" name="member_code" class="w-full bg-white/5 border border-white/10 focus:border-accent rounded-2xl pl-14 pr-6 py-4 text-sm font-black text-slate-200 transition-all outline-none" placeholder="Example: MEMABC123 or BIO-001">
                </div>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="h-12 px-8 rounded-2xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest hover-glow-yellow active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                    Submit
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const scanBtn = document.getElementById('qa-scan-btn');
        const stopBtn = document.getElementById('qa-stop-btn');
        const wrap = document.getElementById('qa-scanner-wrap');
        const video = document.getElementById('qa-video');
        const codeInput = document.getElementById('qa-code');
        const form = document.getElementById('qa-form');
        const errEl = document.getElementById('qa-scan-err');

        if (!scanBtn || !stopBtn || !wrap || !video || !codeInput || !form) return;

        let stream = null;
        let detector = null;
        let running = false;
        let tick = 0;

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

        scanBtn.addEventListener('click', start);
        stopBtn.addEventListener('click', stop);
        window.addEventListener('pagehide', stop);
    })();
</script>
