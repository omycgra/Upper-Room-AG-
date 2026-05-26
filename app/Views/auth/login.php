<?php 
    $churchName = AppConfig::getSetting('church_name', 'Church Management');
    $theme = AppConfig::getSetting('theme', 'dark');
    $theme = in_array($theme, ['dark', 'light', 'ocean', 'sunset'], true) ? $theme : 'dark';
    $logoRelativePath = Branding::getLogoPath();
    $mode = isset($mode) ? (string)$mode : 'login';
    $token = isset($token) ? (string)$token : (string)($_GET['token'] ?? '');
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo $theme; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login | <?php echo $churchName; ?></title>
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
                    const month = Math.floor((h + l - 7 * m + 114) / 31);
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

                    const mother = nthWeekdayOfMonth(y, 4, 0, 2);
                    if (isSameDay(today, mother)) return 'mothersday';

                    const father = nthWeekdayOfMonth(y, 5, 0, 3);
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --login-accent: #fbbf24;
            --login-accent-2: #d97706;
            --login-accent-ring: rgba(251, 191, 36, 0.15);
            --login-shell-glow: linear-gradient(135deg, rgba(251, 191, 36, 0.14), rgba(59, 130, 246, 0.1), rgba(255, 255, 255, 0.04));
            --login-accent-soft-bg: rgba(251, 191, 36, 0.10);
            --login-accent-border: rgba(251, 191, 36, 0.20);
        }

        html[data-theme="ocean"] {
            --login-accent: #38bdf8;
            --login-accent-2: #0ea5e9;
            --login-accent-ring: rgba(56, 189, 248, 0.16);
            --login-shell-glow: linear-gradient(135deg, rgba(56, 189, 248, 0.16), rgba(99, 102, 241, 0.12), rgba(255, 255, 255, 0.04));
            --login-accent-soft-bg: rgba(56, 189, 248, 0.12);
            --login-accent-border: rgba(56, 189, 248, 0.22);
        }

        html[data-theme="sunset"] {
            --login-accent: #fb7185;
            --login-accent-2: #f97316;
            --login-accent-ring: rgba(251, 113, 133, 0.16);
            --login-shell-glow: linear-gradient(135deg, rgba(251, 113, 133, 0.16), rgba(249, 115, 22, 0.12), rgba(255, 255, 255, 0.04));
            --login-accent-soft-bg: rgba(251, 113, 133, 0.12);
            --login-accent-border: rgba(251, 113, 133, 0.22);
        }

        html[data-theme="christmas"] {
            --login-accent: #22c55e;
            --login-accent-2: #16a34a;
            --login-accent-ring: rgba(34, 197, 94, 0.16);
            --login-shell-glow: linear-gradient(135deg, rgba(34, 197, 94, 0.18), rgba(239, 68, 68, 0.10), rgba(255, 255, 255, 0.04));
            --login-accent-soft-bg: rgba(34, 197, 94, 0.12);
            --login-accent-border: rgba(34, 197, 94, 0.22);
        }

        html[data-theme="newyear"] {
            --login-accent: #a78bfa;
            --login-accent-2: #6366f1;
            --login-accent-ring: rgba(167, 139, 250, 0.18);
            --login-shell-glow: linear-gradient(135deg, rgba(167, 139, 250, 0.18), rgba(59, 130, 246, 0.10), rgba(255, 255, 255, 0.04));
            --login-accent-soft-bg: rgba(167, 139, 250, 0.12);
            --login-accent-border: rgba(167, 139, 250, 0.22);
        }

        html[data-theme="easter"] {
            --login-accent: #c084fc;
            --login-accent-2: #a78bfa;
            --login-accent-ring: rgba(192, 132, 252, 0.18);
            --login-shell-glow: linear-gradient(135deg, rgba(192, 132, 252, 0.18), rgba(96, 165, 250, 0.10), rgba(255, 255, 255, 0.04));
            --login-accent-soft-bg: rgba(192, 132, 252, 0.12);
            --login-accent-border: rgba(192, 132, 252, 0.22);
        }

        html[data-theme="mothersday"] {
            --login-accent: #f472b6;
            --login-accent-2: #fb7185;
            --login-accent-ring: rgba(244, 114, 182, 0.18);
            --login-shell-glow: linear-gradient(135deg, rgba(244, 114, 182, 0.18), rgba(251, 113, 133, 0.10), rgba(255, 255, 255, 0.04));
            --login-accent-soft-bg: rgba(244, 114, 182, 0.12);
            --login-accent-border: rgba(244, 114, 182, 0.22);
        }

        html[data-theme="fathersday"] {
            --login-accent: #60a5fa;
            --login-accent-2: #1d4ed8;
            --login-accent-ring: rgba(96, 165, 250, 0.18);
            --login-shell-glow: linear-gradient(135deg, rgba(96, 165, 250, 0.18), rgba(29, 78, 216, 0.10), rgba(255, 255, 255, 0.04));
            --login-accent-soft-bg: rgba(96, 165, 250, 0.12);
            --login-accent-border: rgba(96, 165, 250, 0.22);
        }

        html[data-theme="foundersday"] {
            --login-accent: #fbbf24;
            --login-accent-2: #ef4444;
            --login-accent-ring: rgba(251, 191, 36, 0.18);
            --login-shell-glow: linear-gradient(135deg, rgba(251, 191, 36, 0.18), rgba(239, 68, 68, 0.10), rgba(255, 255, 255, 0.04));
            --login-accent-soft-bg: rgba(251, 191, 36, 0.12);
            --login-accent-border: rgba(251, 191, 36, 0.22);
        }

        .login-accent-text { color: var(--login-accent) !important; }
        .login-accent-text-soft { color: var(--login-accent) !important; opacity: 0.9; }
        .login-accent-bg { background-color: var(--login-accent) !important; }
        .login-accent-bg-soft { background-color: var(--login-accent-soft-bg) !important; }
        .login-accent-border { border-color: var(--login-accent-border) !important; }
        .login-accent-hover:hover { color: var(--login-accent) !important; }
        .group:focus-within .focus-accent { color: var(--login-accent) !important; }
        .input-accent:focus { border-color: var(--login-accent) !important; }
        .peer:checked + .remember-track { background-color: var(--login-accent) !important; }

        body {
            font-family: 'Inter', sans-serif;
        }
        .mesh-gradient {
            background-color: #0f172a; /* Match main theme */
            background-image: 
                radial-gradient(at 0% 0%, hsla(161, 84%, 15%, 1) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(158, 84%, 10%, 1) 0, transparent 50%), 
                radial-gradient(at 100% 100%, hsla(160, 84%, 5%, 1) 0, transparent 50%), 
                radial-gradient(at 0% 100%, hsla(162, 84%, 12%, 1) 0, transparent 50%);
        }
        html[data-theme="light"] .mesh-gradient {
            background-color: #ffffff;
            background-image:
                radial-gradient(at 0% 0%, rgba(241, 245, 249, 1) 0, transparent 55%),
                radial-gradient(at 100% 0%, rgba(226, 232, 240, 1) 0, transparent 55%),
                radial-gradient(at 100% 100%, rgba(241, 245, 249, 1) 0, transparent 55%),
                radial-gradient(at 0% 100%, rgba(226, 232, 240, 1) 0, transparent 55%);
        }
        html[data-theme="ocean"] .mesh-gradient {
            background-color: #00121f;
            background-image:
                radial-gradient(at 0% 0%, rgba(14, 165, 233, 0.28) 0, transparent 55%),
                radial-gradient(at 100% 0%, rgba(56, 189, 248, 0.22) 0, transparent 55%),
                radial-gradient(at 100% 100%, rgba(30, 64, 175, 0.2) 0, transparent 55%),
                radial-gradient(at 0% 100%, rgba(2, 132, 199, 0.22) 0, transparent 55%);
        }
        html[data-theme="sunset"] .mesh-gradient {
            background-color: #1a0b13;
            background-image:
                radial-gradient(at 0% 0%, rgba(251, 113, 133, 0.26) 0, transparent 55%),
                radial-gradient(at 100% 0%, rgba(249, 115, 22, 0.22) 0, transparent 55%),
                radial-gradient(at 100% 100%, rgba(147, 51, 234, 0.16) 0, transparent 55%),
                radial-gradient(at 0% 100%, rgba(244, 63, 94, 0.18) 0, transparent 55%);
        }
        html[data-theme="christmas"] .mesh-gradient {
            background-color: #05140b;
            background-image:
                radial-gradient(at 0% 0%, rgba(34, 197, 94, 0.28) 0, transparent 55%),
                radial-gradient(at 100% 0%, rgba(239, 68, 68, 0.18) 0, transparent 55%),
                radial-gradient(at 100% 100%, rgba(34, 197, 94, 0.18) 0, transparent 55%),
                radial-gradient(at 0% 100%, rgba(22, 163, 74, 0.22) 0, transparent 55%);
        }
        html[data-theme="newyear"] .mesh-gradient {
            background-color: #0b0a1a;
            background-image:
                radial-gradient(at 0% 0%, rgba(167, 139, 250, 0.28) 0, transparent 55%),
                radial-gradient(at 100% 0%, rgba(99, 102, 241, 0.22) 0, transparent 55%),
                radial-gradient(at 100% 100%, rgba(59, 130, 246, 0.18) 0, transparent 55%),
                radial-gradient(at 0% 100%, rgba(167, 139, 250, 0.18) 0, transparent 55%);
        }
        html[data-theme="easter"] .mesh-gradient {
            background-color: #120726;
            background-image:
                radial-gradient(at 0% 0%, rgba(192, 132, 252, 0.28) 0, transparent 55%),
                radial-gradient(at 100% 0%, rgba(167, 139, 250, 0.22) 0, transparent 55%),
                radial-gradient(at 100% 100%, rgba(96, 165, 250, 0.18) 0, transparent 55%),
                radial-gradient(at 0% 100%, rgba(192, 132, 252, 0.18) 0, transparent 55%);
        }
        html[data-theme="mothersday"] .mesh-gradient {
            background-color: #1a0b1f;
            background-image:
                radial-gradient(at 0% 0%, rgba(244, 114, 182, 0.28) 0, transparent 55%),
                radial-gradient(at 100% 0%, rgba(251, 113, 133, 0.22) 0, transparent 55%),
                radial-gradient(at 100% 100%, rgba(147, 51, 234, 0.16) 0, transparent 55%),
                radial-gradient(at 0% 100%, rgba(244, 114, 182, 0.18) 0, transparent 55%);
        }
        html[data-theme="fathersday"] .mesh-gradient {
            background-color: #031225;
            background-image:
                radial-gradient(at 0% 0%, rgba(96, 165, 250, 0.28) 0, transparent 55%),
                radial-gradient(at 100% 0%, rgba(29, 78, 216, 0.22) 0, transparent 55%),
                radial-gradient(at 100% 100%, rgba(56, 189, 248, 0.18) 0, transparent 55%),
                radial-gradient(at 0% 100%, rgba(96, 165, 250, 0.18) 0, transparent 55%);
        }
        html[data-theme="foundersday"] .mesh-gradient {
            background-color: #17110a;
            background-image:
                radial-gradient(at 0% 0%, rgba(251, 191, 36, 0.28) 0, transparent 55%),
                radial-gradient(at 100% 0%, rgba(239, 68, 68, 0.20) 0, transparent 55%),
                radial-gradient(at 100% 100%, rgba(251, 191, 36, 0.18) 0, transparent 55%),
                radial-gradient(at 0% 100%, rgba(234, 179, 8, 0.22) 0, transparent 55%);
        }
        .login-shell {
            position: relative;
        }
        .login-shell::before {
            content: "";
            position: absolute;
            inset: -18px;
            border-radius: 2.8rem;
            background: var(--login-shell-glow);
            filter: blur(18px);
            z-index: 0;
        }
        .login-card {
            position: relative;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.82), rgba(15, 23, 42, 0.72));
            backdrop-filter: blur(26px);
            -webkit-backdrop-filter: blur(26px);
            box-shadow: 0 35px 80px -18px rgba(2, 6, 23, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        html[data-theme="light"] .login-card {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.9), rgba(248, 250, 252, 0.82));
            border: 1px solid rgba(15, 23, 42, 0.12);
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.18);
        }
        .glass-panel {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.08);
        }
        html[data-theme="light"] .glass-panel {
            background: rgba(255, 255, 255, 0.68);
            border: 1px solid rgba(15, 23, 42, 0.08);
        }

        html[data-theme="christmas"] .login-card,
        html[data-theme="newyear"] .login-card,
        html[data-theme="easter"] .login-card,
        html[data-theme="mothersday"] .login-card,
        html[data-theme="fathersday"] .login-card,
        html[data-theme="foundersday"] .login-card {
            background: linear-gradient(180deg, rgba(2, 6, 23, 0.9), rgba(2, 6, 23, 0.76));
            border: 1px solid rgba(255, 255, 255, 0.14);
            box-shadow: 0 35px 80px -18px rgba(2, 6, 23, 0.78);
        }

        html[data-theme="christmas"] .brand-panel,
        html[data-theme="newyear"] .brand-panel,
        html[data-theme="easter"] .brand-panel,
        html[data-theme="mothersday"] .brand-panel,
        html[data-theme="fathersday"] .brand-panel,
        html[data-theme="foundersday"] .brand-panel {
            background: linear-gradient(180deg, rgba(2, 6, 23, 0.78), rgba(2, 6, 23, 0.5));
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 30px 80px -35px rgba(2, 6, 23, 0.86);
        }

        html[data-theme="christmas"] .stat-chip,
        html[data-theme="newyear"] .stat-chip,
        html[data-theme="easter"] .stat-chip,
        html[data-theme="mothersday"] .stat-chip,
        html[data-theme="fathersday"] .stat-chip,
        html[data-theme="foundersday"] .stat-chip {
            background: rgba(2, 6, 23, 0.44);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        html[data-theme="christmas"] .feature-tile,
        html[data-theme="newyear"] .feature-tile,
        html[data-theme="easter"] .feature-tile,
        html[data-theme="mothersday"] .feature-tile,
        html[data-theme="fathersday"] .feature-tile,
        html[data-theme="foundersday"] .feature-tile {
            background: rgba(2, 6, 23, 0.38);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        html[data-theme="christmas"] .glass-panel,
        html[data-theme="newyear"] .glass-panel,
        html[data-theme="easter"] .glass-panel,
        html[data-theme="mothersday"] .glass-panel,
        html[data-theme="fathersday"] .glass-panel,
        html[data-theme="foundersday"] .glass-panel {
            background: rgba(2, 6, 23, 0.42);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.06);
        }

        html[data-theme="christmas"] .bg-white\/5,
        html[data-theme="newyear"] .bg-white\/5,
        html[data-theme="easter"] .bg-white\/5,
        html[data-theme="mothersday"] .bg-white\/5,
        html[data-theme="fathersday"] .bg-white\/5,
        html[data-theme="foundersday"] .bg-white\/5 { background-color: rgba(2, 6, 23, 0.42) !important; }

        html[data-theme="christmas"] .bg-white\/10,
        html[data-theme="newyear"] .bg-white\/10,
        html[data-theme="easter"] .bg-white\/10,
        html[data-theme="mothersday"] .bg-white\/10,
        html[data-theme="fathersday"] .bg-white\/10,
        html[data-theme="foundersday"] .bg-white\/10 { background-color: rgba(2, 6, 23, 0.56) !important; }

        html[data-theme="christmas"] .border-white\/10,
        html[data-theme="newyear"] .border-white\/10,
        html[data-theme="easter"] .border-white\/10,
        html[data-theme="mothersday"] .border-white\/10,
        html[data-theme="fathersday"] .border-white\/10,
        html[data-theme="foundersday"] .border-white\/10 { border-color: rgba(255, 255, 255, 0.16) !important; }

        html[data-theme="christmas"] .border-white\/20,
        html[data-theme="newyear"] .border-white\/20,
        html[data-theme="easter"] .border-white\/20,
        html[data-theme="mothersday"] .border-white\/20,
        html[data-theme="fathersday"] .border-white\/20,
        html[data-theme="foundersday"] .border-white\/20 { border-color: rgba(255, 255, 255, 0.22) !important; }

        html[data-theme="christmas"] [class*="bg-white/[0.03]"],
        html[data-theme="newyear"] [class*="bg-white/[0.03]"],
        html[data-theme="easter"] [class*="bg-white/[0.03]"],
        html[data-theme="mothersday"] [class*="bg-white/[0.03]"],
        html[data-theme="fathersday"] [class*="bg-white/[0.03]"],
        html[data-theme="foundersday"] [class*="bg-white/[0.03]"] { background-color: rgba(2, 6, 23, 0.38) !important; }

        html[data-theme="christmas"] [class*="bg-white/[0.04]"],
        html[data-theme="newyear"] [class*="bg-white/[0.04]"],
        html[data-theme="easter"] [class*="bg-white/[0.04]"],
        html[data-theme="mothersday"] [class*="bg-white/[0.04]"],
        html[data-theme="fathersday"] [class*="bg-white/[0.04]"],
        html[data-theme="foundersday"] [class*="bg-white/[0.04]"] { background-color: rgba(2, 6, 23, 0.42) !important; }
        html[data-theme="light"] .text-white { color: #0f172a !important; }
        html[data-theme="light"] .text-red-200 { color: #991b1b !important; }
        .input-focus:focus {
            box-shadow: 0 0 0 4px var(--login-accent-ring);
        }
        .btn-gradient {
            background: linear-gradient(135deg, var(--login-accent) 0%, var(--login-accent-2) 100%);
            box-shadow: 0 18px 40px -18px rgba(0, 0, 0, 0.55);
        }
        .login-card::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            pointer-events: none;
            background: linear-gradient(135deg, rgba(255,255,255,0.14), transparent 28%, transparent 72%, rgba(255,255,255,0.05));
        }
        #login_type {
            background-color: #020617 !important;
            color: #f8fafc !important;
            color-scheme: dark;
        }
        #login_type option {
            background-color: #020617 !important;
            color: #f8fafc !important;
        }

        .orange-glow {
            position: fixed;
            border-radius: 9999px;
            background: radial-gradient(circle at 30% 30%, rgba(249, 115, 22, 0.22), rgba(249, 115, 22, 0.06));
            filter: blur(0px);
            border: 1px solid rgba(249, 115, 22, 0.10);
            box-shadow: inset 0 0 30px rgba(249, 115, 22, 0.10), 0 35px 80px rgba(0, 0, 0, 0.25);
            z-index: 0;
            pointer-events: none;
            backdrop-filter: blur(35px);
            -webkit-backdrop-filter: blur(35px);
        }

        .orange-glow-1 {
            width: 420px;
            height: 420px;
            top: -140px;
            right: -140px;
            animation: floatOrange1 22s ease-in-out infinite alternate;
        }

        .orange-glow-2 {
            width: 320px;
            height: 320px;
            bottom: -120px;
            left: -120px;
            animation: floatOrange2 26s ease-in-out infinite alternate;
        }

        .orange-glow-3 {
            width: 260px;
            height: 260px;
            top: 40%;
            left: 60%;
            transform: translate(-50%, -50%);
            animation: floatOrange3 18s ease-in-out infinite alternate;
        }
        .blue-glow {
            position: fixed;
            border-radius: 9999px;
            background: radial-gradient(circle at 30% 30%, rgba(59, 130, 246, 0.22), rgba(59, 130, 246, 0.05));
            border: 1px solid rgba(96, 165, 250, 0.18);
            box-shadow: inset 0 0 32px rgba(96, 165, 250, 0.12), 0 35px 90px rgba(2, 6, 23, 0.25);
            z-index: 0;
            pointer-events: none;
            backdrop-filter: blur(35px);
            -webkit-backdrop-filter: blur(35px);
        }
        .blue-glow-1 {
            width: 300px;
            height: 300px;
            top: 14%;
            left: 8%;
            animation: floatBlue1 20s ease-in-out infinite alternate;
        }

        @keyframes floatOrange1 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(-60px, 80px) scale(1.08); }
        }
        @keyframes floatOrange2 {
            0% { transform: translate(0, 0) scale(1); }
            100% { transform: translate(90px, -70px) scale(1.06); }
        }
        @keyframes floatOrange3 {
            0% { transform: translate(-50%, -50%) scale(1); opacity: 0.8; }
            100% { transform: translate(calc(-50% - 80px), calc(-50% + 60px)) scale(1.1); opacity: 1; }
        }
        @keyframes floatBlue1 {
            0% { transform: translate(0, 0) scale(1); opacity: 0.78; }
            100% { transform: translate(70px, 40px) scale(1.12); opacity: 1; }
        }
        .page-shell {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 1180px;
        }

        .auth-seasonal-particles {
            position: fixed;
            inset: 0;
            overflow: hidden;
            pointer-events: none;
            z-index: 0;
        }

        .auth-seasonal-particle {
            position: absolute;
            top: -12vh;
            left: 0;
            opacity: 0;
            transform: translate3d(0, 0, 0);
            will-change: transform, opacity;
        }

        .auth-seasonal-particle.snow {
            border-radius: 9999px;
            background: rgba(191, 219, 254, var(--p-o, 0.85));
            filter: drop-shadow(0 0 10px rgba(191, 219, 254, 0.22));
        }

        .auth-seasonal-particle.star {
            background: rgba(251, 191, 36, var(--p-o, 0.85));
            clip-path: polygon(50% 0%, 61% 35%, 98% 35%, 68% 57%, 79% 92%, 50% 71%, 21% 92%, 32% 57%, 2% 35%, 39% 35%);
            filter: drop-shadow(0 0 12px rgba(251, 191, 36, 0.22));
        }

        .auth-seasonal-particle.sparkle {
            border-radius: 9999px;
            background: rgba(255, 255, 255, var(--p-o, 0.75));
            box-shadow: 0 0 16px rgba(255, 255, 255, 0.18);
        }

        .auth-seasonal-particle.confetti {
            border-radius: 0.35rem;
            background: linear-gradient(180deg, rgba(167, 139, 250, var(--p-o, 0.85)), rgba(59, 130, 246, var(--p-o, 0.72)));
            box-shadow: 0 0 16px rgba(167, 139, 250, 0.15);
        }

        @keyframes authSeasonalFall {
            0% { transform: translate3d(0, 0, 0) rotate(var(--p-r0, 0deg)); opacity: 0; }
            10% { opacity: var(--p-o, 0.85); }
            100% { transform: translate3d(calc(var(--p-drift, 0) * 1px), 120vh, 0) rotate(var(--p-r1, 360deg)); opacity: 0; }
        }

        .christmas-tree-wrap {
            position: fixed;
            left: -20px;
            bottom: -26px;
            width: min(320px, 44vw);
            z-index: 0;
            opacity: 0;
            transform: translateY(18px) scale(0.96);
            transition: opacity 0.35s ease, transform 0.35s ease;
            pointer-events: none;
            filter: drop-shadow(0 30px 70px rgba(2, 6, 23, 0.45));
        }

        html[data-theme="christmas"] .christmas-tree-wrap {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .seasonal-login-deco {
            position: fixed;
            z-index: 0;
            opacity: 0;
            transform: translateY(18px) scale(0.96);
            transition: opacity 0.35s ease, transform 0.35s ease;
            pointer-events: none;
            filter: drop-shadow(0 30px 70px rgba(2, 6, 23, 0.45));
        }

        .newyear-deco-wrap {
            right: -24px;
            bottom: -30px;
            width: min(360px, 46vw);
        }

        .easter-deco-wrap {
            left: -24px;
            bottom: -26px;
            width: min(340px, 44vw);
        }

        .mothersday-deco-wrap {
            right: -18px;
            top: -24px;
            width: min(340px, 44vw);
        }

        .fathersday-deco-wrap {
            left: -18px;
            top: -26px;
            width: min(320px, 42vw);
        }

        .foundersday-deco-wrap {
            right: -22px;
            bottom: -28px;
            width: min(340px, 44vw);
        }

        html[data-theme="newyear"] .newyear-deco-wrap,
        html[data-theme="easter"] .easter-deco-wrap,
        html[data-theme="mothersday"] .mothersday-deco-wrap,
        html[data-theme="fathersday"] .fathersday-deco-wrap,
        html[data-theme="foundersday"] .foundersday-deco-wrap {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        @media (max-width: 640px) {
            .christmas-tree-wrap {
                left: -74px;
                width: min(280px, 62vw);
                opacity: 0.78;
            }

            .newyear-deco-wrap,
            .foundersday-deco-wrap {
                right: -88px;
                width: min(300px, 66vw);
                opacity: 0.82;
            }

            .easter-deco-wrap,
            .fathersday-deco-wrap {
                left: -88px;
                width: min(290px, 66vw);
                opacity: 0.82;
            }

            .mothersday-deco-wrap {
                right: -92px;
                top: -72px;
                width: min(290px, 66vw);
                opacity: 0.78;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .auth-seasonal-particles { display: none; }
            .christmas-tree-wrap { transition: none; }
            .seasonal-login-deco { transition: none; }
        }
        .brand-panel {
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(15, 23, 42, 0.58), rgba(15, 23, 42, 0.28));
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 30px 80px -35px rgba(2, 6, 23, 0.85);
        }
        html[data-theme="light"] .brand-panel {
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.84), rgba(248, 250, 252, 0.72));
            border: 1px solid rgba(15, 23, 42, 0.1);
            box-shadow: 0 24px 50px -30px rgba(15, 23, 42, 0.25);
        }
        .stat-chip {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.06);
        }
        html[data-theme="light"] .stat-chip {
            background: rgba(255, 255, 255, 0.75);
            border-color: rgba(15, 23, 42, 0.08);
        }
        .feature-tile {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        html[data-theme="light"] .feature-tile {
            background: rgba(255, 255, 255, 0.7);
            border-color: rgba(15, 23, 42, 0.08);
        }
        @media (max-width: 767px) {
            .orange-glow-1 {
                width: 240px;
                height: 240px;
                top: -80px;
                right: -80px;
            }
            .orange-glow-2 {
                width: 220px;
                height: 220px;
                bottom: -70px;
                left: -70px;
            }
            .orange-glow-3 {
                width: 170px;
                height: 170px;
                top: 48%;
                left: 72%;
            }
            .blue-glow-1 {
                width: 190px;
                height: 190px;
                top: 10%;
                left: -30px;
            }
        }

        .page-loader-overlay {
            position: fixed;
            inset: 0;
            z-index: 120;
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
            background: rgba(15, 23, 42, 0.9);
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 30px 90px rgba(2, 6, 23, 0.45);
        }

        html[data-theme="light"] .page-loader-card {
            background: rgba(255, 255, 255, 0.94);
            border-color: rgba(15, 23, 42, 0.1);
        }

        .page-loader-spinner {
            width: 3.25rem;
            height: 3.25rem;
            margin: 0 auto 1rem;
            border-radius: 9999px;
            border: 4px solid rgba(255, 255, 255, 0.12);
            border-top-color: var(--login-accent);
            animation: authLoaderSpin 0.8s linear infinite;
        }

        html[data-theme="light"] .page-loader-spinner {
            border-color: rgba(15, 23, 42, 0.12);
            border-top-color: var(--login-accent);
        }

        @keyframes authLoaderSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="mesh-gradient min-h-screen flex items-center justify-center px-4 py-6 sm:px-6 sm:py-10 lg:px-8">
    <div id="auth-page-loader" class="page-loader-overlay" aria-live="polite">
        <div class="page-loader-card">
            <div class="page-loader-spinner"></div>
            <p id="auth-page-loader-text" class="text-sm font-black uppercase tracking-[0.24em] login-accent-text">Loading</p>
            <p id="auth-page-loader-subtext" class="mt-3 text-sm text-slate-300">Please wait while the page gets ready.</p>
        </div>
    </div>
    <div class="blue-glow blue-glow-1"></div>
    <div class="orange-glow orange-glow-1"></div>
    <div class="orange-glow orange-glow-2"></div>
    <div class="orange-glow orange-glow-3"></div>
    <div id="auth-seasonal-particles" class="auth-seasonal-particles" aria-hidden="true"></div>
    <div class="christmas-tree-wrap" aria-hidden="true">
        <svg viewBox="0 0 220 320" width="100%" height="100%" role="presentation" aria-hidden="true">
            <defs>
                <linearGradient id="agTreeGreen" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#22c55e" stop-opacity="0.98"></stop>
                    <stop offset="100%" stop-color="#15803d" stop-opacity="0.98"></stop>
                </linearGradient>
                <linearGradient id="agTreeGold" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#fbbf24" stop-opacity="0.98"></stop>
                    <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.98"></stop>
                </linearGradient>
                <filter id="agTreeGlow" x="-40%" y="-40%" width="180%" height="180%">
                    <feGaussianBlur stdDeviation="6" result="blur"></feGaussianBlur>
                    <feColorMatrix in="blur" type="matrix" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 0.35 0" result="glow"></feColorMatrix>
                    <feMerge>
                        <feMergeNode in="glow"></feMergeNode>
                        <feMergeNode in="SourceGraphic"></feMergeNode>
                    </feMerge>
                </filter>
            </defs>
            <g filter="url(#agTreeGlow)">
                <path d="M110 10 L126 46 L165 46 L133 70 L146 108 L110 84 L74 108 L87 70 L55 46 L94 46 Z" fill="url(#agTreeGold)"></path>
                <path d="M110 58 L32 152 H188 Z" fill="url(#agTreeGreen)"></path>
                <path d="M110 118 L22 230 H198 Z" fill="url(#agTreeGreen)" opacity="0.98"></path>
                <path d="M110 178 L38 280 H182 Z" fill="url(#agTreeGreen)" opacity="0.98"></path>
                <rect x="92" y="260" width="36" height="46" rx="8" fill="#7f1d1d" opacity="0.95"></rect>
                <rect x="92" y="260" width="36" height="12" rx="6" fill="#fbbf24" opacity="0.65"></rect>
                <circle cx="78" cy="170" r="6" fill="#ef4444" opacity="0.92"></circle>
                <circle cx="146" cy="150" r="6" fill="#fbbf24" opacity="0.92"></circle>
                <circle cx="108" cy="140" r="5.5" fill="#bfdbfe" opacity="0.92"></circle>
                <circle cx="66" cy="224" r="6" fill="#bfdbfe" opacity="0.9"></circle>
                <circle cx="154" cy="232" r="6" fill="#ef4444" opacity="0.9"></circle>
                <circle cx="110" cy="216" r="6" fill="#fbbf24" opacity="0.9"></circle>
                <path d="M54 154 C84 168, 136 168, 166 154" stroke="#fbbf24" stroke-width="5" stroke-linecap="round" opacity="0.6"></path>
                <path d="M46 210 C86 226, 134 226, 174 210" stroke="#bfdbfe" stroke-width="5" stroke-linecap="round" opacity="0.52"></path>
                <path d="M62 250 C92 264, 128 264, 158 250" stroke="#ef4444" stroke-width="5" stroke-linecap="round" opacity="0.52"></path>
            </g>
        </svg>
    </div>
    <div class="seasonal-login-deco newyear-deco-wrap" aria-hidden="true">
        <svg viewBox="0 0 360 260" width="100%" height="100%" role="presentation" aria-hidden="true">
            <defs>
                <linearGradient id="agNyGlow" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#a78bfa" stop-opacity="0.92"></stop>
                    <stop offset="100%" stop-color="#38bdf8" stop-opacity="0.78"></stop>
                </linearGradient>
                <linearGradient id="agNyGold" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#fbbf24" stop-opacity="0.95"></stop>
                    <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.9"></stop>
                </linearGradient>
            </defs>
            <g opacity="0.95">
                <circle cx="262" cy="84" r="46" fill="none" stroke="url(#agNyGlow)" stroke-width="10"></circle>
                <path d="M262 22 L262 8" stroke="url(#agNyGold)" stroke-width="6" stroke-linecap="round"></path>
                <path d="M262 160 L262 176" stroke="url(#agNyGold)" stroke-width="6" stroke-linecap="round"></path>
                <path d="M200 84 L184 84" stroke="url(#agNyGold)" stroke-width="6" stroke-linecap="round"></path>
                <path d="M340 84 L356 84" stroke="url(#agNyGold)" stroke-width="6" stroke-linecap="round"></path>
                <path d="M220 42 L208 30" stroke="url(#agNyGold)" stroke-width="6" stroke-linecap="round"></path>
                <path d="M304 126 L316 138" stroke="url(#agNyGold)" stroke-width="6" stroke-linecap="round"></path>
                <path d="M304 42 L316 30" stroke="url(#agNyGold)" stroke-width="6" stroke-linecap="round"></path>
                <path d="M220 126 L208 138" stroke="url(#agNyGold)" stroke-width="6" stroke-linecap="round"></path>
                <path d="M30 210 C96 172, 156 172, 222 210" stroke="url(#agNyGlow)" stroke-width="14" stroke-linecap="round" opacity="0.85"></path>
                <circle cx="90" cy="196" r="6" fill="#fbbf24" opacity="0.95"></circle>
                <circle cx="136" cy="182" r="6" fill="#38bdf8" opacity="0.95"></circle>
                <circle cx="178" cy="190" r="6" fill="#a78bfa" opacity="0.95"></circle>
            </g>
        </svg>
    </div>
    <div class="seasonal-login-deco easter-deco-wrap" aria-hidden="true">
        <svg viewBox="0 0 340 260" width="100%" height="100%" role="presentation" aria-hidden="true">
            <defs>
                <linearGradient id="agEaEgg" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#c084fc" stop-opacity="0.92"></stop>
                    <stop offset="100%" stop-color="#60a5fa" stop-opacity="0.86"></stop>
                </linearGradient>
                <linearGradient id="agEaStripe" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#f472b6" stop-opacity="0.9"></stop>
                    <stop offset="100%" stop-color="#fbbf24" stop-opacity="0.88"></stop>
                </linearGradient>
            </defs>
            <g opacity="0.96">
                <ellipse cx="136" cy="156" rx="78" ry="96" fill="url(#agEaEgg)"></ellipse>
                <path d="M72 138 C104 154, 168 154, 200 138" stroke="url(#agEaStripe)" stroke-width="16" stroke-linecap="round" opacity="0.78"></path>
                <path d="M78 176 C108 190, 164 190, 194 176" stroke="#22c55e" stroke-width="14" stroke-linecap="round" opacity="0.62"></path>
                <circle cx="110" cy="122" r="8" fill="#fbbf24" opacity="0.9"></circle>
                <circle cx="160" cy="122" r="8" fill="#f472b6" opacity="0.9"></circle>
                <path d="M242 86 C256 44, 284 44, 298 86" fill="#f8fafc" opacity="0.9"></path>
                <path d="M248 92 C260 58, 280 58, 292 92" fill="#f8fafc" opacity="0.9"></path>
                <circle cx="270" cy="114" r="26" fill="#f8fafc" opacity="0.92"></circle>
                <circle cx="260" cy="112" r="3" fill="#0f172a" opacity="0.8"></circle>
                <circle cx="280" cy="112" r="3" fill="#0f172a" opacity="0.8"></circle>
                <path d="M264 124 C268 130, 272 130, 276 124" stroke="#0f172a" stroke-width="3" stroke-linecap="round" opacity="0.5"></path>
            </g>
        </svg>
    </div>
    <div class="seasonal-login-deco mothersday-deco-wrap" aria-hidden="true">
        <svg viewBox="0 0 340 260" width="100%" height="100%" role="presentation" aria-hidden="true">
            <defs>
                <linearGradient id="agMoPink" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#f472b6" stop-opacity="0.92"></stop>
                    <stop offset="100%" stop-color="#fb7185" stop-opacity="0.86"></stop>
                </linearGradient>
                <linearGradient id="agMoLeaf" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#22c55e" stop-opacity="0.9"></stop>
                    <stop offset="100%" stop-color="#16a34a" stop-opacity="0.82"></stop>
                </linearGradient>
            </defs>
            <g opacity="0.96">
                <path d="M182 78 C182 52, 206 36, 230 44 C252 52, 258 80, 240 100 L212 128 L184 100 C174 92, 182 86, 182 78 Z" fill="url(#agMoPink)" opacity="0.9"></path>
                <path d="M158 88 C158 62, 132 46, 108 54 C86 62, 80 90, 98 110 L126 138 L154 110 C164 102, 158 96, 158 88 Z" fill="url(#agMoPink)" opacity="0.78"></path>
                <circle cx="170" cy="170" r="46" fill="rgba(255,255,255,0.08)"></circle>
                <path d="M138 204 C150 178, 162 156, 170 132" stroke="url(#agMoLeaf)" stroke-width="8" stroke-linecap="round" opacity="0.8"></path>
                <path d="M198 206 C188 176, 178 154, 170 132" stroke="url(#agMoLeaf)" stroke-width="8" stroke-linecap="round" opacity="0.8"></path>
                <path d="M132 168 C122 158, 114 146, 112 132 C128 134, 144 144, 152 154" fill="url(#agMoLeaf)" opacity="0.75"></path>
                <path d="M208 168 C218 158, 226 146, 228 132 C212 134, 196 144, 188 154" fill="url(#agMoLeaf)" opacity="0.75"></path>
            </g>
        </svg>
    </div>
    <div class="seasonal-login-deco fathersday-deco-wrap" aria-hidden="true">
        <svg viewBox="0 0 320 260" width="100%" height="100%" role="presentation" aria-hidden="true">
            <defs>
                <linearGradient id="agFaBlue" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#60a5fa" stop-opacity="0.92"></stop>
                    <stop offset="100%" stop-color="#1d4ed8" stop-opacity="0.86"></stop>
                </linearGradient>
                <linearGradient id="agFaGold" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#fbbf24" stop-opacity="0.92"></stop>
                    <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.86"></stop>
                </linearGradient>
            </defs>
            <g opacity="0.96">
                <path d="M164 44 L208 88 L182 116 L164 98 L146 116 L120 88 Z" fill="url(#agFaBlue)"></path>
                <path d="M164 96 L196 128 L164 238 L132 128 Z" fill="url(#agFaBlue)" opacity="0.9"></path>
                <path d="M136 152 L164 182 L192 152" stroke="url(#agFaGold)" stroke-width="8" stroke-linecap="round" opacity="0.8"></path>
                <path d="M74 206 C102 184, 128 178, 152 194 C156 196, 160 198, 164 198 C168 198, 172 196, 176 194 C200 178, 226 184, 254 206" stroke="rgba(255,255,255,0.82)" stroke-width="14" stroke-linecap="round" opacity="0.75"></path>
                <path d="M88 206 C110 192, 130 190, 150 200" stroke="rgba(15,23,42,0.38)" stroke-width="4" stroke-linecap="round" opacity="0.6"></path>
                <path d="M240 206 C218 192, 198 190, 178 200" stroke="rgba(15,23,42,0.38)" stroke-width="4" stroke-linecap="round" opacity="0.6"></path>
            </g>
        </svg>
    </div>
    <div class="seasonal-login-deco foundersday-deco-wrap" aria-hidden="true">
        <svg viewBox="0 0 340 260" width="100%" height="100%" role="presentation" aria-hidden="true">
            <defs>
                <linearGradient id="agFoGold" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#fbbf24" stop-opacity="0.95"></stop>
                    <stop offset="100%" stop-color="#f59e0b" stop-opacity="0.88"></stop>
                </linearGradient>
                <linearGradient id="agFoRed" x1="0" y1="0" x2="1" y2="1">
                    <stop offset="0%" stop-color="#fb7185" stop-opacity="0.9"></stop>
                    <stop offset="100%" stop-color="#ef4444" stop-opacity="0.82"></stop>
                </linearGradient>
                <linearGradient id="agFoGreen" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#22c55e" stop-opacity="0.88"></stop>
                    <stop offset="100%" stop-color="#16a34a" stop-opacity="0.8"></stop>
                </linearGradient>
            </defs>
            <g opacity="0.96">
                <path d="M144 56 H196 V96 C196 126, 174 148, 170 152 C166 148, 144 126, 144 96 Z" fill="url(#agFoGold)"></path>
                <path d="M112 64 H142 V92 C142 116, 126 132, 120 136 C114 132, 112 116, 112 92 Z" fill="url(#agFoGold)" opacity="0.8"></path>
                <path d="M198 64 H228 V92 C228 116, 212 132, 206 136 C200 132, 198 116, 198 92 Z" fill="url(#agFoGold)" opacity="0.8"></path>
                <rect x="158" y="150" width="24" height="44" rx="10" fill="url(#agFoGold)" opacity="0.88"></rect>
                <path d="M124 194 H216 C214 214, 198 230, 170 230 C142 230, 126 214, 124 194 Z" fill="url(#agFoRed)" opacity="0.88"></path>
                <path d="M80 186 C96 168, 116 158, 136 156" stroke="url(#agFoGreen)" stroke-width="10" stroke-linecap="round" opacity="0.78"></path>
                <path d="M260 186 C244 168, 224 158, 204 156" stroke="url(#agFoGreen)" stroke-width="10" stroke-linecap="round" opacity="0.78"></path>
                <circle cx="170" cy="40" r="10" fill="url(#agFoGold)" opacity="0.9"></circle>
            </g>
        </svg>
    </div>
    <div class="page-shell">
        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1.08fr)_minmax(420px,520px)] lg:gap-8">
            <section class="brand-panel rounded-[2.4rem] p-6 sm:p-8 lg:p-10">
                <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
                    <div class="inline-flex items-center gap-4">
                        <div class="inline-flex items-center justify-center w-16 h-16 sm:w-20 sm:h-20 bg-white/10 backdrop-blur-md rounded-[1.6rem] border border-white/20 shadow-xl overflow-hidden shrink-0">
                            <?php if ($logoRelativePath): ?>
                                <img src="<?php echo BASE_URL . '/' . $logoRelativePath; ?>" alt="Logo" class="w-full h-full object-contain bg-transparent p-1.5">
                            <?php else: ?>
                                <i class="fas fa-church text-2xl login-accent-text"></i>
                            <?php endif; ?>
                        </div>
                        <div>
                            <p class="text-[10px] sm:text-[11px] font-black uppercase tracking-[0.34em] login-accent-text-soft">Secure Church Portal</p>
                            <h1 class="mt-3 text-2xl sm:text-3xl lg:text-[2.5rem] font-black text-white tracking-tight uppercase leading-tight"><?php echo $churchName; ?></h1>
                        </div>
                    </div>
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 w-full sm:w-auto">
                        <div class="relative w-full sm:w-auto">
                            <select id="login-theme-switch" class="w-full sm:w-auto h-11 sm:h-10 rounded-2xl bg-white/5 border border-white/10 px-4 pr-9 text-[10px] font-black uppercase tracking-[0.24em] text-slate-200 outline-none appearance-none cursor-pointer">
                                <option value="dark">Dark</option>
                                <option value="light">Light</option>
                                <option value="ocean">Ocean</option>
                                <option value="sunset">Sunset</option>
                            </select>
                            <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-slate-500 text-[10px] pointer-events-none"></i>
                        </div>
                        <span class="inline-flex items-center justify-center rounded-full border border-white/10 bg-white/5 px-3 py-2 text-[10px] font-black uppercase tracking-[0.28em] text-slate-300 w-full sm:w-auto">
                            Upper Room
                        </span>
                    </div>
                </div>

                <div class="mt-8 sm:mt-10 max-w-xl">
                    <p class="text-sm sm:text-base text-slate-300 font-semibold leading-7">
                        A polished access point for administrators, department heads, and staff with responsive glassmorphism panels, secure sign-in, and a layout that stays clean on every screen.
                    </p>
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-3">
                    <div class="stat-chip rounded-[1.5rem] p-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.26em] text-slate-500">Portal Mode</p>
                        <p class="mt-2 text-sm font-bold text-white">Role-based access</p>
                    </div>
                    <div class="stat-chip rounded-[1.5rem] p-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.26em] text-slate-500">Experience</p>
                        <p class="mt-2 text-sm font-bold text-white">Mobile to desktop</p>
                    </div>
                    <div class="stat-chip rounded-[1.5rem] p-4">
                        <p class="text-[10px] font-black uppercase tracking-[0.26em] text-slate-500">Security</p>
                        <p class="mt-2 text-sm font-bold text-white">Protected sessions</p>
                    </div>
                </div>

                <div class="mt-8 grid gap-3 sm:grid-cols-2">
                    <div class="feature-tile rounded-[1.7rem] p-5">
                        <div class="w-11 h-11 rounded-2xl login-accent-bg-soft login-accent-border login-accent-text flex items-center justify-center border">
                            <i class="fas fa-shield-halved"></i>
                        </div>
                        <h3 class="mt-4 text-sm font-black text-white uppercase tracking-[0.18em]">Trusted Access</h3>
                        <p class="mt-2 text-sm text-slate-400 leading-6">Separate sign-in levels for admin, department head, and staff with a cleaner guided form layout.</p>
                    </div>
                    <div class="feature-tile rounded-[1.7rem] p-5">
                        <div class="w-11 h-11 rounded-2xl bg-blue-500/10 border border-blue-400/20 text-blue-300 flex items-center justify-center">
                            <i class="fas fa-mobile-screen-button"></i>
                        </div>
                        <h3 class="mt-4 text-sm font-black text-white uppercase tracking-[0.18em]">Responsive UI</h3>
                        <p class="mt-2 text-sm text-slate-400 leading-6">Balanced spacing, wider desktop presentation, and stacked mobile sections for a better first impression.</p>
                    </div>
                </div>
            </section>

            <div class="login-shell">
                <div class="login-card rounded-[2.4rem] overflow-hidden border border-white/5">
                    <div class="relative p-6 sm:p-8 lg:p-10">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between mb-8 sm:mb-10">
                            <div>
                                <h2 class="text-3xl sm:text-[2.2rem] font-black text-white tracking-tighter leading-tight">
                                    <?php echo $mode === 'forgot' ? 'Forgot Password' : ($mode === 'reset' ? 'Reset Password' : 'Sign In'); ?>
                                </h2>
                                <p class="text-slate-400 text-[11px] font-bold uppercase tracking-[0.24em] mt-3">
                                    <?php echo $mode === 'forgot' ? 'Recover account access' : ($mode === 'reset' ? 'Set your new password' : 'Sign in first to continue'); ?>
                                </p>
                            </div>
                            <span class="inline-flex w-fit items-center rounded-full border px-3 py-1.5 text-[10px] font-black uppercase tracking-[0.26em] shadow-lg shadow-yellow-500/10 login-accent-border login-accent-bg-soft login-accent-text">
                                <i class="fas fa-shield-halved mr-2"></i> Secure
                            </span>
                        </div>

                        <?php if ($success = Session::flash('success')): ?>
                            <div class="bg-emerald-500/10 border border-emerald-500/20 p-4 mb-8 rounded-2xl flex items-start space-x-3">
                                <i class="fas fa-circle-check text-emerald-400 mt-1"></i>
                                <p class="text-xs text-emerald-200 font-bold"><?php echo $success; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($error = Session::flash('error')): ?>
                            <div class="bg-red-500/10 border border-red-500/20 p-4 mb-8 rounded-2xl flex items-start space-x-3">
                                <i class="fas fa-circle-exclamation text-red-400 mt-1"></i>
                                <p class="text-xs text-red-200 font-bold"><?php echo $error; ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($mode === 'forgot'): ?>
                            <form action="<?php echo BASE_URL; ?>/forgot-password" method="POST" class="space-y-6 relative z-10">
                                <div class="glass-panel rounded-[2rem] p-5 sm:p-6 space-y-6">
                                    <div class="rounded-2xl bg-white/[0.03] border border-white/10 px-4 py-3">
                                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Account Recovery</p>
                                        <p class="text-xs font-bold text-slate-300 mt-1">Enter your username or email to generate a password reset link.</p>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="login" class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Username or Email</label>
                                        <div class="relative group">
                                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                                <i class="fas fa-user text-slate-600 focus-accent transition-colors"></i>
                                            </div>
                                            <input type="text" id="login" name="login" required
                                                class="block w-full pl-12 pr-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm font-bold placeholder-slate-600 focus:bg-white/10 focus:outline-none transition-all input-focus input-accent"
                                                placeholder="username or email">
                                        </div>
                                        <p class="text-[10px] font-bold text-slate-400 mt-3">A reset link will be generated on this page.</p>
                                    </div>
                                </div>

                                <?php if ($resetLink = Session::flash('reset_link')): ?>
                                    <div class="bg-white/5 border border-white/10 p-4 rounded-2xl">
                                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Reset Link</p>
                                        <div class="flex flex-col gap-2 sm:flex-row">
                                            <input id="reset_link" type="text" readonly value="<?php echo htmlspecialchars($resetLink); ?>"
                                                class="flex-1 bg-slate-950/30 border border-white/10 rounded-xl px-4 py-3 text-[11px] font-bold text-slate-200 outline-none">
                                            <button type="button" onclick="copyResetLink()" class="px-4 py-3 rounded-xl login-accent-bg text-slate-900 text-[10px] font-black uppercase tracking-widest">Copy</button>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($resetLink); ?>" class="inline-flex items-center text-[10px] font-black uppercase tracking-widest login-accent-text mt-4 hover:underline">
                                            Open reset page <i class="fas fa-arrow-right ml-2"></i>
                                        </a>
                                    </div>
                                <?php endif; ?>

                                <button type="submit"
                                    class="w-full btn-gradient text-slate-900 py-4.5 px-6 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl shadow-yellow-500/10">
                                    Generate Reset Link
                                </button>

                                <div class="text-center pt-2">
                                    <a href="<?php echo BASE_URL; ?>/login" class="text-[10px] login-accent-text hover:text-white font-black uppercase tracking-widest transition-colors">Back to login</a>
                                </div>
                            </form>
                        <?php elseif ($mode === 'reset'): ?>
                            <form action="<?php echo BASE_URL; ?>/reset-password" method="POST" class="space-y-6 relative z-10">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                                <div class="glass-panel rounded-[2rem] p-5 sm:p-6 space-y-6">
                                    <div class="rounded-2xl bg-white/[0.03] border border-white/10 px-4 py-3">
                                        <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">New Password</p>
                                        <p class="text-xs font-bold text-slate-300 mt-1">Create a strong password and confirm it to complete account recovery.</p>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="password" class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">New Password</label>
                                        <div class="relative group">
                                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                                <i class="fas fa-lock text-slate-600 focus-accent transition-colors"></i>
                                            </div>
                                            <input type="password" id="password" name="password" required
                                                class="block w-full pl-12 pr-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm font-bold placeholder-slate-600 focus:bg-white/10 focus:outline-none transition-all input-focus input-accent"
                                                placeholder="••••••••">
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="confirm_password" class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Confirm Password</label>
                                        <div class="relative group">
                                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                                <i class="fas fa-lock text-slate-600 focus-accent transition-colors"></i>
                                            </div>
                                            <input type="password" id="confirm_password" name="confirm_password" required
                                                class="block w-full pl-12 pr-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm font-bold placeholder-slate-600 focus:bg-white/10 focus:outline-none transition-all input-focus input-accent"
                                                placeholder="••••••••">
                                        </div>
                                    </div>
                                </div>

                                <button type="submit"
                                    class="w-full btn-gradient text-slate-900 py-4.5 px-6 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl shadow-yellow-500/10">
                                    Update Password
                                </button>

                                <div class="text-center pt-2 flex flex-col items-center justify-center gap-3 sm:flex-row sm:gap-4">
                                    <a href="<?php echo BASE_URL; ?>/forgot-password" class="text-[10px] login-accent-text hover:text-white font-black uppercase tracking-widest transition-colors">New reset link</a>
                                    <span class="hidden sm:inline text-slate-600">|</span>
                                    <a href="<?php echo BASE_URL; ?>/login" class="text-[10px] login-accent-text hover:text-white font-black uppercase tracking-widest transition-colors">Back to login</a>
                                </div>
                            </form>
                        <?php else: ?>
                            <form action="<?php echo BASE_URL; ?>/login" method="POST" class="space-y-6 relative z-10">
                                <div class="glass-panel rounded-[2rem] p-5 sm:p-6 space-y-6">
                                    <div class="flex items-center justify-between gap-4 rounded-2xl bg-white/[0.03] border border-white/10 px-4 py-3">
                                        <div>
                                            <p class="text-[10px] font-black uppercase tracking-[0.22em] text-slate-500">Login Access</p>
                                            <p class="text-xs font-bold text-slate-300 mt-1">Choose your account level and continue securely.</p>
                                        </div>
                                        <div class="w-11 h-11 rounded-2xl bg-blue-500/10 border border-blue-400/20 text-blue-300 flex items-center justify-center shrink-0">
                                            <i class="fas fa-user-shield"></i>
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <label for="login" class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Username</label>
                                        <div class="relative group">
                                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                                <i class="fas fa-user text-slate-600 focus-accent transition-colors"></i>
                                            </div>
                                            <input type="text" id="login" name="login" required
                                                class="block w-full pl-12 pr-6 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm font-bold placeholder-slate-600 focus:bg-white/10 focus:outline-none transition-all input-focus input-accent"
                                                placeholder="username or email">
                                        </div>
                                    </div>

                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between ml-1 gap-3">
                                            <label for="password" class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">Password</label>
                                            <a href="<?php echo BASE_URL; ?>/forgot-password" class="text-[10px] login-accent-text hover:text-white font-black uppercase tracking-widest transition-colors">Forgot?</a>
                                        </div>
                                        <div class="relative group">
                                            <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                                <i class="fas fa-lock text-slate-600 focus-accent transition-colors"></i>
                                            </div>
                                            <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-5 flex items-center text-slate-500 login-accent-hover transition-colors">
                                                <i class="fas fa-eye text-[14px]"></i>
                                            </button>
                                            <input type="password" id="password" name="password" required
                                                class="block w-full pl-12 pr-14 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm font-bold placeholder-slate-600 focus:bg-white/10 focus:outline-none transition-all input-focus input-accent"
                                                placeholder="••••••••">
                                        </div>
                                    </div>

                                    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
                                        <div class="space-y-2">
                                            <label for="login_type" class="block text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] ml-1">Permission Level</label>
                                            <div class="relative group">
                                                <div class="absolute inset-y-0 left-0 pl-5 flex items-center pointer-events-none">
                                                    <i class="fas fa-id-badge text-slate-600 focus-accent transition-colors"></i>
                                                </div>
                                                <select id="login_type" name="login_type" class="block w-full pl-12 pr-10 py-4 bg-white/5 border border-white/10 rounded-2xl text-white text-sm font-bold focus:bg-white/10 focus:outline-none transition-all input-focus input-accent appearance-none">
                                                    <option value="admin">Admin</option>
                                                    <option value="finance_head">Head Of Finance</option>
                                                    <option value="finance_staff">Finance Staff</option>
                                                    <option value="auditor">Auditor</option>
                                                    <option value="dept_head">Department Head</option>
                                                    <option value="visitation_team">Visitation Member</option>
                                                    <option value="pastor">Pastor</option>
                                                </select>
                                                <div class="absolute inset-y-0 right-0 pr-5 flex items-center pointer-events-none">
                                                    <i class="fas fa-chevron-down text-slate-600 text-[10px]"></i>
                                                </div>
                                            </div>
                                        </div>

                                        <label class="relative flex items-center cursor-pointer group min-h-[56px] rounded-2xl border border-white/10 bg-white/[0.03] px-4">
                                            <input type="checkbox" id="remember" name="remember" class="sr-only peer">
                                            <div class="remember-track relative w-10 h-5 rounded-full bg-white/10 transition-colors after:content-[''] after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-slate-300 after:transition-all after:duration-200 peer-checked:after:translate-x-5 peer-checked:after:bg-slate-900"></div>
                                            <span class="ml-3 text-[10px] font-black text-slate-400 uppercase tracking-widest group-hover:text-slate-200 transition-colors">Save credentials</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="glass-panel rounded-[2rem] p-4 sm:p-5 flex items-center justify-between gap-4">
                                    <div>
                                        <p class="text-[10px] font-black uppercase tracking-[0.24em] text-slate-500">Ready To Login</p>
                                        <p class="text-xs font-bold text-slate-300 mt-2">Enter your details and continue to your dashboard.</p>
                                    </div>
                                    <div class="w-12 h-12 rounded-2xl border flex items-center justify-center shrink-0 login-accent-bg-soft login-accent-border login-accent-text">
                                        <i class="fas fa-arrow-right-to-bracket"></i>
                                    </div>
                                </div>

                                <button type="submit"
                                    class="w-full btn-gradient text-slate-900 py-4.5 px-6 rounded-2xl font-black text-xs uppercase tracking-[0.2em] hover:scale-[1.02] active:scale-[0.98] transition-all shadow-xl shadow-yellow-500/10">
                                    Sign In
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <button
        type="button"
        id="auth-scroll-top-button"
        class="fixed bottom-5 right-4 sm:bottom-6 sm:right-6 inline-flex h-12 w-12 items-center justify-center rounded-2xl border login-accent-border login-accent-bg text-slate-900 shadow-xl shadow-yellow-500/20 opacity-0 pointer-events-none translate-y-4 transition-all duration-200 hover:scale-105"
        aria-label="Scroll to top"
    >
        <i class="fas fa-arrow-up text-sm"></i>
    </button>

    <script>
        function copyResetLink() {
            const el = document.getElementById('reset_link');
            if (!el) return;
            el.select();
            el.setSelectionRange(0, el.value.length);
            try {
                document.execCommand('copy');
            } catch (e) {}
        }

        (function () {
            const select = document.getElementById('login-theme-switch');
            if (!select) return;
            const current = (function () {
                try { return localStorage.getItem('uiTheme') || ''; } catch (e) { return ''; }
            })();
            const allowed = ['dark', 'light', 'ocean', 'sunset'];
            if (allowed.includes(current)) select.value = current;
            else select.value = document.documentElement.getAttribute('data-theme') || 'dark';
            select.addEventListener('change', function () {
                const next = select.value;
                if (!allowed.includes(next)) return;
                try { localStorage.setItem('uiTheme', next); } catch (e) {}
                document.documentElement.setAttribute('data-theme', next);
            });
        })();

        (function () {
            const container = document.getElementById('auth-seasonal-particles');
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
                el.className = 'auth-seasonal-particle ' + kind;
                el.style.left = leftVw + 'vw';
                el.style.width = sizePx + 'px';
                el.style.height = sizePx + 'px';
                if (kind === 'confetti') el.style.height = Math.max(10, Math.round(sizePx * 2.2)) + 'px';
                el.style.setProperty('--p-drift', String(driftPx));
                el.style.setProperty('--p-o', String(opacity));
                el.style.setProperty('--p-r0', r0 + 'deg');
                el.style.setProperty('--p-r1', r1 + 'deg');
                el.style.animation = 'authSeasonalFall ' + durationS + 's linear ' + delayS + 's infinite';
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
                    const snowCount = Math.round(34 * base);
                    const starCount = Math.round(12 * base);
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
                    const confettiCount = Math.round(26 * base);
                    const sparkleCount = Math.round(16 * base);
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

                const sparkleCount = Math.round(30 * base);
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
                window.clearTimeout(window.__agAuthSeasonalResizeTimer);
                window.__agAuthSeasonalResizeTimer = window.setTimeout(sync, 180);
            });
        })();

        (function () {
            const loader = document.getElementById('auth-page-loader');
            const loaderText = document.getElementById('auth-page-loader-text');
            const loaderSubtext = document.getElementById('auth-page-loader-subtext');

            const showLoader = function (title, description) {
                if (!loader) return;
                if (loaderText && title) loaderText.textContent = title;
                if (loaderSubtext && description) loaderSubtext.textContent = description;
                loader.classList.remove('is-hidden');
            };

            const hideLoader = function () {
                if (!loader) return;
                loader.classList.add('is-hidden');
            };

            const shouldShowForLink = function (link) {
                if (!link) return false;
                const href = (link.getAttribute('href') || '').trim();
                if (!href || href === '#' || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return false;
                if (href.startsWith('#') || link.hasAttribute('download') || (link.target && link.target !== '_self')) return false;
                return true;
            };

            window.addEventListener('load', function () {
                window.setTimeout(hideLoader, 120);
            });

            window.addEventListener('pageshow', hideLoader);

            document.addEventListener('click', function (event) {
                const link = event.target.closest('a');
                if (!shouldShowForLink(link)) return;
                showLoader('Loading Page', 'Please wait while the next page opens.');
            });

            document.addEventListener('submit', function (event) {
                if (!(event.target instanceof HTMLFormElement)) return;
                showLoader('Loading', 'Processing your request...');
            });
        })();

        (function () {
            const loginType = document.getElementById('login_type');
            const login = document.getElementById('login');
            const remember = document.getElementById('remember');
            if (!loginType || !login || !remember) return;

            const storageKey = 'ag_login_saved';
            try {
                const saved = JSON.parse(localStorage.getItem(storageKey) || 'null');
                if (saved && typeof saved === 'object') {
                    if (saved.login_type) loginType.value = saved.login_type;
                    if (saved.login) login.value = saved.login;
                    remember.checked = true;
                }
            } catch (e) {}

            const save = () => {
                if (!remember.checked) {
                    localStorage.removeItem(storageKey);
                    return;
                }
                localStorage.setItem(storageKey, JSON.stringify({
                    login_type: loginType.value || '',
                    login: login.value || ''
                }));
            };

            remember.addEventListener('change', save);
            loginType.addEventListener('change', save);
            login.addEventListener('input', save);
        })();

        (function () {
            const scrollTopButton = document.getElementById('auth-scroll-top-button');
            if (!scrollTopButton) return;

            const syncScrollTopButton = function () {
                const shouldShow = window.scrollY > 220;
                scrollTopButton.classList.toggle('opacity-0', !shouldShow);
                scrollTopButton.classList.toggle('pointer-events-none', !shouldShow);
                scrollTopButton.classList.toggle('translate-y-4', !shouldShow);
            };

            window.addEventListener('scroll', syncScrollTopButton, { passive: true });
            scrollTopButton.addEventListener('click', function () {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
            syncScrollTopButton();
        })();

        (function () {
            const decorate = function (input) {
                if (!input || input.dataset.pwToggle === '1') return;
                const parent = input.closest('.relative') || input.parentElement;
                if (!parent) return;
                const existing = parent.querySelector('#toggle-password') || parent.querySelector('.ag-password-toggle');
                if (existing) {
                    const btn = existing;
                    const icon = btn.querySelector ? btn.querySelector('i') : null;
                    const sync = () => {
                        const isHidden = input.type === 'password';
                        btn.setAttribute('aria-label', isHidden ? 'Show password' : 'Hide password');
                        if (icon) {
                            icon.classList.toggle('fa-eye', isHidden);
                            icon.classList.toggle('fa-eye-slash', !isHidden);
                        }
                    };
                    if (!btn.dataset.pwBound) {
                        btn.addEventListener('click', function () {
                            input.type = input.type === 'password' ? 'text' : 'password';
                            sync();
                        });
                        btn.dataset.pwBound = '1';
                    }
                    if (!input.className.includes('pr-14')) {
                        input.className = input.className.replace(/\bpr-\d+\b/g, '').trim() + ' pr-14';
                    }
                    sync();
                    input.dataset.pwToggle = '1';
                    return;
                }

                const computed = window.getComputedStyle(parent);
                if (computed && computed.position === 'static') {
                    parent.style.position = 'relative';
                }
                if (!input.className.includes('pr-14')) {
                    input.className = input.className.replace(/\bpr-\d+\b/g, '').trim() + ' pr-14';
                }
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'ag-password-toggle absolute inset-y-0 right-0 pr-5 flex items-center text-slate-500 login-accent-hover transition-colors';
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
</body>
</html>
