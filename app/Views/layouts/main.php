<?php 
    $current_route = trim($_SERVER['REQUEST_URI'], '/');
    $scriptName = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
    if ($scriptName && strpos($current_route, $scriptName) === 0) {
        $current_route = trim(substr($current_route, strlen($scriptName)), '/');
    }
    if (($pos = strpos($current_route, '?')) !== false) {
        $current_route = substr($current_route, 0, $pos);
    }
    if ($current_route === '') $current_route = 'dashboard';
    $isDeptHead = Auth::isDepartmentHead();
    $isStaff = Auth::isStaff();
    $isFinanceHead = Auth::isFinanceHead();
    $isVisitationTeam = Auth::isVisitationTeam();
    $isAuditor = Auth::isAuditor();
    $isPastor = Auth::isPastor();

    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $theme = AppConfig::getSetting('theme', 'dark');
    $theme = in_array($theme, ['dark', 'light', 'ocean', 'sunset'], true) ? $theme : 'dark';
    $logoRelativePath = Branding::getLogoPath();
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Dashboard'; ?> | CMS Admin</title>
    <?php if ($logoRelativePath): ?>
        <link rel="icon" type="image/png" href="<?php echo BASE_URL . '/' . $logoRelativePath; ?>">
        <link rel="shortcut icon" href="<?php echo BASE_URL . '/' . $logoRelativePath; ?>">
    <?php endif; ?>
    <script>
        (function () {
            const holidayTheme = (function () {
                const pad2 = (n) => String(n).padStart(2, '0');
                const keyOf = (d) => pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
                const isSameDay = (a, b) => a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate();

                const nthWeekdayOfMonth = (year, monthIndex, weekday, nth) => {
                    const first = new Date(year, monthIndex, 1);
                    const offset = (weekday - first.getDay() + 7) % 7;
                    return new Date(year, monthIndex, 1 + offset + (nth - 1) * 7);
                };

                const easterSunday = (year) => {
                    const a = year % 19;
                    const b = Math.floor(year / 100);
                    const c = year % 100;
                    const d = Math.floor(b / 4);
                    const e = b % 4;
                    const f = Math.floor((b + 8) / 25);
                    const g = Math.floor((b - f + 1) / 3);
                    const h = (19 * a + b - d - g + 15) % 30;
                    const i = Math.floor(c / 4);
                    const k = c % 4;
                    const l = (32 + 2 * e + 2 * i - h - k) % 7;
                    const m = Math.floor((a + 11 * h + 22 * l) / 451);
                    const month = Math.floor((h + l - 7 * m + 114) / 31); // 3=March, 4=April
                    const day = ((h + l - 7 * m + 114) % 31) + 1;
                    return new Date(year, month - 1, day);
                };

                try {
                    const today = new Date();
                    const y = today.getFullYear();

                    const fixed = {
                        '12-25': 'christmas',
                        '12-26': 'christmas',
                        '01-01': 'newyear',
                        '09-21': 'foundersday'
                    };

                    const fixedTheme = fixed[keyOf(today)] || '';
                    if (fixedTheme) return fixedTheme;

                    const mother = nthWeekdayOfMonth(y, 4, 0, 2); // 2nd Sunday May
                    if (isSameDay(today, mother)) return 'mothersday';

                    const father = nthWeekdayOfMonth(y, 5, 0, 3); // 3rd Sunday June
                    if (isSameDay(today, father)) return 'fathersday';

                    const easter = easterSunday(y);
                    const goodFriday = new Date(easter); goodFriday.setDate(easter.getDate() - 2);
                    const easterMonday = new Date(easter); easterMonday.setDate(easter.getDate() + 1);
                    if (isSameDay(today, goodFriday) || isSameDay(today, easter) || isSameDay(today, easterMonday)) return 'easter';

                    return '';
                } catch (e) {
                    return '';
                }
            })();

            if (holidayTheme) {
                document.documentElement.setAttribute('data-theme', holidayTheme);
                return;
            }

            try {
                const t = localStorage.getItem('uiTheme');
                if (['dark', 'light', 'ocean', 'sunset'].includes(t || '')) {
                    document.documentElement.setAttribute('data-theme', t);
                }
            } catch (e) {}
        })();
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #0f172a; /* Blue-black */
            --primary-accent: #fbbf24; /* Yellow */
            --accent-glow: rgba(251, 191, 36, 0.4);
            --sphere-glow-strong: rgba(251, 191, 36, 0.15);
            --sphere-glow-soft: rgba(251, 191, 36, 0.05);
            --sidebar-bg: #020617;
            --card-glass: rgba(255, 255, 255, 0.03);
            --border-glass: rgba(255, 255, 255, 0.1);
            --text-color: #f8fafc;
            --body-bg: radial-gradient(circle at top right, #1e293b, #0f172a);
            --select-bg: rgba(2, 6, 23, 0.85);
            --select-text: #e2e8f0;
            --select-option-bg: #020617;
            --select-option-text: #e2e8f0;
        }

        html[data-theme="light"] {
            --sidebar-bg: rgba(255, 255, 255, 0.85);
            --card-glass: rgba(255, 255, 255, 0.78);
            --border-glass: rgba(15, 23, 42, 0.12);
            --text-color: #0f172a;
            --body-bg: radial-gradient(circle at top right, #ffffff, #f1f5f9);
            --select-bg: rgba(255, 255, 255, 0.95);
            --select-text: #0f172a;
            --select-option-bg: #ffffff;
            --select-option-text: #0f172a;
        }

        html[data-theme="ocean"] {
            --primary-accent: #38bdf8;
            --accent-glow: rgba(56, 189, 248, 0.42);
            --sphere-glow-strong: rgba(56, 189, 248, 0.16);
            --sphere-glow-soft: rgba(56, 189, 248, 0.05);
            --sidebar-bg: #00121f;
            --border-glass: rgba(56, 189, 248, 0.18);
            --body-bg: radial-gradient(circle at top right, #0b4a6b, #00121f);
            --select-bg: rgba(0, 18, 31, 0.9);
            --select-option-bg: #00121f;
        }

        html[data-theme="sunset"] {
            --primary-accent: #fb7185;
            --accent-glow: rgba(251, 113, 133, 0.42);
            --sphere-glow-strong: rgba(251, 113, 133, 0.16);
            --sphere-glow-soft: rgba(251, 113, 133, 0.05);
            --sidebar-bg: #120a1d;
            --card-glass: rgba(255, 255, 255, 0.055);
            --border-glass: rgba(251, 113, 133, 0.22);
            --body-bg: radial-gradient(circle at top right, #3b1830 0%, #141026 60%, #070a18 100%);
            --select-bg: rgba(7, 10, 24, 0.92);
            --select-option-bg: #070a18;
        }

        html[data-theme="christmas"] {
            --primary-accent: #dc2626;
            --accent-glow: rgba(220, 38, 38, 0.42);
            --sphere-glow-strong: rgba(251, 191, 36, 0.14);
            --sphere-glow-soft: rgba(34, 197, 94, 0.06);
            --sidebar-bg: #2a0b0b;
            --card-glass: rgba(2, 6, 23, 0.76);
            --border-glass: rgba(34, 197, 94, 0.22);
            --text-color: #f8fafc;
            --body-bg: radial-gradient(circle at top right, #166534 0%, #7f1d1d 48%, #220808 100%);
            --select-bg: rgba(42, 11, 11, 0.92);
            --select-text: #f8fafc;
            --select-option-bg: #2a0b0b;
            --select-option-text: #f8fafc;
        }

        html[data-theme="newyear"] {
            --primary-accent: #a78bfa;
            --accent-glow: rgba(167, 139, 250, 0.42);
            --sphere-glow-strong: rgba(167, 139, 250, 0.16);
            --sphere-glow-soft: rgba(167, 139, 250, 0.05);
            --sidebar-bg: #0b0a1a;
            --card-glass: rgba(2, 6, 23, 0.76);
            --border-glass: rgba(167, 139, 250, 0.18);
            --body-bg: radial-gradient(circle at top right, #312e81, #0b0a1a);
            --select-bg: rgba(11, 10, 26, 0.92);
            --select-option-bg: #0b0a1a;
        }

        html[data-theme="easter"] {
            --primary-accent: #c084fc;
            --accent-glow: rgba(192, 132, 252, 0.42);
            --sphere-glow-strong: rgba(192, 132, 252, 0.16);
            --sphere-glow-soft: rgba(192, 132, 252, 0.05);
            --sidebar-bg: #120726;
            --card-glass: rgba(2, 6, 23, 0.76);
            --border-glass: rgba(192, 132, 252, 0.18);
            --body-bg: radial-gradient(circle at top right, #4c1d95, #120726);
            --select-bg: rgba(18, 7, 38, 0.92);
            --select-option-bg: #120726;
        }

        html[data-theme="mothersday"] {
            --primary-accent: #f472b6;
            --accent-glow: rgba(244, 114, 182, 0.42);
            --sphere-glow-strong: rgba(244, 114, 182, 0.16);
            --sphere-glow-soft: rgba(244, 114, 182, 0.05);
            --sidebar-bg: #1a0b1f;
            --card-glass: rgba(2, 6, 23, 0.76);
            --border-glass: rgba(244, 114, 182, 0.18);
            --body-bg: radial-gradient(circle at top right, #5b1b4a, #1a0b1f);
            --select-bg: rgba(26, 11, 31, 0.92);
            --select-option-bg: #1a0b1f;
        }

        html[data-theme="fathersday"] {
            --primary-accent: #60a5fa;
            --accent-glow: rgba(96, 165, 250, 0.42);
            --sphere-glow-strong: rgba(96, 165, 250, 0.16);
            --sphere-glow-soft: rgba(96, 165, 250, 0.05);
            --sidebar-bg: #031225;
            --card-glass: rgba(2, 6, 23, 0.76);
            --border-glass: rgba(96, 165, 250, 0.18);
            --body-bg: radial-gradient(circle at top right, #1d4ed8, #031225);
            --select-bg: rgba(3, 18, 37, 0.92);
            --select-option-bg: #031225;
        }

        html[data-theme="foundersday"] {
            --primary-accent: #fbbf24;
            --accent-glow: rgba(251, 191, 36, 0.42);
            --sphere-glow-strong: rgba(251, 191, 36, 0.16);
            --sphere-glow-soft: rgba(251, 191, 36, 0.05);
            --sidebar-bg: #17110a;
            --card-glass: rgba(2, 6, 23, 0.76);
            --border-glass: rgba(251, 191, 36, 0.18);
            --body-bg: radial-gradient(circle at top right, #7c2d12, #17110a);
            --select-bg: rgba(23, 17, 10, 0.92);
            --select-option-bg: #17110a;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: var(--body-bg);
            background-attachment: fixed;
            color: var(--text-color);
            min-height: 100vh;
        }

        /* Live Animated Gradient Background */
        .live-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(125deg, #020617 0%, #0f172a 50%, #1e293b 100%);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
        }

        html[data-theme="light"] .live-bg {
            background: linear-gradient(125deg, #ffffff 0%, #f1f5f9 50%, #e2e8f0 100%);
        }

        html[data-theme="ocean"] .live-bg {
            background: linear-gradient(125deg, #00121f 0%, #062a4d 50%, #0ea5e9 100%);
        }

        html[data-theme="sunset"] .live-bg {
            background: linear-gradient(125deg, #070a18 0%, #141026 40%, #4a1730 70%, #fb7185 100%);
        }

        html[data-theme="christmas"] .live-bg {
            background: linear-gradient(125deg, #2a0b0b 0%, #7f1d1d 38%, #166534 72%, #052e16 100%);
        }

        html[data-theme="newyear"] .live-bg {
            background: linear-gradient(125deg, #0b0a1a 0%, #312e81 50%, #a78bfa 100%);
        }

        html[data-theme="easter"] .live-bg {
            background: linear-gradient(125deg, #120726 0%, #4c1d95 50%, #c084fc 100%);
        }

        html[data-theme="mothersday"] .live-bg {
            background: linear-gradient(125deg, #1a0b1f 0%, #5b1b4a 50%, #f472b6 100%);
        }

        html[data-theme="fathersday"] .live-bg {
            background: linear-gradient(125deg, #031225 0%, #1d4ed8 50%, #60a5fa 100%);
        }

        html[data-theme="foundersday"] .live-bg {
            background: linear-gradient(125deg, #17110a 0%, #7c2d12 50%, #fbbf24 100%);
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* 3D Floating Glass Objects */
        .glass-sphere {
            position: fixed;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, var(--sphere-glow-strong), var(--sphere-glow-soft));
            backdrop-filter: blur(40px);
            -webkit-backdrop-filter: blur(40px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            z-index: -1;
            pointer-events: none;
            box-shadow: inset 0 0 40px rgba(255, 255, 255, 0.04), 0 20px 50px rgba(0, 0, 0, 0.2);
        }

        .sphere-1 {
            width: 400px;
            height: 400px;
            top: -100px;
            right: -100px;
            animation: float1 25s infinite alternate ease-in-out;
        }

        .sphere-2 {
            width: 300px;
            height: 300px;
            bottom: -50px;
            left: -50px;
            animation: float2 30s infinite alternate ease-in-out;
        }

        .sphere-3 {
            width: 200px;
            height: 200px;
            top: 40%;
            left: 20%;
            animation: float3 20s infinite alternate ease-in-out;
        }

        @keyframes float1 {
            0% { transform: translate(0, 0) rotate(0deg); }
            100% { transform: translate(-150px, 200px) rotate(360deg); }
        }

        @keyframes float2 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(200px, -150px) scale(1.2); }
        }

        @keyframes float3 {
            0% { transform: translate(0, 0) scale(1.2); }
            100% { transform: translate(100px, 100px) scale(0.8); }
        }

        .sidebar-gradient {
            background: var(--sidebar-bg);
            border-right: 1px solid var(--border-glass);
        }

        .active-link {
            background-color: var(--primary-accent);
            color: var(--primary-dark) !important;
            box-shadow: 0 0 20px var(--accent-glow);
        }

        .active-link i {
            color: var(--primary-dark) !important;
        }

        .glass-card {
            background: var(--card-glass);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid var(--border-glass);
        }

        select {
            background-color: var(--select-bg) !important;
            color: var(--select-text) !important;
        }
        select option {
            background-color: var(--select-option-bg) !important;
            color: var(--select-option-text) !important;
        }

        html[data-theme="light"] .text-white { color: #0f172a !important; }
        html[data-theme="light"] .text-slate-200 { color: #0f172a !important; }
        html[data-theme="light"] .text-slate-300 { color: #1f2937 !important; }
        html[data-theme="light"] .text-slate-400 { color: #334155 !important; }
        html[data-theme="light"] .text-slate-500 { color: #475569 !important; }
        html[data-theme="light"] .bg-white\\/5 { background-color: rgba(15, 23, 42, 0.06) !important; }
        html[data-theme="light"] .bg-white\\/10 { background-color: rgba(15, 23, 42, 0.08) !important; }
        html[data-theme="light"] .border-white\\/10 { border-color: rgba(15, 23, 42, 0.12) !important; }
        html[data-theme="light"] .border-white\\/5 { border-color: rgba(15, 23, 42, 0.10) !important; }
        html[data-theme="light"] .bg-slate-900,
        html[data-theme="light"] .bg-slate-900\/50,
        html[data-theme="light"] .bg-slate-950\/80,
        html[data-theme="light"] .bg-slate-950\/90 {
            background-color: rgba(255, 255, 255, 0.92) !important;
        }
        html[data-theme="light"] .card-interaction:hover {
            background: rgba(15, 23, 42, 0.03);
        }

        html[data-theme="sunset"] .text-slate-200 { color: rgba(248, 250, 252, 0.92) !important; }
        html[data-theme="sunset"] .text-slate-300 { color: rgba(226, 232, 240, 0.86) !important; }
        html[data-theme="sunset"] .text-slate-400 { color: rgba(226, 232, 240, 0.72) !important; }
        html[data-theme="sunset"] .text-slate-500 { color: rgba(226, 232, 240, 0.6) !important; }
        html[data-theme="sunset"] .text-slate-600 { color: rgba(226, 232, 240, 0.5) !important; }
        html[data-theme="sunset"] .text-gray-200 { color: rgba(248, 250, 252, 0.92) !important; }
        html[data-theme="sunset"] .text-gray-300 { color: rgba(226, 232, 240, 0.86) !important; }
        html[data-theme="sunset"] .text-gray-400 { color: rgba(226, 232, 240, 0.72) !important; }
        html[data-theme="sunset"] .text-gray-500 { color: rgba(226, 232, 240, 0.6) !important; }
        html[data-theme="sunset"] .text-gray-600 { color: rgba(226, 232, 240, 0.5) !important; }

        html[data-theme="christmas"] .text-slate-200 { color: rgba(255, 255, 255, 0.94) !important; }
        html[data-theme="christmas"] .text-slate-300 { color: rgba(240, 253, 244, 0.88) !important; }
        html[data-theme="christmas"] .text-slate-400 { color: rgba(220, 252, 231, 0.72) !important; }
        html[data-theme="christmas"] .text-slate-500 { color: rgba(254, 242, 242, 0.66) !important; }
        html[data-theme="christmas"] .text-slate-600 { color: rgba(254, 242, 242, 0.54) !important; }
        html[data-theme="christmas"] .text-gray-200 { color: rgba(255, 255, 255, 0.94) !important; }
        html[data-theme="christmas"] .text-gray-300 { color: rgba(240, 253, 244, 0.88) !important; }
        html[data-theme="christmas"] .text-gray-400 { color: rgba(220, 252, 231, 0.72) !important; }
        html[data-theme="christmas"] .text-gray-500 { color: rgba(254, 242, 242, 0.66) !important; }
        html[data-theme="christmas"] .text-gray-600 { color: rgba(254, 242, 242, 0.54) !important; }

        html[data-theme="sunset"] .bg-slate-900 { background-color: rgba(20, 16, 38, 0.74) !important; }
        html[data-theme="sunset"] .bg-slate-900\/50 { background-color: rgba(20, 16, 38, 0.58) !important; }
        html[data-theme="sunset"] .bg-slate-900\/80 { background-color: rgba(20, 16, 38, 0.76) !important; }
        html[data-theme="sunset"] .bg-slate-950 { background-color: rgba(7, 10, 24, 0.86) !important; }
        html[data-theme="sunset"] .bg-slate-950\/40 { background-color: rgba(7, 10, 24, 0.56) !important; }
        html[data-theme="sunset"] .bg-slate-950\/50 { background-color: rgba(7, 10, 24, 0.62) !important; }
        html[data-theme="sunset"] .bg-slate-950\/80 { background-color: rgba(7, 10, 24, 0.78) !important; }
        html[data-theme="sunset"] .bg-slate-950\/90 { background-color: rgba(7, 10, 24, 0.84) !important; }
        html[data-theme="sunset"] .bg-slate-800 { background-color: rgba(28, 19, 51, 0.68) !important; }
        html[data-theme="sunset"] .bg-slate-800\/50 { background-color: rgba(28, 19, 51, 0.56) !important; }
        html[data-theme="sunset"] .bg-slate-700 { background-color: rgba(59, 24, 48, 0.55) !important; }
        html[data-theme="sunset"] .bg-slate-700\/50 { background-color: rgba(59, 24, 48, 0.46) !important; }

        html[data-theme="christmas"] .bg-slate-900 { background-color: rgba(2, 6, 23, 0.84) !important; }
        html[data-theme="christmas"] .bg-slate-900\/50 { background-color: rgba(2, 6, 23, 0.66) !important; }
        html[data-theme="christmas"] .bg-slate-900\/80 { background-color: rgba(2, 6, 23, 0.8) !important; }
        html[data-theme="christmas"] .bg-slate-950 { background-color: rgba(1, 3, 10, 0.9) !important; }
        html[data-theme="christmas"] .bg-slate-950\/40 { background-color: rgba(1, 3, 10, 0.58) !important; }
        html[data-theme="christmas"] .bg-slate-950\/50 { background-color: rgba(1, 3, 10, 0.66) !important; }
        html[data-theme="christmas"] .bg-slate-950\/80 { background-color: rgba(1, 3, 10, 0.84) !important; }
        html[data-theme="christmas"] .bg-slate-950\/90 { background-color: rgba(1, 3, 10, 0.9) !important; }
        html[data-theme="christmas"] .bg-slate-800 { background-color: rgba(15, 23, 42, 0.72) !important; }
        html[data-theme="christmas"] .bg-slate-800\/50 { background-color: rgba(15, 23, 42, 0.56) !important; }
        html[data-theme="christmas"] .bg-slate-700 { background-color: rgba(30, 41, 59, 0.68) !important; }
        html[data-theme="christmas"] .bg-slate-700\/50 { background-color: rgba(30, 41, 59, 0.52) !important; }

        html[data-theme="sunset"] .border-slate-900,
        html[data-theme="sunset"] .border-slate-800,
        html[data-theme="sunset"] .border-slate-700,
        html[data-theme="sunset"] .border-slate-600,
        html[data-theme="sunset"] .border-slate-500,
        html[data-theme="sunset"] .border-gray-900,
        html[data-theme="sunset"] .border-gray-800,
        html[data-theme="sunset"] .border-gray-700,
        html[data-theme="sunset"] .border-gray-600,
        html[data-theme="sunset"] .border-gray-500 {
            border-color: var(--border-glass) !important;
        }

        html[data-theme="christmas"] .border-slate-900,
        html[data-theme="christmas"] .border-slate-800,
        html[data-theme="christmas"] .border-slate-700,
        html[data-theme="christmas"] .border-slate-600,
        html[data-theme="christmas"] .border-slate-500,
        html[data-theme="christmas"] .border-gray-900,
        html[data-theme="christmas"] .border-gray-800,
        html[data-theme="christmas"] .border-gray-700,
        html[data-theme="christmas"] .border-gray-600,
        html[data-theme="christmas"] .border-gray-500 {
            border-color: rgba(34, 197, 94, 0.24) !important;
        }

        html[data-theme="sunset"] .text-yellow-300,
        html[data-theme="sunset"] .text-yellow-400,
        html[data-theme="sunset"] .text-yellow-500,
        html[data-theme="sunset"] .text-yellow-600,
        html[data-theme="sunset"] .border-yellow-300,
        html[data-theme="sunset"] .border-yellow-400,
        html[data-theme="sunset"] .border-yellow-500,
        html[data-theme="sunset"] .border-yellow-600 {
            color: var(--primary-accent) !important;
            border-color: var(--primary-accent) !important;
        }

        html[data-theme="sunset"] .bg-yellow-300,
        html[data-theme="sunset"] .bg-yellow-400,
        html[data-theme="sunset"] .bg-yellow-500,
        html[data-theme="sunset"] .bg-yellow-600 {
            background-color: var(--primary-accent) !important;
        }

        html[data-theme="christmas"] .text-yellow-300,
        html[data-theme="christmas"] .text-yellow-400,
        html[data-theme="christmas"] .text-yellow-500,
        html[data-theme="christmas"] .text-yellow-600,
        html[data-theme="christmas"] .border-yellow-300,
        html[data-theme="christmas"] .border-yellow-400,
        html[data-theme="christmas"] .border-yellow-500,
        html[data-theme="christmas"] .border-yellow-600 {
            color: #22c55e !important;
            border-color: #22c55e !important;
        }

        html[data-theme="christmas"] .bg-yellow-300,
        html[data-theme="christmas"] .bg-yellow-400,
        html[data-theme="christmas"] .bg-yellow-500,
        html[data-theme="christmas"] .bg-yellow-600 {
            background-color: #dc2626 !important;
        }

        html[data-theme="christmas"] .bg-white\/5 { background-color: rgba(2, 6, 23, 0.34) !important; }
        html[data-theme="christmas"] .bg-white\/10 { background-color: rgba(2, 6, 23, 0.48) !important; }
        html[data-theme="christmas"] .border-white\/10 { border-color: rgba(34, 197, 94, 0.22) !important; }
        html[data-theme="christmas"] .border-white\/5 { border-color: rgba(220, 38, 38, 0.16) !important; }

        html[data-theme="newyear"] .bg-slate-900,
        html[data-theme="easter"] .bg-slate-900,
        html[data-theme="mothersday"] .bg-slate-900,
        html[data-theme="fathersday"] .bg-slate-900,
        html[data-theme="foundersday"] .bg-slate-900 { background-color: rgba(2, 6, 23, 0.84) !important; }

        html[data-theme="newyear"] .bg-slate-900\/50,
        html[data-theme="easter"] .bg-slate-900\/50,
        html[data-theme="mothersday"] .bg-slate-900\/50,
        html[data-theme="fathersday"] .bg-slate-900\/50,
        html[data-theme="foundersday"] .bg-slate-900\/50 { background-color: rgba(2, 6, 23, 0.66) !important; }

        html[data-theme="newyear"] .bg-slate-900\/80,
        html[data-theme="easter"] .bg-slate-900\/80,
        html[data-theme="mothersday"] .bg-slate-900\/80,
        html[data-theme="fathersday"] .bg-slate-900\/80,
        html[data-theme="foundersday"] .bg-slate-900\/80 { background-color: rgba(2, 6, 23, 0.8) !important; }

        html[data-theme="newyear"] .bg-slate-950,
        html[data-theme="easter"] .bg-slate-950,
        html[data-theme="mothersday"] .bg-slate-950,
        html[data-theme="fathersday"] .bg-slate-950,
        html[data-theme="foundersday"] .bg-slate-950 { background-color: rgba(1, 3, 10, 0.9) !important; }

        html[data-theme="newyear"] .bg-slate-950\/40,
        html[data-theme="easter"] .bg-slate-950\/40,
        html[data-theme="mothersday"] .bg-slate-950\/40,
        html[data-theme="fathersday"] .bg-slate-950\/40,
        html[data-theme="foundersday"] .bg-slate-950\/40 { background-color: rgba(1, 3, 10, 0.58) !important; }

        html[data-theme="newyear"] .bg-slate-950\/50,
        html[data-theme="easter"] .bg-slate-950\/50,
        html[data-theme="mothersday"] .bg-slate-950\/50,
        html[data-theme="fathersday"] .bg-slate-950\/50,
        html[data-theme="foundersday"] .bg-slate-950\/50 { background-color: rgba(1, 3, 10, 0.66) !important; }

        html[data-theme="newyear"] .bg-slate-950\/80,
        html[data-theme="easter"] .bg-slate-950\/80,
        html[data-theme="mothersday"] .bg-slate-950\/80,
        html[data-theme="fathersday"] .bg-slate-950\/80,
        html[data-theme="foundersday"] .bg-slate-950\/80 { background-color: rgba(1, 3, 10, 0.84) !important; }

        html[data-theme="newyear"] .bg-slate-950\/90,
        html[data-theme="easter"] .bg-slate-950\/90,
        html[data-theme="mothersday"] .bg-slate-950\/90,
        html[data-theme="fathersday"] .bg-slate-950\/90,
        html[data-theme="foundersday"] .bg-slate-950\/90 { background-color: rgba(1, 3, 10, 0.9) !important; }

        html[data-theme="newyear"] .bg-slate-800,
        html[data-theme="easter"] .bg-slate-800,
        html[data-theme="mothersday"] .bg-slate-800,
        html[data-theme="fathersday"] .bg-slate-800,
        html[data-theme="foundersday"] .bg-slate-800 { background-color: rgba(15, 23, 42, 0.72) !important; }

        html[data-theme="newyear"] .bg-slate-800\/50,
        html[data-theme="easter"] .bg-slate-800\/50,
        html[data-theme="mothersday"] .bg-slate-800\/50,
        html[data-theme="fathersday"] .bg-slate-800\/50,
        html[data-theme="foundersday"] .bg-slate-800\/50 { background-color: rgba(15, 23, 42, 0.56) !important; }

        html[data-theme="newyear"] .bg-slate-700,
        html[data-theme="easter"] .bg-slate-700,
        html[data-theme="mothersday"] .bg-slate-700,
        html[data-theme="fathersday"] .bg-slate-700,
        html[data-theme="foundersday"] .bg-slate-700 { background-color: rgba(30, 41, 59, 0.68) !important; }

        html[data-theme="newyear"] .bg-slate-700\/50,
        html[data-theme="easter"] .bg-slate-700\/50,
        html[data-theme="mothersday"] .bg-slate-700\/50,
        html[data-theme="fathersday"] .bg-slate-700\/50,
        html[data-theme="foundersday"] .bg-slate-700\/50 { background-color: rgba(30, 41, 59, 0.52) !important; }

        html[data-theme="newyear"] .bg-white\/5,
        html[data-theme="easter"] .bg-white\/5,
        html[data-theme="mothersday"] .bg-white\/5,
        html[data-theme="fathersday"] .bg-white\/5,
        html[data-theme="foundersday"] .bg-white\/5 { background-color: rgba(2, 6, 23, 0.34) !important; }

        html[data-theme="newyear"] .bg-white\/10,
        html[data-theme="easter"] .bg-white\/10,
        html[data-theme="mothersday"] .bg-white\/10,
        html[data-theme="fathersday"] .bg-white\/10,
        html[data-theme="foundersday"] .bg-white\/10 { background-color: rgba(2, 6, 23, 0.48) !important; }

        html[data-theme="newyear"] .border-white\/10,
        html[data-theme="easter"] .border-white\/10,
        html[data-theme="mothersday"] .border-white\/10,
        html[data-theme="fathersday"] .border-white\/10,
        html[data-theme="foundersday"] .border-white\/10 { border-color: rgba(255, 255, 255, 0.16) !important; }

        html[data-theme="newyear"] .border-white\/5,
        html[data-theme="easter"] .border-white\/5,
        html[data-theme="mothersday"] .border-white\/5,
        html[data-theme="fathersday"] .border-white\/5,
        html[data-theme="foundersday"] .border-white\/5 { border-color: rgba(255, 255, 255, 0.12) !important; }

        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.16);
            border-radius: 10px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: var(--primary-accent);
        }

        html[data-theme="sunset"] .shadow-yellow-500\/10 { --tw-shadow-color: rgba(251, 113, 133, 0.12) !important; }
        html[data-theme="sunset"] .shadow-yellow-500\/20 { --tw-shadow-color: rgba(251, 113, 133, 0.2) !important; }
        html[data-theme="sunset"] .shadow-yellow-500\/30 { --tw-shadow-color: rgba(251, 113, 133, 0.3) !important; }
        html[data-theme="light"] .shadow-yellow-500\/10 { --tw-shadow-color: rgba(251, 191, 36, 0.12) !important; }
        html[data-theme="light"] .shadow-yellow-500\/20 { --tw-shadow-color: rgba(251, 191, 36, 0.2) !important; }
        html[data-theme="light"] .shadow-yellow-500\/30 { --tw-shadow-color: rgba(251, 191, 36, 0.3) !important; }

        /* Typography & Utility */
        .text-accent { color: var(--primary-accent); }
        .bg-accent { background-color: var(--primary-accent); }

        /* Colorful Reusable Hovers */
        .hover-glow-yellow:hover {
            box-shadow: 0 0 25px var(--accent-glow);
            transform: translateY(-3px);
            border-color: rgba(255, 255, 255, 0.22) !important;
        }
        .hover-glow-blue:hover {
            box-shadow: 0 0 25px rgba(59, 130, 246, 0.4);
            transform: translateY(-3px);
            border-color: rgba(59, 130, 246, 0.5) !important;
        }
        .hover-glow-green:hover {
            box-shadow: 0 0 25px rgba(34, 197, 94, 0.4);
            transform: translateY(-3px);
            border-color: rgba(34, 197, 94, 0.5) !important;
        }
        .hover-glow-red:hover {
            box-shadow: 0 0 25px rgba(239, 68, 68, 0.4);
            transform: translateY(-3px);
            border-color: rgba(239, 68, 68, 0.5) !important;
        }
        .hover-glow-purple:hover {
            box-shadow: 0 0 25px rgba(168, 85, 247, 0.4);
            transform: translateY(-3px);
            border-color: rgba(168, 85, 247, 0.5) !important;
        }

        /* Sidebar Item Hover */
        .nav-item-hover {
            position: relative;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .nav-item-hover:hover:not(.active-link) {
            background: rgba(255, 255, 255, 0.05);
            padding-left: 1.5rem;
            color: var(--primary-accent) !important;
        }
        .nav-item-hover:hover i {
            color: var(--primary-accent) !important;
            transform: scale(1.2);
        }

        /* Card Scale Hover */
        .card-interaction {
            transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .card-interaction:hover {
            transform: translateY(-8px) scale(1.01);
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .scroll-top-button {
            opacity: 0;
            pointer-events: none;
            transform: translateY(18px);
            transition: opacity 0.25s ease, transform 0.25s ease, box-shadow 0.25s ease;
        }

        .scroll-top-button.is-visible {
            opacity: 1;
            pointer-events: auto;
            transform: translateY(0);
        }

        .page-loader-overlay {
            position: fixed;
            inset: 0;
            z-index: 110;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(2, 6, 23, 0.82);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }

        .page-loader-overlay.is-hidden {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .page-loader-card {
            width: min(100%, 24rem);
            border-radius: 1.75rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(15, 23, 42, 0.88);
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 30px 90px rgba(2, 6, 23, 0.45);
        }

        html[data-theme="light"] .page-loader-card {
            background: rgba(255, 255, 255, 0.92);
            border-color: rgba(15, 23, 42, 0.1);
        }

        .page-loader-spinner {
            width: 3.25rem;
            height: 3.25rem;
            margin: 0 auto 1rem;
            border-radius: 9999px;
            border: 4px solid rgba(255, 255, 255, 0.12);
            border-top-color: var(--primary-accent);
            animation: loaderSpin 0.8s linear infinite;
        }

        html[data-theme="light"] .page-loader-spinner {
            border-color: rgba(15, 23, 42, 0.12);
            border-top-color: var(--primary-accent);
        }

        .upload-progress-overlay {
            position: fixed;
            inset: 0;
            z-index: 115;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(2, 6, 23, 0.88);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .upload-progress-card {
            width: min(100%, 28rem);
            border-radius: 1.75rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(15, 23, 42, 0.94);
            padding: 1.5rem;
            box-shadow: 0 30px 90px rgba(2, 6, 23, 0.55);
        }

        html[data-theme="light"] .upload-progress-card {
            background: rgba(255, 255, 255, 0.96);
            border-color: rgba(15, 23, 42, 0.1);
        }

        .upload-progress-track {
            overflow: hidden;
            height: 0.9rem;
            width: 100%;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.08);
        }

        html[data-theme="light"] .upload-progress-track {
            background: rgba(15, 23, 42, 0.08);
        }

        .upload-progress-bar {
            height: 100%;
            width: 0;
            border-radius: inherit;
            background: linear-gradient(90deg, #fbbf24 0%, #3b82f6 100%);
            box-shadow: 0 0 22px rgba(251, 191, 36, 0.4);
            transition: width 0.2s ease;
        }

        @keyframes loaderSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .top-action-loader {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            z-index: 125;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }

        html[data-theme="light"] .top-action-loader {
            background: rgba(15, 23, 42, 0.08);
        }

        .top-action-loader-bar {
            height: 100%;
            width: 40%;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0), var(--primary-accent), rgba(255, 255, 255, 0));
            box-shadow: 0 0 18px var(--accent-glow);
            animation: topLoaderMove 1.05s ease-in-out infinite;
        }

        @keyframes topLoaderMove {
            0% { transform: translateX(-60%); }
            50% { transform: translateX(130%); }
            100% { transform: translateX(-60%); }
        }

        .global-toast-wrap {
            position: fixed;
            top: 0.9rem;
            left: 0.9rem;
            right: 0.9rem;
            z-index: 130;
            display: flex;
            justify-content: center;
            pointer-events: none;
        }

        @media (min-width: 640px) {
            .global-toast-wrap {
                left: auto;
                right: 1.25rem;
                justify-content: flex-end;
                width: 24rem;
            }
        }

        .global-toast {
            pointer-events: auto;
            width: 100%;
            border-radius: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(15, 23, 42, 0.9);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 24px 70px rgba(2, 6, 23, 0.55);
            transform: translateY(-10px);
            opacity: 0;
            transition: opacity 0.18s ease, transform 0.18s ease;
        }

        .global-toast.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        html[data-theme="light"] .global-toast {
            background: rgba(255, 255, 255, 0.95);
            border-color: rgba(15, 23, 42, 0.1);
            box-shadow: 0 24px 70px rgba(15, 23, 42, 0.12);
        }

        .seasonal-particles {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: -1;
        }

        .seasonal-particle {
            position: absolute;
            top: -12vh;
            left: 0;
            opacity: 0;
            transform: translate3d(0, 0, 0);
            will-change: transform, opacity;
        }

        .seasonal-particle.snow {
            border-radius: 9999px;
            background: rgba(191, 219, 254, var(--p-o, 0.85));
            filter: drop-shadow(0 0 10px rgba(191, 219, 254, 0.22));
        }

        .seasonal-particle.star {
            background: rgba(251, 191, 36, var(--p-o, 0.85));
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 92%, 50% 71%, 21% 92%, 32% 57%, 2% 35%, 39% 35%);
            filter: drop-shadow(0 0 12px rgba(251, 191, 36, 0.22));
        }

        .seasonal-particle.sparkle {
            border-radius: 9999px;
            background: rgba(255, 255, 255, var(--p-o, 0.75));
            box-shadow: 0 0 16px rgba(255, 255, 255, 0.18);
        }

        .seasonal-particle.confetti {
            border-radius: 0.35rem;
            background: linear-gradient(180deg, rgba(167, 139, 250, var(--p-o, 0.85)), rgba(59, 130, 246, var(--p-o, 0.72)));
            box-shadow: 0 0 16px rgba(167, 139, 250, 0.15);
        }

        @keyframes seasonalFall {
            0% { transform: translate3d(0, 0, 0) rotate(var(--p-r0, 0deg)); opacity: 0; }
            10% { opacity: var(--p-o, 0.85); }
            100% { transform: translate3d(calc(var(--p-drift, 0) * 1px), 120vh, 0) rotate(var(--p-r1, 360deg)); opacity: 0; }
        }

        @media (prefers-reduced-motion: reduce) {
            .seasonal-particles { display: none; }
        }
    </style>
</head>
<body class="text-gray-100">
    <?php
        $flashSuccess = Session::get('flash_success');
        $flashError = Session::get('flash_error');
        $flashWarning = Session::get('flash_warning');
        if ($flashSuccess !== null) Session::remove('flash_success');
        if ($flashError !== null) Session::remove('flash_error');
        if ($flashWarning !== null) Session::remove('flash_warning');
        $toastType = null;
        $toastMessage = null;
        if (is_string($flashError) && trim($flashError) !== '') {
            $toastType = 'error';
            $toastMessage = trim($flashError);
        } elseif (is_string($flashWarning) && trim($flashWarning) !== '') {
            $toastType = 'warning';
            $toastMessage = trim($flashWarning);
        } elseif (is_string($flashSuccess) && trim($flashSuccess) !== '') {
            $toastType = 'success';
            $toastMessage = trim($flashSuccess);
        }
    ?>
    <div id="top-action-loader" class="top-action-loader hidden" aria-hidden="true">
        <div class="top-action-loader-bar"></div>
    </div>
    <?php if ($toastType && $toastMessage): ?>
        <div class="global-toast-wrap" aria-live="polite">
            <div id="global-toast" class="global-toast px-5 py-4">
                <div class="flex items-start gap-4">
                    <div class="mt-0.5 w-10 h-10 rounded-2xl flex items-center justify-center border border-white/10 <?php echo $toastType === 'success' ? 'bg-emerald-500/10 text-emerald-300' : ($toastType === 'warning' ? 'bg-yellow-500/10 text-yellow-300' : 'bg-rose-500/10 text-rose-300'); ?>">
                        <i class="fas <?php echo $toastType === 'success' ? 'fa-circle-check' : ($toastType === 'warning' ? 'fa-triangle-exclamation' : 'fa-circle-xmark'); ?> text-sm"></i>
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="text-[10px] font-black uppercase tracking-[0.28em] <?php echo $toastType === 'success' ? 'text-emerald-300' : ($toastType === 'warning' ? 'text-yellow-300' : 'text-rose-300'); ?>">
                            <?php echo htmlspecialchars(strtoupper($toastType)); ?>
                        </div>
                        <div class="mt-1 text-sm font-bold text-slate-200 break-words"><?php echo htmlspecialchars($toastMessage); ?></div>
                    </div>
                    <button type="button" id="global-toast-close" class="shrink-0 w-9 h-9 rounded-2xl border border-white/10 bg-white/5 text-slate-300 hover:bg-white/10 transition-all">
                        <i class="fas fa-xmark text-[12px]"></i>
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <div id="global-page-loader" class="page-loader-overlay is-hidden" aria-live="polite">
        <div class="page-loader-card">
            <div class="page-loader-spinner"></div>
            <p id="global-page-loader-text" class="text-sm font-black uppercase tracking-[0.24em] text-accent">Loading</p>
            <p id="global-page-loader-subtext" class="mt-3 text-sm text-slate-300">Please wait while the page gets ready.</p>
        </div>
    </div>
    <div id="global-upload-progress" class="upload-progress-overlay hidden" aria-live="polite">
        <div class="upload-progress-card">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.26em] text-accent">Upload In Progress</p>
                    <p id="global-upload-progress-text" class="mt-2 text-sm font-bold text-slate-200">Uploading your file...</p>
                </div>
                <p id="global-upload-progress-percent" class="text-2xl font-black text-accent">0%</p>
            </div>
            <div class="upload-progress-track mt-5">
                <div id="global-upload-progress-bar" class="upload-progress-bar"></div>
            </div>
            <p id="global-upload-progress-subtext" class="mt-3 text-xs font-bold text-slate-400">Please do not close this page until the upload completes.</p>
        </div>
    </div>
    <div class="live-bg"></div>
    <div id="seasonal-particles" class="seasonal-particles" aria-hidden="true"></div>
    <!-- Floating Background Objects -->
    <div class="glass-sphere sphere-1"></div>
    <div class="glass-sphere sphere-2"></div>
    <div class="glass-sphere sphere-3"></div>
    
    <div class="relative flex min-h-screen lg:h-screen overflow-hidden">
        <div id="mobile-sidebar-overlay" class="fixed inset-0 z-30 hidden bg-slate-950/70 backdrop-blur-sm lg:hidden"></div>
        <!-- Sidebar -->
        <aside id="app-sidebar" class="fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] -translate-x-full flex-col sidebar-gradient text-white shadow-2xl transition-transform duration-300 lg:static lg:z-20 lg:w-64 lg:max-w-none lg:translate-x-0 lg:flex-shrink-0">
            <div class="flex items-center justify-between border-b border-white/5 p-6 lg:block lg:p-8">
                <button type="button" onclick="toggleSidebar(false)" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-slate-300 transition-all hover:bg-white/10 lg:hidden">
                    <i class="fas fa-times text-sm"></i>
                </button>
                <div class="hidden lg:block"></div>
            </div>
            <div class="px-6 pb-6 pt-2 lg:p-8 lg:pt-0 flex flex-col items-center border-b border-white/5">
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mb-4 shadow-xl shadow-yellow-500/20 overflow-hidden bg-transparent border border-white/10">
                    <?php if ($logoRelativePath): ?>
                        <img src="<?php echo BASE_URL . '/' . $logoRelativePath; ?>" alt="Logo" class="w-full h-full object-contain bg-transparent p-1">
                    <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-accent">
                            <i class="fas fa-church text-slate-900 text-3xl"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <span class="text-lg font-extrabold tracking-tight uppercase text-center"><?php echo $churchName; ?></span>
            </div>
            
            <nav class="flex-1 overflow-y-auto py-6 custom-scrollbar">
                <ul class="space-y-2 px-4">
                    <li>
                        <a href="<?php echo BASE_URL; ?>/<?php echo $isAuditor ? 'auditor' : ($isPastor ? 'pastor' : 'dashboard'); ?>" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo ($current_route === ($isAuditor ? 'auditor' : ($isPastor ? 'pastor' : 'dashboard'))) ? 'active-link' : 'text-slate-400'; ?>">
                            <i class="fas fa-th-large w-6 text-sm"></i>
                            <span class="ml-3 text-sm font-bold"><?php echo $isAuditor ? 'Auditor' : 'Dashboard'; ?></span>
                        </a>
                    </li>
                    <?php if (!$isAuditor && !$isPastor && !$isStaff && !$isVisitationTeam): ?>
                    <li>
                        <div class="flex items-center">
                            <a href="<?php echo BASE_URL; ?>/members" class="flex-1 flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'members') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                                <i class="fas fa-users w-6 text-sm"></i>
                                <span class="ml-3 text-sm font-bold">Members</span>
                            </a>
                            <?php if (!$isDeptHead): ?>
                                <button type="button" onclick="toggleMembersSubmenu()" class="ml-2 w-10 h-10 flex items-center justify-center rounded-2xl bg-white/5 border border-white/10 text-slate-400 hover:bg-white/10 transition-all">
                                    <i id="members-chevron" class="fas fa-chevron-down text-[10px] transition-transform"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endif; ?>
                    <?php if (!$isAuditor && !$isPastor && !$isDeptHead && !$isStaff && !$isVisitationTeam): ?>
                        <li>
                            <div id="members-submenu" class="<?php echo (strpos($current_route, 'cluster') === 0 || strpos($current_route, 'departments') === 0) ? '' : 'hidden'; ?>">
                                <a href="<?php echo BASE_URL; ?>/cluster" class="flex items-center px-5 py-2.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'cluster') === 0 ? 'active-link' : 'text-slate-400'; ?> ml-8">
                                    <i class="fas fa-layer-group w-6 text-[11px]"></i>
                                    <span class="ml-3 text-[11px] font-black uppercase tracking-widest">Groups</span>
                                </a>
                                <a href="<?php echo BASE_URL; ?>/departments" class="flex items-center px-5 py-2.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'departments') === 0 ? 'active-link' : 'text-slate-400'; ?> ml-8 mt-1">
                                    <i class="fas fa-sitemap w-6 text-[11px]"></i>
                                    <span class="ml-3 text-[11px] font-black uppercase tracking-widest">Departments</span>
                                </a>
                            </div>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/attendance" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'attendance') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                                <i class="fas fa-check-square w-6 text-sm"></i>
                                <span class="ml-3 text-sm font-bold">Attendance</span>
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (!$isAuditor && !$isPastor && !$isVisitationTeam): ?>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/finance" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'finance') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                            <i class="fas fa-wallet w-6 text-sm"></i>
                            <span class="ml-3 text-sm font-bold">Finance</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/transactions" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'transactions') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                            <i class="fas fa-receipt w-6 text-sm"></i>
                            <span class="ml-3 text-sm font-bold">Transactions</span>
                        </a>
                    </li>
                    <li>
                        <a href="<?php echo BASE_URL; ?>/department-savings" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'department-savings') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                            <i class="fas fa-piggy-bank w-6 text-sm"></i>
                            <span class="ml-3 text-sm font-bold">Departmental Savings</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    <?php if ($isAuditor): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/reports" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'reports') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                                <i class="fas fa-chart-pie w-6 text-sm"></i>
                                <span class="ml-3 text-sm font-bold">Reports</span>
                            </a>
                        </li>
                    <?php elseif ($isFinanceHead): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/reports" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'reports') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                                <i class="fas fa-chart-pie w-6 text-sm"></i>
                                <span class="ml-3 text-sm font-bold">Reports</span>
                            </a>
                        </li>
                    <?php elseif ($isPastor): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/reports" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'reports') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                                <i class="fas fa-chart-pie w-6 text-sm"></i>
                                <span class="ml-3 text-sm font-bold">Reports</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/visitors" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'visitors') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                                <i class="fas fa-user-friends w-6 text-sm"></i>
                                <span class="ml-3 text-sm font-bold">Visitors</span>
                            </a>
                        </li>
                    <?php elseif (!$isDeptHead && !$isStaff && !$isVisitationTeam): ?>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/sms" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'sms') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                                <i class="fas fa-comment-dots w-6 text-sm"></i>
                                <span class="ml-3 text-sm font-bold">SMS Broadcast</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/visitors" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'visitors') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                                <i class="fas fa-user-friends w-6 text-sm"></i>
                                <span class="ml-3 text-sm font-bold">Visitors</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo BASE_URL; ?>/reports" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'reports') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                                <i class="fas fa-chart-pie w-6 text-sm"></i>
                                <span class="ml-3 text-sm font-bold">Reports</span>
                            </a>
                        </li>
                        <li class="mt-8 pt-4 border-t border-white/5">
                            <a href="<?php echo BASE_URL; ?>/settings" class="flex items-center px-5 py-3.5 rounded-2xl nav-item-hover transition-all duration-300 <?php echo strpos($current_route, 'settings') === 0 ? 'active-link' : 'text-slate-400'; ?>">
                                <i class="fas fa-cog w-6 text-sm"></i>
                                <span class="ml-3 text-sm font-bold">Settings</span>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="border-b border-white/5 px-4 py-4 sm:px-6 sm:py-5 lg:px-10 lg:py-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3 sm:gap-4">
                        <button type="button" onclick="toggleSidebar(true)" class="inline-flex h-11 w-11 items-center justify-center rounded-2xl border border-white/10 bg-white/5 text-slate-300 transition-all hover:bg-white/10 lg:hidden">
                            <i class="fas fa-bars text-sm"></i>
                        </button>
                        <h1 class="text-base sm:text-lg font-black tracking-tight uppercase text-accent"><?php echo $title ?? 'Dashboard'; ?></h1>
                    </div>
                    <div class="flex items-center justify-between gap-3 sm:justify-end sm:gap-4 lg:gap-6">
                        <div class="flex min-w-0 items-center space-x-3 rounded-2xl border border-white/10 bg-white/5 px-3 py-2 sm:px-4">
                            <span class="hidden truncate text-xs font-bold text-slate-300 sm:inline"><?php echo Session::get('user_name', 'Admin'); ?></span>
                            <span class="truncate text-[10px] font-black uppercase tracking-widest text-slate-400 sm:hidden">Profile</span>
                            <?php
                                $userPhotoUrl = Branding::mediaUrl((string)Session::get('user_photo', ''));
                            ?>
                            <div class="w-8 h-8 bg-accent rounded-xl flex items-center justify-center overflow-hidden">
                                <?php if ($userPhotoUrl !== ''): ?>
                                    <img src="<?php echo htmlspecialchars($userPhotoUrl); ?>" alt="Admin" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <i class="fas fa-user text-slate-900 text-xs"></i>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!$isAuditor): ?>
                            <div id="support-chat-widget" class="relative">
                                <button type="button" id="support-chat-toggle" class="w-10 h-10 sm:w-11 sm:h-11 inline-flex items-center justify-center rounded-2xl bg-accent text-slate-900 shadow-xl shadow-yellow-500/20 hover-glow-yellow border border-accent/30 relative" aria-label="Open chat">
                                    <i class="fas fa-comment-dots text-sm"></i>
                                    <span id="chat-unread-badge" class="hidden absolute -top-2 -right-2 min-w-[1.75rem] h-7 px-2 rounded-full bg-rose-500 text-white text-[10px] font-black flex items-center justify-center border border-white/20 shadow-xl"></span>
                                </button>
                                <div id="chat-toast" class="hidden fixed top-[5.75rem] left-4 right-4 sm:left-auto sm:right-6 sm:w-[30rem] max-w-[92vw] z-[119]">
                                    <button type="button" id="chat-toast-btn" class="w-full glass-card rounded-[2rem] border-white/10 overflow-hidden shadow-2xl text-left">
                                        <div class="px-5 py-4 flex items-start gap-4">
                                            <div id="chat-toast-avatar" class="w-11 h-11 rounded-2xl overflow-hidden bg-white/5 border border-white/10 flex items-center justify-center shrink-0"></div>
                                            <div class="min-w-0 flex-1">
                                                <p id="chat-toast-title" class="text-[10px] font-black uppercase tracking-[0.26em] text-accent">New Message</p>
                                                <p id="chat-toast-name" class="mt-1 text-sm font-black text-slate-200 truncate"></p>
                                                <p id="chat-toast-preview" class="mt-2 text-[11px] font-bold text-slate-400 truncate"></p>
                                            </div>
                                            <div class="w-10 h-10 rounded-2xl bg-white/5 border border-white/10 flex items-center justify-center text-slate-400 shrink-0">
                                                <i class="fas fa-arrow-right"></i>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                                <div id="support-chat-panel" class="hidden fixed inset-0 z-[119] bg-slate-950/60 backdrop-blur-xl">
                                    <div class="glass-card rounded-none border-white/10 overflow-hidden shadow-2xl h-full w-full flex flex-col">
                                        <div class="px-6 py-5 bg-white/[0.03] border-b border-white/10 flex items-start justify-between gap-3 flex-shrink-0">
                                            <div class="min-w-0">
                                                <p class="text-[10px] font-black uppercase tracking-[0.28em] text-slate-500">Assistant</p>
                                                <h3 class="text-lg font-black text-white truncate">Messages</h3>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button type="button" id="chat-desktop-notify-toggle" class="w-10 h-10 inline-flex items-center justify-center rounded-2xl bg-white/5 text-slate-400 hover:bg-white/10 border border-white/10">
                                                    <i class="fas fa-bell text-sm"></i>
                                                </button>
                                                <button type="button" id="support-chat-close" class="w-10 h-10 inline-flex items-center justify-center rounded-2xl bg-white/5 text-slate-400 hover:bg-white/10 border border-white/10">
                                                    <i class="fas fa-times text-sm"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div id="support-pane-chat" class="p-4 sm:p-6 space-y-4 flex-1 min-h-0 flex flex-col overflow-hidden">
                                            <div class="grid grid-cols-1 sm:grid-cols-[18rem_minmax(0,1fr)] gap-4 flex-1 min-h-0">
                                                <div id="chat-list-pane" class="bg-white/5 border border-white/10 rounded-[2rem] p-4 flex flex-col min-h-0">
                                                    <div class="flex items-center justify-between gap-3">
                                                        <p class="text-[10px] font-black uppercase tracking-[0.28em] text-slate-500">Chats</p>
                                                        <span id="chat-list-count" class="px-3 py-1.5 text-[9px] font-black rounded-full uppercase tracking-widest border bg-white/5 text-slate-300 border-white/10">0</span>
                                                    </div>
                                                    <div class="mt-4 relative">
                                                        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-slate-600 text-[12px]"></i>
                                                        <input id="chat-search" type="text" class="w-full bg-slate-950/40 border border-white/10 focus:border-accent rounded-2xl pl-11 pr-4 py-3 text-[11px] font-black text-slate-200 outline-none" placeholder="Search chats">
                                                    </div>
                                                    <div id="chat-threads" class="mt-4 flex-1 overflow-y-auto custom-scrollbar pr-1"></div>
                                                </div>
                                                <div id="chat-convo-pane" class="hidden flex bg-white/5 border border-white/10 rounded-[2rem] overflow-hidden flex-col min-h-0">
                                                    <div class="px-4 py-4 bg-white/[0.03] border-b border-white/10 flex items-center justify-between gap-3 flex-shrink-0">
                                                        <div class="flex items-center gap-3 min-w-0">
                                                            <button type="button" id="chat-mobile-back" class="sm:hidden w-10 h-10 inline-flex items-center justify-center rounded-2xl bg-white/5 text-slate-300 hover:bg-white/10 border border-white/10">
                                                                <i class="fas fa-arrow-left text-sm"></i>
                                                            </button>
                                                            <div id="chat-active-avatar" class="relative w-10 h-10 rounded-2xl overflow-hidden bg-white/5 border border-white/10 flex items-center justify-center shrink-0"></div>
                                                            <div class="min-w-0">
                                                                <p id="chat-active-name" class="text-sm font-black text-slate-200 truncate">Select a chat</p>
                                                                <p id="chat-active-status" class="text-[10px] font-black uppercase tracking-widest text-slate-500 truncate"></p>
                                                            </div>
                                                        </div>
                                                        <div class="flex items-center gap-2">
                                                            <button type="button" id="chat-refresh" class="w-10 h-10 inline-flex items-center justify-center rounded-2xl bg-white/5 text-slate-400 hover:bg-white/10 border border-white/10">
                                                                <i class="fas fa-rotate text-sm"></i>
                                                            </button>
                                                            <button type="button" id="chat-clear" class="w-10 h-10 inline-flex items-center justify-center rounded-2xl bg-white/5 text-slate-400 hover:bg-white/10 border border-white/10">
                                                                <i class="fas fa-trash-can text-sm"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                    <div id="chat-messages" class="flex-1 overflow-y-auto custom-scrollbar space-y-3 p-4 sm:p-5" style="background-image: radial-gradient(circle at 30% 20%, rgba(255,255,255,0.06), transparent 45%), radial-gradient(circle at 70% 80%, rgba(255,255,255,0.05), transparent 50%);"></div>
                                                    <form id="chat-form" class="p-4 sm:p-5 pb-[calc(env(safe-area-inset-bottom)+1rem)] border-t border-white/10 flex items-center gap-3 flex-shrink-0 bg-slate-950/20">
                                                        <input id="chat-input" type="text" class="flex-1 bg-slate-950/50 border border-white/10 focus:border-accent rounded-2xl px-4 py-3 text-sm font-bold text-white outline-none" placeholder="Type a message">
                                                        <button type="submit" class="h-12 px-6 rounded-2xl bg-accent text-slate-900 font-black text-[10px] uppercase tracking-widest">
                                                            Send
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                            <p id="chat-hint" class="hidden sm:block text-[10px] font-black uppercase tracking-widest text-slate-500">
                                                <?php if (Auth::isAdmin()): ?>
                                                    Admin can chat with everyone.
                                                <?php elseif (Auth::isFinanceHead()): ?>
                                                    Head Of Finance can chat with Finance Staff and Department Heads.
                                                <?php elseif (Auth::isStaff() || Auth::isDepartmentHead()): ?>
                                                    You can chat with Admin and Head Of Finance.
                                                <?php else: ?>
                                                    You can chat with Admin.
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                        <a href="<?php echo BASE_URL; ?>/logout" class="w-10 h-10 inline-flex items-center justify-center rounded-2xl bg-white/5 text-slate-400 hover:bg-white/10 transition-all border border-white/10">
                            <i class="fas fa-right-from-bracket text-sm"></i>
                        </a>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div id="app-scroll-container" class="flex-1 overflow-y-auto px-4 py-6 sm:px-6 sm:py-8 lg:px-10 lg:py-10 custom-scrollbar">
                <?php echo $content; ?>
            </div>
        </main>
    </div>
    <button
        type="button"
        id="scroll-top-button"
        class="scroll-top-button fixed bottom-5 right-4 sm:bottom-6 sm:right-6 lg:bottom-8 lg:right-10 z-[117] inline-flex h-12 w-12 sm:h-14 sm:w-14 items-center justify-center rounded-2xl bg-accent text-slate-900 shadow-xl shadow-yellow-500/20 hover-glow-yellow"
        aria-label="Scroll to top"
    >
        <i class="fas fa-arrow-up text-sm"></i>
    </button>
    <?php if ($isFinanceHead): ?>
        <div id="finance-approvals-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-[120] hidden items-center justify-center p-4">
            <div id="finance-approvals-modal-content" class="glass-card w-full max-w-xl rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col">
                <div class="px-8 sm:px-10 py-8 bg-slate-900 relative overflow-hidden border-b border-white/5 flex-shrink-0">
                    <div class="relative z-10 flex justify-between items-start gap-6 text-white">
                        <div class="min-w-0">
                            <h3 class="text-2xl sm:text-3xl font-black tracking-tighter">Approval Request</h3>
                            <p id="finance-approvals-subtitle" class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase">Pending</p>
                        </div>
                        <button type="button" id="finance-approvals-close" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1 space-y-5">
                    <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Type</p>
                        <p id="finance-approvals-type" class="text-sm font-black text-slate-200 mt-3"></p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Requested By</p>
                            <p id="finance-approvals-requester" class="text-sm font-black text-slate-200 mt-3"></p>
                            <p id="finance-approvals-created" class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-3"></p>
                        </div>
                        <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Amount</p>
                            <p id="finance-approvals-amount" class="text-lg font-black text-accent mt-3"></p>
                            <p id="finance-approvals-meta" class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-3"></p>
                        </div>
                    </div>
                    <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Details</p>
                        <p id="finance-approvals-details" class="text-sm font-bold text-slate-300 mt-3"></p>
                    </div>
                </div>
                <div class="px-6 sm:px-10 pb-8 bg-slate-900/50 flex flex-col sm:flex-row gap-4">
                    <button type="button" id="finance-approvals-approve" class="flex-1 bg-emerald-400 text-slate-950 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-emerald-500/10">
                        Approve
                    </button>
                    <button type="button" id="finance-approvals-reject" class="flex-1 bg-rose-500/20 text-rose-200 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-rose-500/30 transition-all border border-rose-500/25">
                        Reject
                    </button>
                    <button type="button" id="finance-approvals-later" class="px-10 py-5 bg-white/5 text-slate-300 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-white/10 transition-all">
                        Later
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!$isAuditor && (Auth::isStaff() || Auth::isDepartmentHead())): ?>
        <div id="my-request-updates-modal" class="fixed inset-0 bg-slate-950/80 backdrop-blur-xl z-[119] hidden items-center justify-center p-4">
            <div id="my-request-updates-modal-content" class="glass-card w-full max-w-xl rounded-[3.5rem] overflow-hidden shadow-2xl transform transition-all duration-500 scale-95 opacity-0 border-white/10 max-h-[90vh] flex flex-col">
                <div class="px-8 sm:px-10 py-8 bg-slate-900 relative overflow-hidden border-b border-white/5 flex-shrink-0">
                    <div class="relative z-10 flex justify-between items-start gap-6 text-white">
                        <div class="min-w-0">
                            <h3 class="text-2xl sm:text-3xl font-black tracking-tighter">Request Update</h3>
                            <p id="my-request-updates-subtitle" class="text-accent text-[10px] font-black mt-2 tracking-[0.3em] uppercase">Status</p>
                        </div>
                        <button type="button" id="my-request-updates-close" class="w-12 h-12 bg-white/5 hover:bg-accent hover:text-slate-900 rounded-2xl flex items-center justify-center transition-all border border-white/10">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                <div class="p-6 sm:p-10 bg-slate-900/50 overflow-y-auto custom-scrollbar flex-1 space-y-5">
                    <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Type</p>
                        <p id="my-request-updates-type" class="text-sm font-black text-slate-200 mt-3"></p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Status</p>
                            <p id="my-request-updates-status" class="text-lg font-black mt-3"></p>
                            <p id="my-request-updates-when" class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-3"></p>
                        </div>
                        <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                            <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Amount</p>
                            <p id="my-request-updates-amount" class="text-lg font-black text-accent mt-3"></p>
                            <p id="my-request-updates-meta" class="text-[10px] font-black text-slate-500 uppercase tracking-widest mt-3"></p>
                        </div>
                    </div>
                    <div class="bg-white/5 rounded-[2.5rem] p-6 border border-white/10">
                        <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest">Message</p>
                        <p id="my-request-updates-message" class="text-sm font-bold text-slate-300 mt-3"></p>
                    </div>
                </div>
                <div class="px-6 sm:px-10 pb-8 bg-slate-900/50 flex flex-col sm:flex-row gap-4">
                    <button type="button" id="my-request-updates-open" class="flex-1 bg-accent text-slate-950 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-95 transition-all shadow-xl shadow-yellow-500/10">
                        Open Finance
                    </button>
                    <button type="button" id="my-request-updates-next" class="flex-1 bg-white/5 text-slate-200 py-5 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:bg-white/10 transition-all border border-white/10">
                        Next
                    </button>
                    <button type="button" id="my-request-updates-ok" class="px-10 py-5 bg-emerald-500/15 text-emerald-200 rounded-2xl font-black text-xs uppercase tracking-[0.3em] hover:bg-emerald-500/20 transition-all border border-emerald-500/20">
                        Ok
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
    <script>
        (function () {
            const storageKey = 'membersSubmenuOpen';
            const submenu = document.getElementById('members-submenu');
            const chevron = document.getElementById('members-chevron');
            const sidebar = document.getElementById('app-sidebar');
            const overlay = document.getElementById('mobile-sidebar-overlay');
            const scrollContainer = document.getElementById('app-scroll-container');
            const scrollTopButton = document.getElementById('scroll-top-button');
            const topActionLoader = document.getElementById('top-action-loader');
            const pageLoader = document.getElementById('global-page-loader');
            const pageLoaderText = document.getElementById('global-page-loader-text');
            const pageLoaderSubtext = document.getElementById('global-page-loader-subtext');
            const globalToast = document.getElementById('global-toast');
            const globalToastClose = document.getElementById('global-toast-close');
            const uploadOverlay = document.getElementById('global-upload-progress');
            const uploadBar = document.getElementById('global-upload-progress-bar');
            const uploadPercent = document.getElementById('global-upload-progress-percent');
            const uploadText = document.getElementById('global-upload-progress-text');
            const uploadSubtext = document.getElementById('global-upload-progress-subtext');

            const showPageLoader = function (title, description) {
                if (!pageLoader) return;
                if (pageLoaderText && title) pageLoaderText.textContent = title;
                if (pageLoaderSubtext && description) pageLoaderSubtext.textContent = description;
                pageLoader.classList.remove('is-hidden');
            };

            const hidePageLoader = function () {
                if (!pageLoader) return;
                pageLoader.classList.add('is-hidden');
            };

            const showTopActionLoader = function () {
                if (!topActionLoader) return;
                topActionLoader.classList.remove('hidden');
            };

            const hideTopActionLoader = function () {
                if (!topActionLoader) return;
                topActionLoader.classList.add('hidden');
            };

            const showUploadOverlay = function (percentValue, title, description) {
                if (!uploadOverlay) return;
                const safePercent = Math.max(0, Math.min(100, Number(percentValue) || 0));
                if (uploadText && title) uploadText.textContent = title;
                if (uploadSubtext && description) uploadSubtext.textContent = description;
                if (uploadBar) uploadBar.style.width = safePercent + '%';
                if (uploadPercent) uploadPercent.textContent = safePercent + '%';
                uploadOverlay.classList.remove('hidden');
            };

            const hideUploadOverlay = function () {
                if (!uploadOverlay) return;
                uploadOverlay.classList.add('hidden');
            };

            const isUploadForm = function (form) {
                return (form.enctype || '').toLowerCase().includes('multipart/form-data')
                    && form.querySelector('input[type="file"]');
            };

            const hasSelectedUploadFile = function (form) {
                return Array.from(form.querySelectorAll('input[type="file"]')).some(function (input) {
                    return input.files && input.files.length > 0;
                });
            };

            const setFormSubmittingState = function (form, isSubmitting) {
                Array.from(form.querySelectorAll('button, input[type="submit"], input[type="button"]')).forEach(function (element) {
                    element.disabled = isSubmitting;
                });
            };

            const submitUploadForm = function (form, submitter) {
                if (!form.action || form.dataset.uploading === '1') return;

                form.dataset.uploading = '1';
                setFormSubmittingState(form, true);
                showUploadOverlay(0, 'Uploading your file...', 'Please wait while the upload finishes.');

                const formData = new FormData(form);
                if (submitter && submitter.name) {
                    formData.append(submitter.name, submitter.value || '');
                }

                const xhr = new XMLHttpRequest();
                xhr.open((form.method || 'POST').toUpperCase(), form.action, true);

                xhr.upload.addEventListener('progress', function (event) {
                    if (!event.lengthComputable) {
                        showUploadOverlay(35, 'Uploading your file...', 'Still uploading. Please keep this page open.');
                        return;
                    }
                    const percentValue = Math.max(1, Math.round((event.loaded / event.total) * 100));
                    showUploadOverlay(percentValue, 'Uploading your file...', 'Uploaded ' + percentValue + '%');
                });

                xhr.onload = function () {
                    hideUploadOverlay();
                    showTopActionLoader();

                    if (xhr.status < 200 || xhr.status >= 400) {
                        form.dataset.uploading = '0';
                        setFormSubmittingState(form, false);
                        hidePageLoader();
                        hideTopActionLoader();
                        alert('Upload failed. Please try again.');
                        return;
                    }

                    const responseUrl = xhr.responseURL || '';
                    const currentUrl = window.location.href;
                    const normalizedAction = new URL(form.action, currentUrl).href;

                    if (responseUrl && responseUrl !== normalizedAction) {
                        window.location.href = responseUrl;
                        return;
                    }

                    document.open();
                    document.write(xhr.responseText);
                    document.close();
                };

                xhr.onerror = xhr.onabort = function () {
                    form.dataset.uploading = '0';
                    setFormSubmittingState(form, false);
                    hideUploadOverlay();
                    hidePageLoader();
                    alert('Upload could not be completed. Please try again.');
                };

                xhr.send(formData);
            };

            const shouldShowLoaderForLink = function (link) {
                if (!link || link.dataset.skipLoader === '1') return false;
                const href = (link.getAttribute('href') || '').trim();
                if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;
                if (link.hasAttribute('download') || (link.target && link.target !== '_self')) return false;
                if (href.startsWith('#')) return false;
                return true;
            };

            window.addEventListener('load', function () {
                window.setTimeout(hidePageLoader, 120);
                hideTopActionLoader();
            });

            window.addEventListener('pageshow', function () {
                hidePageLoader();
                hideUploadOverlay();
                hideTopActionLoader();
            });

            if (globalToast) {
                requestAnimationFrame(() => globalToast.classList.add('is-visible'));
                const close = () => {
                    globalToast.classList.remove('is-visible');
                    window.setTimeout(() => {
                        const wrap = globalToast.closest('.global-toast-wrap');
                        if (wrap) wrap.remove();
                    }, 220);
                };
                if (globalToastClose) globalToastClose.addEventListener('click', close);
                window.setTimeout(close, 5200);
            }

            document.addEventListener('click', function (event) {
                const link = event.target.closest('a');
                if (!shouldShowLoaderForLink(link)) return;
                showTopActionLoader();
            });

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;
                if (form.closest('#support-chat-widget')) return;

                if (isUploadForm(form) && hasSelectedUploadFile(form)) {
                    event.preventDefault();
                    submitUploadForm(form, event.submitter || document.activeElement);
                    return;
                }

                setFormSubmittingState(form, true);
                showTopActionLoader();
            });

            window.toggleSidebar = function (open) {
                if (!sidebar || !overlay) return;
                const shouldOpen = open === undefined ? sidebar.classList.contains('-translate-x-full') : open;
                sidebar.classList.toggle('-translate-x-full', !shouldOpen);
                overlay.classList.toggle('hidden', !shouldOpen);
                document.body.classList.toggle('overflow-hidden', shouldOpen);
            };

            if (overlay) {
                overlay.addEventListener('click', function () {
                    window.toggleSidebar(false);
                });
            }

            window.addEventListener('resize', function () {
                if (window.innerWidth >= 1024) {
                    sidebar?.classList.remove('-translate-x-full');
                    overlay?.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                } else {
                    sidebar?.classList.add('-translate-x-full');
                    overlay?.classList.add('hidden');
                    document.body.classList.remove('overflow-hidden');
                }
            });

            const getScrollPosition = function () {
                const containerScroll = scrollContainer ? (scrollContainer.scrollTop || 0) : 0;
                const winScroll = window.scrollY || document.documentElement.scrollTop || document.body.scrollTop || 0;
                return Math.max(containerScroll, winScroll);
            };

            const syncScrollTopButton = function () {
                if (!scrollTopButton) return;
                const currentPosition = getScrollPosition();
                scrollTopButton.classList.toggle('is-visible', currentPosition > 260);
            };

            scrollContainer?.addEventListener('scroll', syncScrollTopButton, { passive: true });
            window.addEventListener('scroll', syncScrollTopButton, { passive: true });
            window.addEventListener('resize', syncScrollTopButton, { passive: true });

            scrollTopButton?.addEventListener('click', function () {
                scrollContainer?.scrollTo({ top: 0, behavior: 'smooth' });
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });

            syncScrollTopButton();

            if (!submenu || !chevron) return;

            const route = <?php echo json_encode($current_route); ?>;
            const isChildActive = route.startsWith('cluster') || route.startsWith('departments');
            const saved = localStorage.getItem(storageKey);
            const shouldOpen = isChildActive || saved === '1';

            if (shouldOpen) submenu.classList.remove('hidden');
            chevron.classList.toggle('rotate-180', shouldOpen);

            window.toggleMembersSubmenu = function () {
                const isOpen = !submenu.classList.contains('hidden');
                if (isOpen) {
                    submenu.classList.add('hidden');
                    chevron.classList.remove('rotate-180');
                    localStorage.setItem(storageKey, '0');
                } else {
                    submenu.classList.remove('hidden');
                    chevron.classList.add('rotate-180');
                    localStorage.setItem(storageKey, '1');
                }
            }

        })();
    </script>
    <?php if ($isFinanceHead): ?>
        <script>
            (function () {
                const baseUrl = <?php echo json_encode(BASE_URL); ?>;
                const currency = <?php echo json_encode(strtoupper(trim((string)(AppConfig::getSetting('finance_currency', 'GHS'))))); ?>;
                const modal = document.getElementById('finance-approvals-modal');
                const content = document.getElementById('finance-approvals-modal-content');
                const closeBtn = document.getElementById('finance-approvals-close');
                const laterBtn = document.getElementById('finance-approvals-later');
                const approveBtn = document.getElementById('finance-approvals-approve');
                const rejectBtn = document.getElementById('finance-approvals-reject');
                const subtitleEl = document.getElementById('finance-approvals-subtitle');
                const typeEl = document.getElementById('finance-approvals-type');
                const requesterEl = document.getElementById('finance-approvals-requester');
                const createdEl = document.getElementById('finance-approvals-created');
                const amountEl = document.getElementById('finance-approvals-amount');
                const metaEl = document.getElementById('finance-approvals-meta');
                const detailsEl = document.getElementById('finance-approvals-details');

                if (!modal || !content || !approveBtn || !rejectBtn) return;

                let items = [];
                let idx = 0;
                let busy = false;

                const openModal = function () {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    window.setTimeout(() => {
                        content.classList.remove('scale-95', 'opacity-0');
                        content.classList.add('scale-100', 'opacity-100');
                    }, 10);
                };

                const closeModal = function () {
                    content.classList.remove('scale-100', 'opacity-100');
                    content.classList.add('scale-95', 'opacity-0');
                    window.setTimeout(() => {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }, 240);
                };

                const setBusy = function (state) {
                    busy = state;
                    approveBtn.disabled = state;
                    rejectBtn.disabled = state;
                    laterBtn && (laterBtn.disabled = state);
                    closeBtn && (closeBtn.disabled = state);
                    if (subtitleEl) subtitleEl.textContent = state ? 'Processing...' : 'Pending';
                };

                const formatAmount = function (v) {
                    const num = Number(v || 0);
                    return currency + ' ' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };

                const render = function () {
                    const item = items[idx];
                    if (!item) {
                        closeModal();
                        return;
                    }

                    const total = items.length;
                    if (subtitleEl) subtitleEl.textContent = `Pending (${idx + 1}/${total})`;

                    const created = String(item.created_at || '').trim();
                    if (createdEl) createdEl.textContent = created ? created : '';

                    const requester = String(item.requested_by_name || '').trim();
                    if (requesterEl) requesterEl.textContent = requester ? requester : 'N/A';

                    if (amountEl) amountEl.textContent = formatAmount(item.amount);

                    if (item.kind === 'change_request') {
                        if (typeEl) typeEl.textContent = 'Finance Change Request';
                        const txType = String(item.transaction_type || '').trim();
                        const txNo = String(item.transaction_number || '').trim();
                        if (metaEl) metaEl.textContent = txNo ? txNo : '';
                        const reason = String(item.reason || '').trim();
                        if (detailsEl) detailsEl.textContent = reason ? reason : '';
                        const label = [txType, txNo].filter(Boolean).join(' • ');
                        if (metaEl) metaEl.textContent = label;
                    } else {
                        if (typeEl) typeEl.textContent = 'Department Expense Request';
                        const dept = String(item.department_name || '').trim();
                        if (metaEl) metaEl.textContent = dept ? dept : '';
                        const purpose = String(item.purpose || '').trim();
                        if (detailsEl) detailsEl.textContent = purpose ? purpose : '';
                    }
                };

                const postAction = async function (action) {
                    const item = items[idx];
                    if (!item || busy) return;
                    setBusy(true);

                    const body = new URLSearchParams();
                    let url = '';
                    if (item.kind === 'change_request') {
                        body.set('request_id', String(item.id));
                        url = baseUrl + (action === 'approve' ? '/finance/approveChangeRequest' : '/finance/rejectChangeRequest');
                    } else {
                        body.set('request_id', String(item.id));
                        url = baseUrl + (action === 'approve' ? '/finance/approveDepartmentExpenseRequest' : '/finance/rejectDepartmentExpenseRequest');
                    }

                    try {
                        const res = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                            },
                            body: body.toString()
                        });
                        const data = await res.json().catch(() => null);
                        if (!res.ok || !data || data.success !== true) {
                            setBusy(false);
                            return;
                        }

                        items.splice(idx, 1);
                        if (idx >= items.length) idx = 0;
                        setBusy(false);
                        if (items.length === 0) {
                            closeModal();
                            sessionStorage.setItem('financeApprovalSig', '');
                            return;
                        }
                        render();
                    } catch (e) {
                        setBusy(false);
                    }
                };

                const poll = async function (forceOpen) {
                    if (busy) return;
                    try {
                        const res = await fetch(baseUrl + '/finance/pendingApprovals', {
                            headers: { 'Accept': 'application/json' }
                        });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (!data || data.success !== true) return;
                        const sig = String(data.latest_key || '') + '|' + String(data.total_pending || 0);
                        const prev = String(sessionStorage.getItem('financeApprovalSig') || '');
                        if (!forceOpen && sig !== '' && sig === prev) return;
                        sessionStorage.setItem('financeApprovalSig', sig);
                        if ((data.total_pending || 0) <= 0) return;
                        items = Array.isArray(data.items) ? data.items : [];
                        idx = 0;
                        render();
                        openModal();
                    } catch (e) {
                    }
                };

                approveBtn.addEventListener('click', function () { postAction('approve'); });
                rejectBtn.addEventListener('click', function () { postAction('reject'); });
                closeBtn && closeBtn.addEventListener('click', closeModal);
                laterBtn && laterBtn.addEventListener('click', closeModal);
                modal.addEventListener('click', function (e) {
                    if (e.target === modal && !busy) closeModal();
                });

                window.addEventListener('load', function () {
                    window.setTimeout(() => poll(true), 900);
                });
                window.setInterval(() => poll(false), 18000);
            })();
        </script>
    <?php endif; ?>
    <?php if (!$isAuditor && (Auth::isStaff() || Auth::isDepartmentHead())): ?>
        <script>
            (function () {
                const baseUrl = <?php echo json_encode(BASE_URL); ?>;
                const currency = <?php echo json_encode(strtoupper(trim((string)(AppConfig::getSetting('finance_currency', 'GHS'))))); ?>;
                const modal = document.getElementById('my-request-updates-modal');
                const content = document.getElementById('my-request-updates-modal-content');
                const closeBtn = document.getElementById('my-request-updates-close');
                const openBtn = document.getElementById('my-request-updates-open');
                const nextBtn = document.getElementById('my-request-updates-next');
                const okBtn = document.getElementById('my-request-updates-ok');
                const subtitleEl = document.getElementById('my-request-updates-subtitle');
                const typeEl = document.getElementById('my-request-updates-type');
                const statusEl = document.getElementById('my-request-updates-status');
                const whenEl = document.getElementById('my-request-updates-when');
                const amountEl = document.getElementById('my-request-updates-amount');
                const metaEl = document.getElementById('my-request-updates-meta');
                const msgEl = document.getElementById('my-request-updates-message');

                if (!modal || !content || !okBtn || !nextBtn || !openBtn) return;

                let queue = [];
                let idx = 0;
                let busy = false;

                const getSeen = function () {
                    try {
                        const raw = sessionStorage.getItem('myRequestUpdatesSeen') || '[]';
                        const arr = JSON.parse(raw);
                        return Array.isArray(arr) ? arr : [];
                    } catch (e) {
                        return [];
                    }
                };

                const setSeen = function (arr) {
                    try {
                        const trimmed = arr.slice(-80);
                        sessionStorage.setItem('myRequestUpdatesSeen', JSON.stringify(trimmed));
                    } catch (e) {
                    }
                };

                const markSeen = function (key) {
                    const seen = getSeen();
                    if (!seen.includes(key)) {
                        seen.push(key);
                        setSeen(seen);
                    }
                };

                const formatAmount = function (v) {
                    const num = Number(v || 0);
                    return currency + ' ' + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                };

                const openModal = function () {
                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                    window.setTimeout(() => {
                        content.classList.remove('scale-95', 'opacity-0');
                        content.classList.add('scale-100', 'opacity-100');
                    }, 10);
                };

                const closeModal = function () {
                    content.classList.remove('scale-100', 'opacity-100');
                    content.classList.add('scale-95', 'opacity-0');
                    window.setTimeout(() => {
                        modal.classList.add('hidden');
                        modal.classList.remove('flex');
                    }, 240);
                };

                const setBusy = function (state) {
                    busy = state;
                    okBtn.disabled = state;
                    nextBtn.disabled = state;
                    openBtn.disabled = state;
                    closeBtn && (closeBtn.disabled = state);
                };

                const render = function () {
                    const item = queue[idx];
                    if (!item) {
                        closeModal();
                        return;
                    }

                    if (subtitleEl) subtitleEl.textContent = `Update (${idx + 1}/${queue.length})`;

                    const decidedAt = String(item.decided_at || '').trim();
                    if (whenEl) whenEl.textContent = decidedAt ? decidedAt : '';

                    const status = String(item.status || '').toLowerCase();
                    if (statusEl) {
                        statusEl.textContent = status ? status.toUpperCase() : '';
                        statusEl.className = 'text-lg font-black mt-3 ' + (status === 'approved' ? 'text-emerald-400' : 'text-rose-400');
                    }

                    if (amountEl) amountEl.textContent = formatAmount(item.amount);

                    if (item.kind === 'change_request') {
                        if (typeEl) typeEl.textContent = 'Finance Change Request';
                        const txType = String(item.transaction_type || '').trim();
                        const txNo = String(item.transaction_number || '').trim();
                        const remaining = Number(item.remaining_edits || 0);
                        const meta = [txType, txNo].filter(Boolean).join(' • ');
                        if (metaEl) metaEl.textContent = meta ? meta + (status === 'approved' ? ` • ${remaining} edits left` : '') : '';
                        const byName = status === 'approved'
                            ? String(item.approved_by_name || '').trim()
                            : String(item.rejected_by_name || '').trim();
                        if (msgEl) {
                            if (status === 'approved') {
                                msgEl.textContent = `Approved by ${byName || 'Head of Finance'}. You can now edit this transaction.`;
                            } else {
                                msgEl.textContent = `Rejected by ${byName || 'Head of Finance'}.`;
                            }
                        }
                    } else {
                        if (typeEl) typeEl.textContent = 'Department Expense Request';
                        const dept = String(item.department_name || '').trim();
                        if (metaEl) metaEl.textContent = dept ? dept : '';
                        const byName = status === 'approved'
                            ? String(item.approved_by_name || '').trim()
                            : String(item.rejected_by_name || '').trim();
                        if (msgEl) {
                            if (status === 'approved') {
                                msgEl.textContent = `Approved by ${byName || 'Head of Finance'}.`;
                            } else {
                                msgEl.textContent = `Rejected by ${byName || 'Head of Finance'}.`;
                            }
                        }
                    }
                };

                const moveNext = function () {
                    if (queue.length === 0) {
                        closeModal();
                        return;
                    }
                    idx = (idx + 1) % queue.length;
                    render();
                };

                const acknowledgeCurrent = function () {
                    const item = queue[idx];
                    if (!item) {
                        closeModal();
                        return;
                    }
                    const key = `${item.kind}:${item.id}:${item.status}:${item.decided_at || ''}`;
                    markSeen(key);
                    queue.splice(idx, 1);
                    if (idx >= queue.length) idx = 0;
                    if (queue.length === 0) {
                        closeModal();
                        return;
                    }
                    render();
                };

                const poll = async function () {
                    if (busy) return;
                    try {
                        const res = await fetch(baseUrl + '/finance/myRequestUpdates', { headers: { 'Accept': 'application/json' } });
                        if (!res.ok) return;
                        const data = await res.json();
                        if (!data || data.success !== true) return;
                        const items = Array.isArray(data.items) ? data.items : [];
                        const seen = new Set(getSeen());
                        const fresh = items.filter((item) => {
                            const key = `${item.kind}:${item.id}:${item.status}:${item.decided_at || ''}`;
                            return !seen.has(key);
                        });
                        if (fresh.length === 0) return;
                        queue = fresh;
                        idx = 0;
                        render();
                        openModal();
                    } catch (e) {
                    }
                };

                okBtn.addEventListener('click', function () { acknowledgeCurrent(); });
                nextBtn.addEventListener('click', function () { moveNext(); });
                openBtn.addEventListener('click', function () { window.location.href = baseUrl + '/finance'; });
                closeBtn && closeBtn.addEventListener('click', closeModal);
                modal.addEventListener('click', function (e) {
                    if (e.target === modal && !busy) closeModal();
                });

                window.addEventListener('load', function () {
                    window.setTimeout(poll, 1100);
                });
                window.setInterval(poll, 22000);
            })();
        </script>
    <?php endif; ?>
    <?php if (!$isAuditor): ?>
        <script>
            (function () {
                const baseUrl = <?php echo json_encode(BASE_URL); ?>;
                const isAdmin = <?php echo json_encode(Auth::isAdmin()); ?>;
                const isFinanceHead = <?php echo json_encode(Auth::isFinanceHead()); ?>;
                const meId = <?php echo json_encode((int)Session::get('user_id')); ?>;
                const meName = <?php echo json_encode((string)Session::get('user_name', 'User')); ?>;
                const mePhoto = <?php echo json_encode((string)Session::get('user_photo', '')); ?>;
                const supabaseUrl = <?php echo json_encode(trim((string)Env::get('SUPABASE_URL', ''))); ?>;
                const supabaseBucket = <?php echo json_encode(trim((string)Env::get('SUPABASE_STORAGE_BUCKET', '')) ?: 'uploads'); ?>;
                const supabasePublicBase = supabaseUrl ? (String(supabaseUrl).replace(/\/+$/, '') + '/storage/v1/object/public/' + encodeURIComponent(String(supabaseBucket || 'uploads')) + '/') : '';

                const widget = document.getElementById('support-chat-widget');
                const toggle = document.getElementById('support-chat-toggle');
                const panel = document.getElementById('support-chat-panel');
                const closeBtn = document.getElementById('support-chat-close');
                const paneChat = document.getElementById('support-pane-chat');
                const desktopNotifyBtn = document.getElementById('chat-desktop-notify-toggle');
                const badgeEl = document.getElementById('chat-unread-badge');
                const toastEl = document.getElementById('chat-toast');
                const toastBtn = document.getElementById('chat-toast-btn');
                const toastAvatar = document.getElementById('chat-toast-avatar');
                const toastTitle = document.getElementById('chat-toast-title');
                const toastName = document.getElementById('chat-toast-name');
                const toastPreview = document.getElementById('chat-toast-preview');

                const threadsEl = document.getElementById('chat-threads');
                const chatList = document.getElementById('chat-messages');
                const chatForm = document.getElementById('chat-form');
                const chatInput = document.getElementById('chat-input');
                const chatSearch = document.getElementById('chat-search');
                const chatListCount = document.getElementById('chat-list-count');
                const chatListPane = document.getElementById('chat-list-pane');
                const chatConvoPane = document.getElementById('chat-convo-pane');
                const chatMobileBack = document.getElementById('chat-mobile-back');
                const chatActiveAvatar = document.getElementById('chat-active-avatar');
                const chatActiveName = document.getElementById('chat-active-name');
                const chatActiveStatus = document.getElementById('chat-active-status');
                const chatRefresh = document.getElementById('chat-refresh');
                const chatClear = document.getElementById('chat-clear');

                if (!widget || !toggle || !panel) return;

                let activeThreadId = 0;
                let activeToUserId = 0;
                let pollMessagesTimer = null;
                let pollThreadsTimer = null;
                let activeLastId = 0;
                let renderedMessageIds = new Set();
                let totalUnread = 0;
                const lastThreadMessageId = {};
                let lastToastThreadId = 0;
                let audioCtx = null;
                let desktopNotifyEnabled = false;
                const desktopNotifyKey = 'chatDesktopNotify';
                let threadsCacheByThreadId = {};
                let threadsCacheByUserId = {};
                let allThreads = [];
                let chatSearchTerm = '';
                const onlineCutoffMs = 10 * 60 * 1000;

                const photoUrl = function (relative) {
                    let p = String(relative || '').trim();
                    if (!p) return '';
                    if (p.startsWith('http://') || p.startsWith('https://')) return p;
                    p = p.replace(/\\/g, '/').replace(/^\/+/, '');
                    const publicIdx = p.indexOf('public/uploads/');
                    if (publicIdx >= 0) p = p.slice(publicIdx);
                    const uploadsIdx = p.indexOf('uploads/');
                    if (uploadsIdx >= 0 && publicIdx < 0) p = p.slice(uploadsIdx);
                    if (p.startsWith('uploads/')) p = 'public/' + p;
                    if (supabasePublicBase && p.startsWith('public/uploads/')) {
                        const objectPath = p.slice('public/uploads/'.length);
                        if (objectPath) {
                            const encoded = objectPath.split('/').filter(Boolean).map(encodeURIComponent).join('/');
                            return supabasePublicBase + encoded;
                        }
                    }
                    return baseUrl + '/' + p;
                };

                const escapeHtml = function (text) {
                    const div = document.createElement('div');
                    div.textContent = String(text || '');
                    return div.innerHTML;
                };

                const scrollToBottom = function (el) {
                    if (!el) return;
                    el.scrollTop = el.scrollHeight;
                };

                const canDesktopNotify = function () {
                    return typeof window !== 'undefined' && 'Notification' in window;
                };

                const syncDesktopNotifyButton = function () {
                    if (!desktopNotifyBtn) return;
                    if (!canDesktopNotify()) {
                        desktopNotifyBtn.classList.add('opacity-50');
                        desktopNotifyBtn.disabled = true;
                        desktopNotifyBtn.title = 'Desktop notifications not supported';
                        return;
                    }

                    const perm = Notification.permission;
                    const on = desktopNotifyEnabled && perm === 'granted';
                    desktopNotifyBtn.disabled = false;
                    desktopNotifyBtn.classList.toggle('text-accent', on);
                    desktopNotifyBtn.classList.toggle('text-slate-400', !on);
                    desktopNotifyBtn.title = on ? 'Desktop notifications enabled' : (perm === 'denied' ? 'Desktop notifications blocked by browser' : 'Enable desktop notifications');
                };

                const isPanelOpen = function () {
                    return !panel.classList.contains('hidden');
                };

                const isMobile = function () {
                    return window.innerWidth < 640;
                };

                let lastIsMobile = isMobile();
                let resizeTimer = null;

                const showListView = function () {
                    if (!isMobile()) {
                        chatListPane?.classList.remove('hidden');
                        chatConvoPane?.classList.remove('hidden');
                        return;
                    }
                    chatListPane?.classList.remove('hidden');
                    chatConvoPane?.classList.add('hidden');
                };

                const showConversationView = function () {
                    if (!isMobile()) return;
                    chatListPane?.classList.add('hidden');
                    chatConvoPane?.classList.remove('hidden');
                };

                window.addEventListener('resize', function () {
                    if (resizeTimer) window.clearTimeout(resizeTimer);
                    resizeTimer = window.setTimeout(function () {
                        if (!isPanelOpen()) return;
                        const nowMobile = isMobile();
                        if (nowMobile !== lastIsMobile) {
                            lastIsMobile = nowMobile;
                            if (nowMobile) {
                                if (activeThreadId > 0 || activeToUserId > 0) showConversationView();
                                else showListView();
                            } else {
                                showListView();
                            }
                        }
                        if (threadsEl && allThreads && allThreads.length > 0) {
                            renderThreads(allThreads);
                        }
                        window.setTimeout(function () {
                            scrollToBottom(chatList);
                        }, 50);
                    }, 120);
                });

                const setBadge = function (count) {
                    const c = Number(count || 0);
                    if (!badgeEl) return;
                    if (c <= 0) {
                        badgeEl.classList.add('hidden');
                        badgeEl.textContent = '';
                        return;
                    }
                    badgeEl.classList.remove('hidden');
                    badgeEl.textContent = c > 99 ? '99+' : String(c);
                };

                const playNotificationSound = function () {
                    try {
                        const Ctx = window.AudioContext || window.webkitAudioContext;
                        if (!Ctx) return;
                        if (!audioCtx) audioCtx = new Ctx();
                        if (audioCtx.state === 'suspended') audioCtx.resume();
                        const osc = audioCtx.createOscillator();
                        const gain = audioCtx.createGain();
                        osc.type = 'sine';
                        osc.frequency.value = 880;
                        gain.gain.value = 0.0001;
                        osc.connect(gain);
                        gain.connect(audioCtx.destination);
                        osc.start();
                        gain.gain.exponentialRampToValueAtTime(0.12, audioCtx.currentTime + 0.01);
                        gain.gain.exponentialRampToValueAtTime(0.0001, audioCtx.currentTime + 0.12);
                        osc.stop(audioCtx.currentTime + 0.13);
                    } catch (e) {
                    }
                };

                const hideToast = function () {
                    toastEl?.classList.add('hidden');
                };

                const showToast = function (thread) {
                    if (!toastEl || !toastBtn || !thread) return;
                    const src = photoUrl(thread.photo_path || '');
                    if (toastAvatar) {
                        toastAvatar.innerHTML = src
                            ? `<img src="${src}" class="w-full h-full object-cover" alt="">`
                            : `<i class="fas fa-user text-slate-500"></i>`;
                    }
                    if (toastTitle) toastTitle.textContent = 'New Message';
                    if (toastName) toastName.textContent = String(thread.name || '');
                    if (toastPreview) toastPreview.textContent = String(thread.last_message || '').slice(0, 120);
                    toastEl.classList.remove('hidden');
                    window.setTimeout(function () {
                        if (Number(lastToastThreadId || 0) === Number(thread.thread_id || 0)) hideToast();
                    }, 8000);
                };

                const showDesktopNotification = function (thread) {
                    if (!canDesktopNotify()) return;
                    if (!desktopNotifyEnabled) return;
                    if (Notification.permission !== 'granted') return;
                    if (!thread) return;
                    const title = String(thread.name || 'New Message');
                    const body = String(thread.last_message || '').slice(0, 150);
                    const icon = (function () {
                        const src = photoUrl(thread.photo_path || '');
                        return src || undefined;
                    })();

                    try {
                        const n = new Notification(title, {
                            body: body,
                            icon: icon
                        });
                        n.onclick = function () {
                            try { window.focus(); } catch (e) {}
                            lastToastThreadId = Number(thread.thread_id || 0);
                            openPanel();
                            setActiveThread(Number(thread.thread_id || 0), Number(thread.user_id || 0));
                            n.close();
                        };
                        window.setTimeout(function () {
                            try { n.close(); } catch (e) {}
                        }, 9000);
                    } catch (e) {
                    }
                };

                const addChatBubble = function (msg) {
                    if (!chatList) return;
                    const messageId = Number(msg.id || 0);
                    if (messageId > 0) {
                        if (renderedMessageIds.has(messageId)) return;
                        renderedMessageIds.add(messageId);
                    }
                    const senderId = Number(msg.sender_id || 0);
                    const isMine = senderId === meId;
                    const deletedForAll = Boolean(msg.deleted_for_all);
                    const wrapper = document.createElement('div');
                    wrapper.className = 'flex items-end gap-2 ' + (isMine ? 'justify-end' : 'justify-start');
                    if (messageId > 0) wrapper.dataset.messageId = String(messageId);
                    const clientTempId = String(msg.client_temp_id || '').trim();
                    if (clientTempId) wrapper.dataset.clientTempId = clientTempId;

                    if (!isMine) {
                        const avatar = document.createElement('div');
                        avatar.className = 'w-8 h-8 rounded-xl overflow-hidden bg-white/5 border border-white/10 flex items-center justify-center';
                        const src = photoUrl(msg.sender_photo || '');
                        avatar.innerHTML = src
                            ? `<img src="${src}" class="w-full h-full object-cover" alt="">`
                            : `<i class="fas fa-user text-slate-500 text-xs"></i>`;
                        wrapper.appendChild(avatar);
                    }

                    const bubble = document.createElement('div');
                    bubble.className = (isMine
                        ? 'max-w-[82%] rounded-2xl bg-accent text-slate-900 px-4 py-3 text-sm font-bold relative group'
                        : 'max-w-[82%] rounded-2xl bg-slate-950/40 border border-white/10 text-slate-200 px-4 py-3 text-sm font-bold relative group');

                    const textWrap = document.createElement('div');
                    textWrap.className = deletedForAll ? 'text-[12px] font-black text-slate-300 italic' : '';
                    textWrap.innerHTML = escapeHtml(deletedForAll ? 'This message was deleted.' : (msg.message || ''));
                    bubble.appendChild(textWrap);

                    const menuBtn = document.createElement('button');
                    menuBtn.type = 'button';
                    menuBtn.className = 'absolute -top-3 -right-3 w-9 h-9 rounded-2xl bg-white/10 border border-white/10 text-slate-300 hover:bg-white/20 items-center justify-center flex opacity-100 pointer-events-auto sm:opacity-0 sm:pointer-events-none sm:group-hover:opacity-100 sm:group-hover:pointer-events-auto';
                    menuBtn.innerHTML = '<i class="fas fa-ellipsis-vertical text-[11px]"></i>';
                    menuBtn.setAttribute('aria-label', 'Message options');
                    bubble.appendChild(menuBtn);
                    wrapper.appendChild(bubble);

                    const timeEl = document.createElement('div');
                    timeEl.className = 'mt-2 text-[10px] font-black uppercase tracking-widest ' + (isMine ? 'text-slate-900/60 text-right' : 'text-slate-500 text-right');
                    const createdAt = String(msg.created_at || '').trim();
                    if (createdAt) {
                        const ts = Date.parse(createdAt);
                        if (!Number.isNaN(ts)) {
                            const d = new Date(ts);
                            const hh = String(d.getHours()).padStart(2, '0');
                            const mm = String(d.getMinutes()).padStart(2, '0');
                            timeEl.textContent = `${hh}:${mm}`;
                        }
                    }
                    bubble.appendChild(timeEl);

                    const deleteChatMessage = async function (id, mode) {
                        const body = new URLSearchParams();
                        body.set('message_id', String(id));
                        body.set('mode', String(mode || 'me'));
                        const res = await fetch(baseUrl + '/chat/delete', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                            },
                            body: body.toString()
                        });
                        const data = await res.json().catch(() => null);
                        if (!res.ok || !data || data.success !== true) return null;
                        return data;
                    };

                    const canDeleteForAll = (function () {
                        if (deletedForAll) return false;
                        if (isAdmin) return true;
                        if (!isMine) return false;
                        const createdAt = String(msg.created_at || '').trim();
                        const ts = createdAt ? Date.parse(createdAt) : NaN;
                        if (Number.isNaN(ts)) return true;
                        return (Date.now() - ts) <= (10 * 60 * 1000);
                    })();

                    menuBtn.addEventListener('click', async function () {
                        if (messageId <= 0) return;
                        if (canDeleteForAll) {
                            const all = window.confirm('Delete for everyone?\\nOK = Delete for everyone\\nCancel = Delete only for me');
                            if (all) {
                                const ok = window.confirm('Are you sure you want to delete for everyone?');
                                if (!ok) return;
                                const resp = await deleteChatMessage(messageId, 'all');
                                if (!resp) return;
                                textWrap.className = 'text-[12px] font-black text-slate-300 italic';
                                textWrap.textContent = 'This message was deleted.';
                                loadThreads(true);
                                return;
                            }
                            const okMe = window.confirm('Delete this message for you only?');
                            if (!okMe) return;
                            const respMe = await deleteChatMessage(messageId, 'me');
                            if (!respMe) return;
                            wrapper.remove();
                            loadThreads(true);
                            return;
                        }

                        const ok = window.confirm('Delete this message for you only?');
                        if (!ok) return;
                        const resp = await deleteChatMessage(messageId, 'me');
                        if (!resp) return;
                        wrapper.remove();
                        loadThreads(true);
                    });

                    if (isMine) {
                        const avatar = document.createElement('div');
                        avatar.className = 'w-8 h-8 rounded-xl overflow-hidden bg-white/5 border border-white/10 flex items-center justify-center';
                        const src = photoUrl(mePhoto || '');
                        avatar.innerHTML = src
                            ? `<img src="${src}" class="w-full h-full object-cover" alt="">`
                            : `<i class="fas fa-user text-slate-900/60 text-xs"></i>`;
                        wrapper.appendChild(avatar);
                    }

                    chatList.appendChild(wrapper);
                    scrollToBottom(chatList);
                    return wrapper;
                };

                const setActiveHeader = function (thread) {
                    if (!chatActiveName || !chatActiveStatus || !chatActiveAvatar) return;
                    if (!thread) {
                        chatActiveName.textContent = 'Select a chat';
                        chatActiveStatus.textContent = '';
                        chatActiveAvatar.innerHTML = `<i class="fas fa-user text-slate-500"></i>`;
                        return;
                    }
                    const name = String(thread.name || '').trim();
                    chatActiveName.textContent = name || 'Chat';
                    const isOnline = Boolean(thread.is_online);
                    if (isOnline) {
                        chatActiveStatus.textContent = 'Online';
                    } else {
                        const lastActivity = String(thread.last_activity_at || '').trim();
                        const ts = lastActivity ? Date.parse(lastActivity) : NaN;
                        if (!Number.isNaN(ts)) {
                            const delta = Date.now() - ts;
                            if (delta < 24 * 60 * 60 * 1000) {
                                const d = new Date(ts);
                                const hh = String(d.getHours()).padStart(2, '0');
                                const mm = String(d.getMinutes()).padStart(2, '0');
                                chatActiveStatus.textContent = `Last seen ${hh}:${mm}`;
                            } else {
                                chatActiveStatus.textContent = 'Offline';
                            }
                        } else {
                            chatActiveStatus.textContent = 'Offline';
                        }
                    }
                    const src = photoUrl(thread.photo_path || '');
                    const dot = isOnline ? `<span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-400 border border-slate-900 shadow-[0_0_10px_rgba(52,211,153,0.55)]"></span>` : '';
                    chatActiveAvatar.innerHTML = src
                        ? `<img src="${src}" class="w-full h-full object-cover" alt="">${dot}`
                        : `<i class="fas fa-user text-slate-500"></i>${dot}`;
                };

                const setActiveThread = function (threadId, toUserId) {
                    activeThreadId = Number(threadId || 0);
                    activeToUserId = Number(toUserId || 0);
                    activeLastId = 0;
                    renderedMessageIds = new Set();
                    if (chatList) {
                        chatList.innerHTML = '';
                        if (activeThreadId <= 0 && activeToUserId > 0) {
                            const hint = document.createElement('div');
                            hint.className = 'rounded-2xl bg-white/5 border border-white/10 px-4 py-3 text-[11px] font-bold text-slate-400';
                            hint.textContent = 'Type a message to start this conversation.';
                            chatList.appendChild(hint);
                        }
                    }
                    const t = activeThreadId > 0 ? threadsCacheByThreadId[activeThreadId] : (activeToUserId > 0 ? threadsCacheByUserId[activeToUserId] : null);
                    setActiveHeader(t || null);
                    if (activeThreadId > 0) loadMessages(activeThreadId, false);
                    showConversationView();
                };

                const renderThreads = function (threads) {
                    if (!threadsEl) return;
                    const list = Array.isArray(threads) ? threads : [];
                    const filtered = (function () {
                        const term = String(chatSearchTerm || '').trim().toLowerCase();
                        if (!term) return list;
                        return list.filter((t) => String(t.name || '').toLowerCase().includes(term));
                    })();
                    if (chatListCount) chatListCount.textContent = String(filtered.length);
                    threadsEl.innerHTML = '';
                    filtered.forEach((t) => {
                        const btn = document.createElement('button');
                        btn.type = 'button';
                        const isActive = Number(t.thread_id || 0) === Number(activeThreadId || 0);
                        const unread = Number(t.unread_count || 0);
                        btn.className = 'w-full flex items-start gap-3 px-3 py-3 rounded-2xl transition-all border mb-2 text-left ' +
                            (isActive ? 'bg-white/10 border-white/10' : 'hover:bg-white/10 border-white/5');
                        const src = photoUrl(t.photo_path || '');
                        const preview = String(t.last_message || '').trim();
                        const isOnline = Boolean(t.is_online);
                        btn.innerHTML = `
                            <div class="relative w-9 h-9 rounded-xl overflow-hidden bg-white/5 border border-white/10 flex items-center justify-center shrink-0">
                                ${src ? `<img src="${src}" class="w-full h-full object-cover" alt="">` : `<i class="fas fa-user text-slate-500 text-xs"></i>`}
                                ${isOnline ? `<span class="absolute -bottom-1 -right-1 w-3.5 h-3.5 rounded-full bg-emerald-400 border border-slate-900 shadow-[0_0_10px_rgba(52,211,153,0.55)]"></span>` : ''}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <p class="text-[11px] font-black text-slate-200 truncate">${escapeHtml(t.name || '')}</p>
                                    ${unread > 0 ? `<span class="min-w-[1.4rem] h-6 px-2 rounded-full bg-rose-500 text-white text-[9px] font-black flex items-center justify-center">${unread > 99 ? '99+' : unread}</span>` : ''}
                                </div>
                                <p class="mt-1 text-[10px] font-bold text-slate-500 truncate">${escapeHtml(preview || '')}</p>
                            </div>
                        `;
                        btn.addEventListener('click', function () {
                            const tid = Number(t.thread_id || 0);
                            const uid = Number(t.user_id || 0);
                            if (tid > 0) setActiveThread(tid, uid);
                            else if (uid > 0) setActiveThread(0, uid);
                        });
                        threadsEl.appendChild(btn);
                    });
                };

                const loadThreads = async function (silent) {
                    try {
                        const res = await fetch(baseUrl + '/chat/threads', { headers: { 'Accept': 'application/json' } });
                        const data = await res.json().catch(() => null);
                        if (!res.ok || !data || data.success !== true) return;
                        const threads = Array.isArray(data.threads) ? data.threads : [];
                        allThreads = threads;
                        threadsCacheByThreadId = {};
                        threadsCacheByUserId = {};
                        threads.forEach((t) => {
                            const tid = Number(t.thread_id || 0);
                            const uid = Number(t.user_id || 0);
                            if (tid > 0) threadsCacheByThreadId[tid] = t;
                            if (uid > 0) threadsCacheByUserId[uid] = t;
                        });
                        if (activeThreadId > 0 && threadsCacheByThreadId[activeThreadId]) {
                            setActiveHeader(threadsCacheByThreadId[activeThreadId]);
                        } else if (activeToUserId > 0 && threadsCacheByUserId[activeToUserId]) {
                            setActiveHeader(threadsCacheByUserId[activeToUserId]);
                        }

                        let newUnreadTotal = 0;
                        let newestThread = null;
                        threads.forEach((t) => {
                            const tid = Number(t.thread_id || 0);
                            const unread = Number(t.unread_count || 0);
                            newUnreadTotal += unread;
                            const lastId = Number(t.last_message_id || 0);
                            const prevId = Number(lastThreadMessageId[tid] || 0);
                            if (tid > 0 && lastId > prevId) {
                                lastThreadMessageId[tid] = lastId;
                                if (!silent && unread > 0 && Number(t.last_sender_id || 0) !== meId) {
                                    if (!newestThread || Number(newestThread.last_message_id || 0) < lastId) {
                                        newestThread = t;
                                    }
                                }
                            }
                        });

                        renderThreads(threads);
                        setBadge(newUnreadTotal);

                        if (!silent && newestThread && !isPanelOpen()) {
                            lastToastThreadId = Number(newestThread.thread_id || 0);
                            playNotificationSound();
                            showToast(newestThread);
                            if (document.hidden) showDesktopNotification(newestThread);
                        }

                        totalUnread = newUnreadTotal;

                        if (threads.length > 0 && activeThreadId <= 0 && activeToUserId <= 0) {
                            const firstWithThread = threads.find(t => Number(t.thread_id || 0) > 0);
                            if (firstWithThread) {
                                setActiveThread(Number(firstWithThread.thread_id || 0), Number(firstWithThread.user_id || 0));
                            } else {
                                const firstUser = threads.find(t => Number(t.user_id || 0) > 0);
                                if (firstUser) setActiveThread(0, Number(firstUser.user_id || 0));
                            }
                        }
                    } catch (e) {
                    }
                };

                const loadMessages = async function (threadId, silent) {
                    if (!chatList || threadId <= 0) return;
                    try {
                        const url = baseUrl + '/chat/messages?thread_id=' + encodeURIComponent(String(threadId))
                            + '&limit=80' + (activeLastId > 0 ? '&since_id=' + encodeURIComponent(String(activeLastId)) : '');
                        const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                        const data = await res.json().catch(() => null);
                        if (!res.ok || !data || data.success !== true) return;
                        const messages = Array.isArray(data.messages) ? data.messages : [];

                        if (activeLastId <= 0) {
                            chatList.innerHTML = '';
                            renderedMessageIds = new Set();
                            messages.forEach(addChatBubble);
                            if (!silent) scrollToBottom(chatList);
                        } else {
                            messages.forEach(addChatBubble);
                            if (!silent) scrollToBottom(chatList);
                            if (!silent && messages.length > 0 && document.hidden) playNotificationSound();
                        }

                        const lastId = Number(data.last_id || 0);
                        if (lastId > activeLastId) activeLastId = lastId;
                    } catch (e) {
                    }
                };

                const sendChatMessage = async function () {
                    const text = String(chatInput?.value || '').trim();
                    if (!text) return;
                    if (activeThreadId <= 0 && activeToUserId <= 0) return;

                    const tempId = 'tmp_' + String(Date.now()) + '_' + String(Math.floor(Math.random() * 10000));
                    const optimisticWrapper = addChatBubble({
                        id: 0,
                        sender_id: meId,
                        message: text,
                        created_at: new Date().toISOString(),
                        deleted_for_all: false,
                        client_temp_id: tempId
                    });

                    const body = new URLSearchParams();
                    if (activeThreadId > 0) body.set('thread_id', String(activeThreadId));
                    if (activeThreadId <= 0 && activeToUserId > 0) body.set('to_user_id', String(activeToUserId));
                    body.set('message', text);

                    chatInput.value = '';

                    try {
                        const res = await fetch(baseUrl + '/chat/send', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                            },
                            body: body.toString()
                        });
                        const data = await res.json().catch(() => null);
                        if (!res.ok || !data || data.success !== true) return;
                        const newThreadId = Number(data.thread_id || activeThreadId);
                        const newMessageId = Number(data.message_id || 0);
                        if (newMessageId > 0) {
                            renderedMessageIds.add(newMessageId);
                            if (optimisticWrapper) {
                                optimisticWrapper.dataset.messageId = String(newMessageId);
                                delete optimisticWrapper.dataset.clientTempId;
                            }
                            if (newMessageId > activeLastId) activeLastId = newMessageId;
                        }
                        activeThreadId = newThreadId;
                        if (activeThreadId > 0) loadMessages(activeThreadId, true);
                        loadThreads(true);
                    } catch (e) {
                    }
                };

                const openPanel = function () {
                    panel.classList.remove('hidden');
                    hideToast();
                    loadThreads(true);
                    showListView();
                    if (pollMessagesTimer) window.clearInterval(pollMessagesTimer);
                    pollMessagesTimer = window.setInterval(function () {
                        if (!isPanelOpen()) return;
                        if (activeThreadId <= 0) return;
                        loadMessages(activeThreadId, true);
                    }, 4500);
                };

                const closePanel = function () {
                    panel.classList.add('hidden');
                    if (pollMessagesTimer) window.clearInterval(pollMessagesTimer);
                    pollMessagesTimer = null;
                };

                toggle.addEventListener('click', function () {
                    const open = panel.classList.contains('hidden');
                    if (open) openPanel();
                    else closePanel();
                });
                closeBtn?.addEventListener('click', closePanel);

                chatMobileBack?.addEventListener('click', function () {
                    showListView();
                });

                chatSearch?.addEventListener('input', function () {
                    chatSearchTerm = String(chatSearch.value || '');
                    renderThreads(allThreads);
                });

                chatRefresh?.addEventListener('click', function () {
                    loadThreads(true);
                    if (activeThreadId > 0) loadMessages(activeThreadId, true);
                });

                chatClear?.addEventListener('click', async function () {
                    if (activeThreadId <= 0) return;
                    const ok = window.confirm('Clear this chat for you only?');
                    if (!ok) return;
                    const body = new URLSearchParams();
                    body.set('thread_id', String(activeThreadId));
                    const res = await fetch(baseUrl + '/chat/clear', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                        },
                        body: body.toString()
                    });
                    const data = await res.json().catch(() => null);
                    if (!res.ok || !data || data.success !== true) return;
                    activeLastId = 0;
                    renderedMessageIds = new Set();
                    if (chatList) {
                        chatList.innerHTML = '';
                        const hint = document.createElement('div');
                        hint.className = 'rounded-2xl bg-white/5 border border-white/10 px-4 py-3 text-[11px] font-bold text-slate-400';
                        hint.textContent = 'Chat cleared. Type a message to continue.';
                        chatList.appendChild(hint);
                    }
                    loadThreads(true);
                });

                chatForm?.addEventListener('submit', function (e) {
                    e.preventDefault();
                    sendChatMessage();
                });

                toastBtn?.addEventListener('click', function () {
                    if (lastToastThreadId <= 0) return;
                    openPanel();
                    setActiveThread(lastToastThreadId, 0);
                    hideToast();
                });

                paneChat?.addEventListener('click', function (e) {
                    const t = e.target;
                    if (t && t.closest && t.closest('#support-chat-close')) return;
                    hideToast();
                });

                window.addEventListener('load', function () {
                    desktopNotifyEnabled = (function () {
                        try { return localStorage.getItem(desktopNotifyKey) === '1'; } catch (e) { return false; }
                    })();
                    syncDesktopNotifyButton();
                    window.setTimeout(function () {
                        loadThreads(true);
                    }, 900);
                    let inFlightThreads = false;
                    let inFlightMessages = false;
                    const schedule = function () {
                        if (pollThreadsTimer) window.clearInterval(pollThreadsTimer);
                        const ms = isPanelOpen() ? 6500 : 18000;
                        pollThreadsTimer = window.setInterval(function () {
                            if (document.hidden && !isPanelOpen()) return;
                            if (!inFlightThreads) {
                                inFlightThreads = true;
                                Promise.resolve(loadThreads(false)).finally(() => { inFlightThreads = false; });
                            }
                            if (isPanelOpen() && activeThreadId > 0 && !inFlightMessages) {
                                inFlightMessages = true;
                                Promise.resolve(loadMessages(activeThreadId, true)).finally(() => { inFlightMessages = false; });
                            }
                        }, ms);
                    };
                    schedule();
                    document.addEventListener('visibilitychange', function () {
                        schedule();
                    });
                    toggle.addEventListener('click', function () {
                        schedule();
                    });
                });

                desktopNotifyBtn?.addEventListener('click', async function () {
                    if (!canDesktopNotify()) return;
                    playNotificationSound();
                    if (Notification.permission === 'granted') {
                        desktopNotifyEnabled = !desktopNotifyEnabled;
                        try { localStorage.setItem(desktopNotifyKey, desktopNotifyEnabled ? '1' : '0'); } catch (e) {}
                        syncDesktopNotifyButton();
                        return;
                    }

                    if (Notification.permission === 'denied') {
                        desktopNotifyEnabled = false;
                        try { localStorage.setItem(desktopNotifyKey, '0'); } catch (e) {}
                        syncDesktopNotifyButton();
                        return;
                    }

                    try {
                        const perm = await Notification.requestPermission();
                        desktopNotifyEnabled = perm === 'granted';
                        try { localStorage.setItem(desktopNotifyKey, desktopNotifyEnabled ? '1' : '0'); } catch (e) {}
                        syncDesktopNotifyButton();
                    } catch (e) {
                    }
                });
            })();
        </script>
    <?php endif; ?>
    <script>
        (function () {
            const container = document.getElementById('seasonal-particles');
            if (!container) return;

            const holidayThemes = new Set(['christmas', 'newyear', 'easter', 'mothersday', 'fathersday', 'foundersday']);

            const clear = function () {
                container.innerHTML = '';
                container.style.display = 'none';
            };

            const rand = function (min, max) {
                return min + Math.random() * (max - min);
            };

            const make = function (kind, sizePx, leftVw, durationS, delayS, driftPx, opacity, r0, r1) {
                const el = document.createElement('div');
                el.className = 'seasonal-particle ' + kind;
                el.style.left = leftVw + 'vw';
                el.style.width = sizePx + 'px';
                el.style.height = sizePx + 'px';
                if (kind === 'confetti') el.style.height = Math.max(10, Math.round(sizePx * 2.2)) + 'px';
                el.style.setProperty('--p-drift', String(driftPx));
                el.style.setProperty('--p-o', String(opacity));
                el.style.setProperty('--p-r0', r0 + 'deg');
                el.style.setProperty('--p-r1', r1 + 'deg');
                el.style.animation = 'seasonalFall ' + durationS + 's linear ' + delayS + 's infinite';
                container.appendChild(el);
            };

            const build = function (theme) {
                if (!holidayThemes.has(theme || '')) {
                    clear();
                    return;
                }

                if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    clear();
                    return;
                }

                container.innerHTML = '';
                container.style.display = 'block';

                const isSmall = window.innerWidth < 768;
                const base = isSmall ? 0.7 : 1;

                if (theme === 'christmas') {
                    const snowCount = Math.round(42 * base);
                    const starCount = Math.round(14 * base);
                    for (let i = 0; i < snowCount; i++) {
                        make(
                            'snow',
                            rand(5, 11),
                            rand(0, 100),
                            rand(8, 14),
                            rand(-14, 0),
                            rand(-80, 80),
                            rand(0.35, 0.95),
                            rand(0, 180),
                            rand(180, 540)
                        );
                    }
                    for (let i = 0; i < starCount; i++) {
                        make(
                            'star',
                            rand(12, 24),
                            rand(0, 100),
                            rand(10, 18),
                            rand(-18, 0),
                            rand(-120, 120),
                            rand(0.25, 0.7),
                            rand(0, 360),
                            rand(360, 900)
                        );
                    }
                    return;
                }

                if (theme === 'newyear') {
                    const confettiCount = Math.round(30 * base);
                    const sparkleCount = Math.round(18 * base);
                    for (let i = 0; i < confettiCount; i++) {
                        make(
                            'confetti',
                            rand(7, 12),
                            rand(0, 100),
                            rand(7, 12),
                            rand(-12, 0),
                            rand(-140, 140),
                            rand(0.28, 0.9),
                            rand(0, 360),
                            rand(720, 1440)
                        );
                    }
                    for (let i = 0; i < sparkleCount; i++) {
                        make(
                            'sparkle',
                            rand(4, 8),
                            rand(0, 100),
                            rand(6, 10),
                            rand(-10, 0),
                            rand(-110, 110),
                            rand(0.18, 0.6),
                            rand(0, 180),
                            rand(180, 540)
                        );
                    }
                    return;
                }

                const sparkleCount = Math.round(34 * base);
                for (let i = 0; i < sparkleCount; i++) {
                    make(
                        'sparkle',
                        rand(5, 9),
                        rand(0, 100),
                        rand(9, 16),
                        rand(-16, 0),
                        rand(-90, 90),
                        rand(0.14, 0.55),
                        rand(0, 90),
                        rand(270, 720)
                    );
                }
            };

            const sync = function () {
                const theme = document.documentElement.getAttribute('data-theme') || '';
                build(theme);
            };

            sync();

            const observer = new MutationObserver(function (records) {
                for (const r of records) {
                    if (r.type === 'attributes' && r.attributeName === 'data-theme') {
                        sync();
                        return;
                    }
                }
            });

            observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
            window.addEventListener('resize', function () {
                window.clearTimeout(window.__agSeasonalResizeTimer);
                window.__agSeasonalResizeTimer = window.setTimeout(sync, 180);
            });
        })();
    </script>
    <script>
        (function () {
            const decorate = function (input) {
                if (!input || input.dataset.pwToggle === '1') return;
                const parent = input.closest('.relative') || input.parentElement;
                if (!parent) return;
                if (parent.querySelector('.ag-password-toggle')) {
                    input.dataset.pwToggle = '1';
                    return;
                }
                const computed = window.getComputedStyle(parent);
                if (computed && computed.position === 'static') {
                    parent.style.position = 'relative';
                }
                if (!input.style.paddingRight) {
                    input.style.paddingRight = '3.5rem';
                }
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ag-password-toggle absolute inset-y-0 right-0 pr-5 flex items-center text-slate-500 hover:text-accent transition-colors';
                btn.setAttribute('aria-label', 'Show password');
                btn.innerHTML = '<i class="fas fa-eye text-[14px]"></i>';
                btn.addEventListener('click', function () {
                    input.type = input.type === 'password' ? 'text' : 'password';
                    const icon = btn.querySelector('i');
                    const isHidden = input.type === 'password';
                    btn.setAttribute('aria-label', isHidden ? 'Show password' : 'Hide password');
                    if (icon) {
                        icon.classList.toggle('fa-eye', isHidden);
                        icon.classList.toggle('fa-eye-slash', !isHidden);
                    }
                });
                parent.appendChild(btn);
                input.dataset.pwToggle = '1';
            };

            document.querySelectorAll('input[type="password"]').forEach(decorate);
        })();
    </script>
    <?php if (in_array($current_route, ['dashboard', 'pastor'], true) && Session::get('just_logged_in')): ?>
        <script>
            (function () {
                const name = <?php echo json_encode(Session::get('user_name', 'Admin')); ?>;
                const isPastor = <?php echo Auth::isPastor() ? 'true' : 'false'; ?>;
                const text = isPastor ? `Welcome Reverend ${name}.` : `Welcome ${name}.`;
                if (!('speechSynthesis' in window)) return;
                const u = new SpeechSynthesisUtterance(text);
                u.rate = 1;
                u.pitch = 1;
                u.volume = 1;
                window.speechSynthesis.cancel();
                window.speechSynthesis.speak(u);
            })();
        </script>
        <?php Session::remove('just_logged_in'); ?>
    <?php endif; ?>
</body>
</html>
