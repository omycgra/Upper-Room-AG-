<?php

define('ROOT_PATH', __DIR__);
require_once ROOT_PATH . '/app/Helpers/Env.php';
Env::load(ROOT_PATH . '/.env');
require_once ROOT_PATH . '/app/Helpers/Database.php';
require_once ROOT_PATH . '/app/Helpers/AppConfig.php';
require_once ROOT_PATH . '/app/Helpers/Branding.php';

$dbHost = (string)Env::get('DB_HOST', 'localhost');
$dbDriver = strtolower((string)Env::get('DB_DRIVER', 'mysql'));
if (in_array($dbDriver, ['postgres', 'postgresql'], true)) {
    $dbDriver = 'pgsql';
}
$dbPort = (string)Env::get('DB_PORT', $dbDriver === 'pgsql' ? '5432' : '3306');
$dbName = (string)Env::get('DB_NAME', $dbDriver === 'pgsql' ? 'postgres' : 'church_management');
$dbUser = (string)Env::get('DB_USER', $dbDriver === 'pgsql' ? 'postgres' : 'root');
$dbPass = (string)Env::get('DB_PASS', '');
$dbCharset = (string)Env::get('DB_CHARSET', $dbDriver === 'pgsql' ? 'utf8' : 'utf8mb4');
$dbSchema = (string)Env::get('DB_SCHEMA', $dbDriver === 'pgsql' ? 'public' : $dbName);
$dbSslMode = (string)Env::get('DB_SSLMODE', $dbDriver === 'pgsql' ? 'require' : '');
$appBaseUrl = (string)Env::get('APP_BASE_URL', '/');
$isCli = PHP_SAPI === 'cli';
$forceSetup = isset($_GET['force_setup']) && $_GET['force_setup'] === '1';

if ($isCli) {
    $result = runSetup([
        'church_name' => 'UPPER ROOM ASSEMBLY MAMPONG',
        'admin_username' => 'admin',
        'admin_password' => 'Admin123'
    ]);

    echo "Church Management System - Setup\n";
    echo "================================\n\n";
    foreach ($result['messages'] as $message) {
        echo $message . "\n";
    }
    if ($result['success']) {
        echo "\n================================\n";
        echo "Setup completed successfully!\n";
        echo "You can now access the application at:\n";
        echo rtrim($appBaseUrl, '/') . "/index.php?route=login\n";
        echo "Login with: admin / Admin123\n";
    } else {
        exit(1);
    }
    return;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && !$forceSetup && isSetupComplete()) {
    $loginUrl = rtrim($appBaseUrl, '/') . '/index.php?route=login';
    header('Location: ' . $loginUrl);
    exit;
}

$messages = [];
$errors = [];
$submittedName = trim((string)($_POST['church_name'] ?? ''));
$submittedAdminUsername = trim((string)($_POST['admin_username'] ?? ''));
$submittedAdminPassword = (string)($_POST['admin_password'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($submittedName === '') {
        $errors[] = 'Church name is required.';
    }
    if ($submittedAdminUsername === '') {
        $errors[] = 'Admin username is required.';
    }
    if (trim($submittedAdminPassword) === '') {
        $errors[] = 'Admin password is required.';
    } elseif (strlen($submittedAdminPassword) < 6) {
        $errors[] = 'Admin password must be at least 6 characters.';
    }

    if (empty($errors)) {
        $result = runSetup([
            'church_name' => $submittedName,
            'admin_username' => $submittedAdminUsername,
            'admin_password' => $submittedAdminPassword
        ]);
        $messages = $result['messages'];
        if ($result['success']) {
            $loginUrl = rtrim($appBaseUrl, '/') . '/index.php?route=login';
            renderSetupPage([
                'success' => true,
                'messages' => $messages,
                'errors' => [],
                'loginUrl' => $loginUrl,
                'churchName' => $submittedName,
                'adminUsername' => $submittedAdminUsername
            ]);
            return;
        }
        $errors[] = $result['error'] ?: 'Setup failed.';
    }
}

renderSetupPage([
    'success' => false,
    'messages' => $messages,
    'errors' => $errors,
    'loginUrl' => '',
    'churchName' => $submittedName !== '' ? $submittedName : 'Church Management',
    'adminUsername' => $submittedAdminUsername
]);

function runSetup(array $setupData)
{
    global $dbHost, $dbPort, $dbName, $dbUser, $dbPass, $dbCharset, $dbDriver, $dbSslMode, $appBaseUrl;

    $messages = [];

    if ($dbDriver === 'mysql') {
        try {
            $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};charset={$dbCharset}", $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = ?");
            $stmt->execute([$dbName]);
            $dbExists = $stmt->fetch();

            if (!$dbExists) {
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$dbCharset} COLLATE {$dbCharset}_unicode_ci");
                $messages[] = "Database '{$dbName}' created successfully.";
            } else {
                $messages[] = "Database '{$dbName}' already exists.";
            }

            $pdo = null;
        } catch (PDOException $e) {
            return [
                'success' => false,
                'messages' => $messages,
                'error' => 'Error creating database: ' . $e->getMessage()
            ];
        }
    } else {
        try {
            $dsn = "pgsql:host={$dbHost};port={$dbPort};dbname={$dbName}";
            if ($dbSslMode !== '') {
                $dsn .= ";sslmode={$dbSslMode}";
            }
            $pdo = new PDO($dsn, $dbUser, $dbPass);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo = null;
            $messages[] = "PostgreSQL database connection verified.";
        } catch (PDOException $e) {
            return [
                'success' => false,
                'messages' => $messages,
                'error' => 'Error connecting to PostgreSQL database: ' . $e->getMessage()
            ];
        }
    }

    $migrations = [
        'create_users_table.php',
        'create_departments_table.php',
        'create_clusters_table.php',
        'create_members_table.php',
        'create_visitors_table.php',
        'create_attendance_table.php',
        'create_finances_table.php',
        'create_equipment_table.php',
        'create_settings_table.php',
        'create_audit_logs_table.php'
    ];

    if ($dbDriver === 'pgsql') {
        try {
            $db = Database::getInstance();
            $schemaPath = ROOT_PATH . '/supabase/migrations/church_management_schema.sql';
            if (!file_exists($schemaPath)) {
                return [
                    'success' => false,
                    'messages' => $messages,
                    'error' => 'PostgreSQL schema file not found: ' . $schemaPath
                ];
            }

            $db->rawExec((string)file_get_contents($schemaPath));
            $messages[] = 'PostgreSQL schema verified.';
        } catch (Throwable $e) {
            return [
                'success' => false,
                'messages' => $messages,
                'error' => 'Error applying PostgreSQL schema: ' . $e->getMessage()
            ];
        }
    } else {
        ob_start();
        foreach ($migrations as $migration) {
            $path = __DIR__ . '/database/migrations/' . $migration;
            if (file_exists($path)) {
                include $path;
                $messages[] = "Migration verified: {$migration}";
            } else {
                $messages[] = "Migration file not found: {$migration}";
            }
        }
        ob_end_clean();
    }

    try {
        $db = Database::getInstance();
        ensureDefaultAdmin(
            $db,
            $messages,
            (string)($setupData['admin_username'] ?? ''),
            (string)($setupData['admin_password'] ?? '')
        );
        upsertSetting($db, 'church_name', trim((string)($setupData['church_name'] ?? 'Church Management')));
        $existingTheme = strtolower(trim((string)AppConfig::getSetting('theme', 'dark')));
        $theme = in_array($existingTheme, ['dark', 'light', 'ocean', 'sunset'], true) ? $existingTheme : 'dark';
        upsertSetting($db, 'theme', $theme);

        AppConfig::reset();
    } catch (Throwable $e) {
        return [
            'success' => false,
            'messages' => $messages,
            'error' => 'Error saving setup: ' . $e->getMessage()
        ];
    }

    $shortcutResult = refreshDesktopShortcut('Church Management System');
    if ($shortcutResult['message'] !== '') {
        $messages[] = $shortcutResult['message'];
    }

    $messages[] = 'Setup saved.';
    $messages[] = 'Application URL: ' . rtrim($appBaseUrl, '/') . '/index.php?route=login';

    return [
        'success' => true,
        'messages' => $messages,
        'error' => null
    ];
}

function upsertSetting($db, $key, $value)
{
    $exists = $db->fetch("SELECT id FROM settings WHERE key_name = ?", [$key]);
    if ($exists) {
        $db->query("UPDATE settings SET value = ? WHERE key_name = ?", [$value, $key]);
    } else {
        $db->query("INSERT INTO settings (key_name, value) VALUES (?, ?)", [$key, $value]);
    }
}

function refreshDesktopShortcut(string $appName): array
{
    if (PHP_OS_FAMILY !== 'Windows') {
        return ['success' => false, 'message' => ''];
    }

    $scriptPath = ROOT_PATH . '/create-local-shortcut.ps1';
    if (!file_exists($scriptPath)) {
        return ['success' => false, 'message' => 'Desktop shortcut refresh skipped: shortcut script not found.'];
    }

    if (!function_exists('exec')) {
        return ['success' => false, 'message' => 'Desktop shortcut refresh skipped: exec is not available.'];
    }

    $powerShellExe = getenv('SystemRoot')
        ? rtrim((string)getenv('SystemRoot'), '\\/') . '\\System32\\WindowsPowerShell\\v1.0\\powershell.exe'
        : 'powershell.exe';

    $command = '"' . $powerShellExe . '" -NoProfile -ExecutionPolicy Bypass -File '
        . escapeshellarg($scriptPath)
        . ' -AppDir ' . escapeshellarg(ROOT_PATH)
        . ' -AppName ' . escapeshellarg($appName)
        . ' 2>&1';

    $output = [];
    $exitCode = 1;
    exec($command, $output, $exitCode);

    if ($exitCode === 0) {
        return ['success' => true, 'message' => 'Desktop shortcut refreshed automatically.'];
    }

    $detail = trim(implode(' ', $output));
    if ($detail !== '') {
        $detail = ' ' . $detail;
    }

    return ['success' => false, 'message' => 'Desktop shortcut refresh could not be completed automatically.' . $detail];
}

function ensureDefaultAdmin($db, array &$messages, string $adminUsername, string $adminPassword): void
{
    $countRow = $db->fetch("SELECT COUNT(*) AS c FROM users");
    if ((int)($countRow['c'] ?? 0) > 0) {
        return;
    }

    $adminUsername = trim($adminUsername);
    if ($adminUsername === '') {
        $adminUsername = 'admin';
    }
    if (trim($adminPassword) === '') {
        $adminPassword = 'Admin123';
    }
    $password = password_hash($adminPassword, PASSWORD_DEFAULT);
    $email = (strpos($adminUsername, '@') !== false) ? $adminUsername : ($adminUsername . '@admin.local');
    $db->query(
        "INSERT INTO users (name, email, username, password, role)
         VALUES (?, ?, ?, ?, ?)",
        ['System Admin', $email, $adminUsername, $password, 'admin']
    );

    $defaults = [
        ['finance_currency', 'GHS', 'Default finance currency'],
        ['sms_provider', 'infobip', 'Primary SMS gateway provider'],
        ['sms_sender_id', 'UPPERROOM', 'Default SMS sender identifier']
    ];

    foreach ($defaults as $row) {
        $exists = $db->fetch("SELECT id FROM settings WHERE key_name = ?", [$row[0]]);
        if (!$exists) {
            $db->query(
                "INSERT INTO settings (key_name, value, description) VALUES (?, ?, ?)",
                $row
            );
        }
    }

    $messages[] = 'Default admin user verified.';
}

function renderSetupPage(array $data): void
{
    global $appBaseUrl;
    $success = (bool)($data['success'] ?? false);
    $messages = $data['messages'] ?? [];
    $errors = $data['errors'] ?? [];
    $loginUrl = (string)($data['loginUrl'] ?? '');
    $churchName = (string)($data['churchName'] ?? 'Church Management');
    $adminUsername = (string)($data['adminUsername'] ?? '');
    $theme = 'dark';
    ?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo htmlspecialchars($theme); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Church Setup Installer</title>
    <?php $faviconLogoPath = Branding::getLogoPath(); ?>
    <?php if ($faviconLogoPath): ?>
        <link rel="icon" type="image/png" href="<?php echo htmlspecialchars(rtrim($appBaseUrl, '/') . '/' . ltrim($faviconLogoPath, '/')); ?>">
        <link rel="shortcut icon" href="<?php echo htmlspecialchars(rtrim($appBaseUrl, '/') . '/' . ltrim($faviconLogoPath, '/')); ?>">
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
    <style>
        body { font-family: Arial, sans-serif; }
        html[data-theme="dark"] body { background: linear-gradient(135deg, #020617, #0f172a, #1e293b); color: #e2e8f0; }
        html[data-theme="light"] body { background: linear-gradient(135deg, #ffffff, #f1f5f9, #e2e8f0); color: #0f172a; }
        html[data-theme="ocean"] body { background: linear-gradient(135deg, #00121f, #062a4d, #0ea5e9); color: #e2e8f0; }
        html[data-theme="sunset"] body { background: linear-gradient(135deg, #1a0b13, #4a1730, #fb7185); color: #e2e8f0; }
        html[data-theme="christmas"] body { background: linear-gradient(135deg, #05140b, #0f4d2d, #22c55e); color: #e2e8f0; }
        html[data-theme="newyear"] body { background: linear-gradient(135deg, #0b0a1a, #312e81, #a78bfa); color: #e2e8f0; }
        html[data-theme="easter"] body { background: linear-gradient(135deg, #120726, #4c1d95, #c084fc); color: #e2e8f0; }
        html[data-theme="mothersday"] body { background: linear-gradient(135deg, #1a0b1f, #5b1b4a, #f472b6); color: #e2e8f0; }
        html[data-theme="fathersday"] body { background: linear-gradient(135deg, #031225, #1d4ed8, #60a5fa); color: #e2e8f0; }
        html[data-theme="foundersday"] body { background: linear-gradient(135deg, #17110a, #7c2d12, #fbbf24); color: #e2e8f0; }

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

        .page-loader-card,
        .upload-progress-card {
            width: min(100%, 28rem);
            border-radius: 1.75rem;
            border: 1px solid rgba(255, 255, 255, 0.12);
            background: rgba(15, 23, 42, 0.92);
            padding: 1.5rem;
            box-shadow: 0 30px 90px rgba(2, 6, 23, 0.45);
        }

        html[data-theme="light"] .page-loader-card,
        html[data-theme="light"] .upload-progress-card {
            background: rgba(255, 255, 255, 0.94);
            border-color: rgba(15, 23, 42, 0.1);
        }

        .page-loader-spinner {
            width: 3.25rem;
            height: 3.25rem;
            margin: 0 auto 1rem;
            border-radius: 9999px;
            border: 4px solid rgba(255, 255, 255, 0.12);
            border-top-color: #fbbf24;
            animation: setupLoaderSpin 0.8s linear infinite;
        }

        .upload-progress-overlay {
            position: fixed;
            inset: 0;
            z-index: 125;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            background: rgba(2, 6, 23, 0.88);
            backdrop-filter: blur(18px);
            -webkit-backdrop-filter: blur(18px);
        }

        .upload-progress-track {
            overflow: hidden;
            height: 0.9rem;
            width: 100%;
            border-radius: 9999px;
            background: rgba(255, 255, 255, 0.08);
        }

        .upload-progress-bar {
            height: 100%;
            width: 0;
            border-radius: inherit;
            background: linear-gradient(90deg, #fbbf24 0%, #3b82f6 100%);
            box-shadow: 0 0 22px rgba(251, 191, 36, 0.4);
            transition: width 0.2s ease;
        }

        @keyframes setupLoaderSpin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body class="min-h-screen p-4 sm:p-8">
    <div id="setup-page-loader" class="page-loader-overlay" aria-live="polite">
        <div class="page-loader-card text-center">
            <div class="page-loader-spinner"></div>
            <p id="setup-page-loader-text" class="text-sm font-black uppercase tracking-[0.24em] text-amber-300">Loading</p>
            <p id="setup-page-loader-subtext" class="mt-3 text-sm opacity-80">Please wait while the installer gets ready.</p>
        </div>
    </div>
    <div class="mx-auto max-w-4xl rounded-[2rem] border border-white/10 bg-slate-950/60 p-6 sm:p-10 shadow-2xl backdrop-blur-xl">
        <div class="mb-8">
            <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-amber-400">Local Installer</p>
            <h1 class="mt-3 text-3xl sm:text-5xl font-black"><?php echo $success ? 'Setup Complete' : 'First Admin Setup'; ?></h1>
            <p class="mt-3 text-sm opacity-80"><?php echo $success ? 'The application is ready for this church installation.' : 'Set the church name and create the first admin login.'; ?></p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="mb-6 rounded-3xl border border-red-500/30 bg-red-500/10 p-5 text-sm">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($messages)): ?>
            <div class="mb-6 rounded-3xl border border-emerald-500/20 bg-emerald-500/10 p-5 text-sm space-y-2">
                <?php foreach ($messages as $message): ?>
                    <div><?php echo htmlspecialchars($message); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="mb-6 rounded-3xl border border-amber-400/30 bg-amber-400/10 p-5 text-sm">
            <div class="font-black uppercase tracking-[0.2em] text-amber-300 text-[10px]">Safety Notice</div>
            <div class="mt-2">
                Re-running this installer does not delete existing members, finance records, attendance, reports, or users. It only verifies setup and updates branding/settings unless you manually point the app to a different database.
            </div>
        </div>

        <?php if ($success): ?>
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">Church Name</p>
                    <p class="mt-3 text-2xl font-black"><?php echo htmlspecialchars($churchName); ?></p>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/5 p-6">
                    <p class="text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">Admin Username</p>
                    <p class="mt-3 text-2xl font-black"><?php echo htmlspecialchars($adminUsername); ?></p>
                </div>
            </div>
            <?php if ($loginUrl !== ''): ?>
                <a href="<?php echo htmlspecialchars($loginUrl); ?>" class="mt-8 inline-flex rounded-2xl bg-amber-400 px-6 py-4 text-xs font-black uppercase tracking-[0.2em] text-slate-900">
                    Open Login
                </a>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST" class="space-y-6">
                <div>
                    <label class="mb-2 block text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">Church Name</label>
                    <input type="text" name="church_name" value="<?php echo htmlspecialchars($churchName === 'Church Management' ? '' : $churchName); ?>" required class="w-full rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-sm font-bold text-inherit outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">Admin Username</label>
                    <input type="text" name="admin_username" value="<?php echo htmlspecialchars($adminUsername); ?>" required class="w-full rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-sm font-bold text-inherit outline-none">
                </div>
                <div>
                    <label class="mb-2 block text-[10px] font-bold uppercase tracking-[0.3em] text-slate-400">Admin Password</label>
                    <input type="password" name="admin_password" required class="w-full rounded-2xl border border-white/10 bg-white/5 px-5 py-4 text-sm font-bold text-inherit outline-none">
                </div>
                <button type="submit" class="inline-flex rounded-2xl bg-amber-400 px-6 py-4 text-xs font-black uppercase tracking-[0.2em] text-slate-900">
                    Run Setup
                </button>
            </form>
        <?php endif; ?>
    </div>
    <script>
        (function () {
            const pageLoader = document.getElementById('setup-page-loader');
            const pageLoaderText = document.getElementById('setup-page-loader-text');
            const pageLoaderSubtext = document.getElementById('setup-page-loader-subtext');

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

            window.addEventListener('load', function () {
                window.setTimeout(hidePageLoader, 120);
            });

            window.addEventListener('pageshow', function () {
                hidePageLoader();
            });

            document.addEventListener('click', function (event) {
                const link = event.target.closest('a');
                if (!link) return;
                const href = (link.getAttribute('href') || '').trim();
                if (!href || href === '#' || href.startsWith('#') || href.startsWith('javascript:')) return;
                showPageLoader('Loading Page', 'Opening the next installer page...');
            });

            document.addEventListener('submit', function (event) {
                const form = event.target;
                if (!(form instanceof HTMLFormElement)) return;
                showPageLoader('Loading', 'Running setup and saving your settings...');
            });
        })();
    </script>
</body>
</html>
<?php
}

function isSetupComplete(): bool
{
    try {
        $db = Database::getInstance();
        $hasUsersTable = $db->tableExists('users');
        $hasSettingsTable = $db->tableExists('settings');
        if (!$hasUsersTable || !$hasSettingsTable) {
            return false;
        }

        $userCount = (int)(($db->fetch("SELECT COUNT(*) as c FROM users")['c'] ?? 0));
        if ($userCount <= 0) {
            return false;
        }

        $churchName = trim((string)AppConfig::getSetting('church_name', ''));
        return $churchName !== '';
    } catch (Throwable $e) {
        return false;
    }
}
