<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Department.php';

class SettingController extends BaseController {
    public function index() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $db = Database::getInstance();
        $this->ensureUserSchema($db);
        $users = $db->fetchAll("SELECT * FROM users ORDER BY created_at DESC");
        $deptModel = new Department();
        $departments = $deptModel->all('name ASC');
        $churchName = AppConfig::getSetting('church_name', 'Church Management');
        $theme = AppConfig::getSetting('theme', 'dark');
        $smsProvider = AppConfig::getSetting('sms_provider', 'nalo');
        $smsApiKey = AppConfig::getSetting('sms_api_key', '');
        $smsSenderId = AppConfig::getSetting('sms_sender_id', '');
        $smsPrefix = AppConfig::getSetting('sms_nalo_prefix', 'Resl_Nalo');
        $smsBaseUrl = AppConfig::getSetting('sms_base_url', 'https://sms.nalosolutions.com/smsbackend/clientapi/{prefix}/send-message/');
        $smsInfobipBaseUrl = AppConfig::getSetting('sms_infobip_base_url', 'https://api.infobip.com');
        $smsTwilioAccountSid = AppConfig::getSetting('sms_twilio_account_sid', '');
        $smsTwilioAuthToken = AppConfig::getSetting('sms_twilio_auth_token', '');
        $smsTwilioFrom = AppConfig::getSetting('sms_twilio_from', '');
        $dbDriver = strtolower((string)Env::get('DB_DRIVER', 'mysql'));
        if (in_array($dbDriver, ['postgres', 'postgresql'], true)) {
            $dbDriver = 'pgsql';
        }
        $dbConfig = [
            'driver' => in_array($dbDriver, ['mysql', 'pgsql'], true) ? $dbDriver : 'mysql',
            'host' => (string)Env::get('DB_HOST', 'localhost'),
            'port' => (string)Env::get('DB_PORT', $dbDriver === 'pgsql' ? '5432' : '3306'),
            'name' => (string)Env::get('DB_NAME', $dbDriver === 'pgsql' ? 'postgres' : 'church_management'),
            'user' => (string)Env::get('DB_USER', $dbDriver === 'pgsql' ? 'postgres' : 'root'),
            'schema' => (string)Env::get('DB_SCHEMA', $dbDriver === 'pgsql' ? 'public' : ((string)Env::get('DB_NAME', 'church_management'))),
            'sslmode' => (string)Env::get('DB_SSLMODE', $dbDriver === 'pgsql' ? 'require' : ''),
            'has_pass' => trim((string)Env::get('DB_PASS', '')) !== '',
            'pdo_pgsql' => extension_loaded('pdo_pgsql'),
            'pgsql' => extension_loaded('pgsql')
        ];
        $me = null;
        if (Session::get('user_id')) {
            $me = $db->fetch("SELECT id, name, username, email, role, photo_path, department_id FROM users WHERE id = ?", [Session::get('user_id')]);
        }
        
        View::render('settings.index', [
            'title' => 'System Settings',
            'users' => $users,
            'departments' => $departments,
            'churchName' => $churchName,
            'churchLogoPath' => Branding::getLogoPath(),
            'theme' => $theme,
            'smsProvider' => $smsProvider,
            'smsApiKey' => $smsApiKey,
            'smsSenderId' => $smsSenderId,
            'smsPrefix' => $smsPrefix,
            'smsBaseUrl' => $smsBaseUrl,
            'smsInfobipBaseUrl' => $smsInfobipBaseUrl,
            'smsTwilioAccountSid' => $smsTwilioAccountSid,
            'smsTwilioAuthToken' => $smsTwilioAuthToken,
            'smsTwilioFrom' => $smsTwilioFrom,
            'dbConfig' => $dbConfig,
            'me' => $me
        ]);
    }

    public function updateChurchName() {
        $this->updateBranding();
    }

    public function updateBranding() {
        $this->isAdmin();
        $name = trim((string)($_POST['church_name'] ?? ''));
        $theme = strtolower(trim((string)($_POST['theme'] ?? AppConfig::getSetting('theme', 'dark'))));
        $theme = in_array($theme, ['dark', 'light', 'ocean', 'sunset'], true) ? $theme : 'dark';
        
        if (empty($name)) {
            Session::flash('error', 'Church name cannot be empty');
        } else {
            $db = Database::getInstance();
            try {
                $oldChurchName = (string)AppConfig::getSetting('church_name', '');
                $oldTheme = (string)AppConfig::getSetting('theme', 'dark');
                $oldLogoPath = trim((string)AppConfig::getSetting('church_logo', ''));

                $this->upsertSetting($db, 'church_name', $name);
                $this->upsertSetting($db, 'theme', $theme);

                if (!empty($_FILES['church_logo']['name'])) {
                    $logoPath = $this->handleChurchLogoUpload($_FILES['church_logo'], $oldLogoPath);
                    $this->upsertSetting($db, 'church_logo', $logoPath);
                }
                
                AuditLog::log("Updated church branding", "settings", null, [
                    'church_name' => $oldChurchName,
                    'theme' => $oldTheme,
                    'church_logo' => $oldLogoPath
                ], [
                    'church_name' => $name,
                    'theme' => $theme,
                    'church_logo' => AppConfig::getSetting('church_logo', $oldLogoPath)
                ]);
                Session::flash('success', 'Church branding updated successfully');
            } catch (Exception $e) {
                Session::flash('error', 'Error updating church branding: ' . $e->getMessage());
            }
        }
        
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/settings");
        exit;
    }

    public function addUser() {
        // Only admins can add users
        $this->isAdmin();
        $db = Database::getInstance();
        $this->ensureUserSchema($db);
        
        $name = $_POST['name'];
        $username = $_POST['username'] ?? null;
        $email = $_POST['email'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $role = $_POST['role'];
        $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        if (!in_array($role, ['dept_head', 'visitation_team'], true)) {
            $departmentId = null;
        }

        if ($role === 'visitation_team') {
            if ($departmentId === null) {
                Session::flash('error', 'Visitation team users must be assigned to the Visitation department.');
                header('Location: ' . BASE_URL . '/settings');
                exit;
            }

            $department = $db->fetch("SELECT name FROM departments WHERE id = ? LIMIT 1", [$departmentId]);
            $departmentName = strtolower(trim((string)($department['name'] ?? '')));
            if ($departmentName === '' || strpos($departmentName, 'visitation') === false) {
                Session::flash('error', 'Please assign visitation team users to the Visitation department only.');
                header('Location: ' . BASE_URL . '/settings');
                exit;
            }
        }
        
        try {
            $photoPath = null;
            if (!empty($_FILES['photo']['name'])) {
                $photoPath = $this->handleUserPhotoUpload($_FILES['photo']);
            }

            $db->query("INSERT INTO users (name, username, email, password, role, department_id, photo_path) VALUES (?, ?, ?, ?, ?, ?, ?)", [
                $name, $username, $email, $password, $role, $departmentId, $photoPath
            ]);
            
            AuditLog::log("Created new user: $email ($role)", "users");
            Session::flash('success', 'User created successfully');
        } catch (Exception $e) {
            Session::flash('error', 'Error creating user: ' . $e->getMessage());
        }
        
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/settings");
        exit;
    }

    public function deleteUser() {
        $this->isAdmin();
        
        $id = $_GET['id'] ?? null;
        if ($id == Session::get('user_id')) {
            Session::flash('error', 'You cannot delete your own account');
        } else {
            $db = Database::getInstance();
            $user = $db->fetch("SELECT email FROM users WHERE id = ?", [$id]);
            $db->query("DELETE FROM users WHERE id = ?", [$id]);
            
            AuditLog::log("Deleted user: " . ($user['email'] ?? 'Unknown'), "users");
            Session::flash('success', 'User deleted successfully');
        }
        
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/settings");
        exit;
    }

    public function resetUserPassword() {
        $this->isAdmin();

        $db = Database::getInstance();
        $this->ensureUserSchema($db);

        $userId = (int)($_POST['user_id'] ?? 0);
        $newPassword = (string)($_POST['new_password'] ?? '');
        $confirmPassword = (string)($_POST['confirm_password'] ?? '');

        if ($userId <= 0) {
            Session::flash('error', 'Invalid user selected.');
            $this->redirectSettings();
        }

        if ($userId === (int)Session::get('user_id')) {
            Session::flash('error', 'Use My Profile to change your own password.');
            $this->redirectSettings();
        }

        $newPassword = trim($newPassword);
        $confirmPassword = trim($confirmPassword);
        if ($confirmPassword !== '' && $confirmPassword !== $newPassword) {
            Session::flash('error', 'Passwords do not match.');
            $this->redirectSettings();
        }
        if ($newPassword === '' || strlen($newPassword) < 6) {
            Session::flash('error', 'Password must be at least 6 characters.');
            $this->redirectSettings();
        }

        $user = $db->fetch("SELECT id, name, email, role FROM users WHERE id = ? LIMIT 1", [$userId]);
        if (!$user) {
            Session::flash('error', 'User not found.');
            $this->redirectSettings();
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        try {
            $db->query("UPDATE users SET password = ? WHERE id = ?", [$hash, $userId]);
            AuditLog::log("Reset password for user: " . ($user['email'] ?? 'Unknown'), "users", $userId);
            Session::flash('success', 'Password updated successfully.');
        } catch (Exception $e) {
            Session::flash('error', 'Error updating password: ' . $e->getMessage());
        }

        $this->redirectSettings();
    }

    public function updateTheme() {
        $this->isAdmin();

        $theme = $_POST['theme'] ?? 'dark';
        $theme = in_array($theme, ['dark', 'light', 'ocean', 'sunset'], true) ? $theme : 'dark';

        $db = Database::getInstance();
        try {
            $exists = $db->fetch("SELECT id FROM settings WHERE key_name = 'theme'");
            if ($exists) {
                $db->query("UPDATE settings SET value = ? WHERE key_name = 'theme'", [$theme]);
            } else {
                $db->query("INSERT INTO settings (key_name, value) VALUES ('theme', ?)", [$theme]);
            }
            AppConfig::reset();

            AuditLog::log("Updated theme to: $theme", "settings");
            Session::flash('success', 'Theme updated successfully');
        } catch (Exception $e) {
            Session::flash('error', 'Error updating theme: ' . $e->getMessage());
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/settings");
        exit;
    }

    public function updateProfile() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $db = Database::getInstance();
        $this->ensureUserSchema($db);

        $userId = Session::get('user_id');
        if (!$userId) {
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/login");
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $newPassword = $_POST['password'] ?? '';

        if ($name === '' || $email === '') {
            Session::flash('error', 'Name and email are required');
            $this->redirectSettings();
        }

        $data = [
            'name' => $name,
            'username' => $username !== '' ? $username : null,
            'email' => $email
        ];

        if ($newPassword !== '') {
            $data['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }

        try {
            $old = $db->fetch("SELECT id, name, username, email, photo_path FROM users WHERE id = ?", [$userId]);

            if (!empty($_FILES['photo']['name'])) {
                $photoPath = $this->handleUserPhotoUpload($_FILES['photo']);
                if (!empty($old['photo_path']) && file_exists(ROOT_PATH . '/' . $old['photo_path'])) {
                    unlink(ROOT_PATH . '/' . $old['photo_path']);
                }
                $data['photo_path'] = $photoPath;
            }

            $setParts = [];
            $params = [];
            foreach ($data as $k => $v) {
                $setParts[] = "$k = ?";
                $params[] = $v;
            }
            $params[] = $userId;

            $db->query("UPDATE users SET " . implode(', ', $setParts) . " WHERE id = ?", $params);

            Session::set('user_name', $data['name']);
            if (array_key_exists('photo_path', $data)) {
                Session::set('user_photo', $data['photo_path']);
            }

            AuditLog::log("Updated admin profile", "users", $userId, $old, $data);
            Session::flash('success', 'Profile updated successfully');
        } catch (Exception $e) {
            Session::flash('error', 'Error updating profile: ' . $e->getMessage());
        }

        $this->redirectSettings();
    }

    public function updateSmsConfig() {
        $this->isAdmin();

        $db = Database::getInstance();

        $provider = strtolower(trim($_POST['sms_provider'] ?? 'nalo'));
        if (!in_array($provider, ['nalo', 'mnotify', 'twilio', 'infobip'], true)) {
            $provider = 'nalo';
        }
        $apiKey = trim($_POST['sms_api_key'] ?? '');
        $senderId = trim($_POST['sms_sender_id'] ?? '');
        $prefix = trim($_POST['sms_nalo_prefix'] ?? '');
        $baseUrl = trim($_POST['sms_base_url'] ?? '');
        $infobipBaseUrl = trim($_POST['sms_infobip_base_url'] ?? '');
        $twilioAccountSid = trim($_POST['sms_twilio_account_sid'] ?? '');
        $twilioAuthToken = trim($_POST['sms_twilio_auth_token'] ?? '');
        $twilioFrom = trim($_POST['sms_twilio_from'] ?? '');

        if ($senderId !== '') {
            $senderId = preg_replace('/\s+/', ' ', $senderId);
            $senderId = str_replace(' ', '', $senderId);
            $senderId = preg_replace('/[^A-Za-z0-9]/', '', $senderId);
            $senderId = substr($senderId, 0, 11);
        }

        if ($baseUrl === '') {
            $baseUrl = 'https://sms.nalosolutions.com/smsbackend/clientapi/{prefix}/send-message/';
        }

        if ($infobipBaseUrl === '') {
            $infobipBaseUrl = 'https://api.infobip.com';
        }
        if (!preg_match('#^https?://#i', $infobipBaseUrl)) {
            $infobipBaseUrl = 'https://' . $infobipBaseUrl;
        }
        $infobipBaseUrl = rtrim($infobipBaseUrl, '/');

        if ($prefix === '') {
            $prefix = 'Resl_Nalo';
        }

        try {
            $this->upsertSetting($db, 'sms_provider', $provider);
            $this->upsertSetting($db, 'sms_api_key', $apiKey);
            $this->upsertSetting($db, 'sms_sender_id', $senderId);
            $this->upsertSetting($db, 'sms_nalo_prefix', $prefix);
            $this->upsertSetting($db, 'sms_base_url', $baseUrl);
            $this->upsertSetting($db, 'sms_infobip_base_url', $infobipBaseUrl);
            $this->upsertSetting($db, 'sms_twilio_account_sid', $twilioAccountSid);
            $this->upsertSetting($db, 'sms_twilio_auth_token', $twilioAuthToken);
            $this->upsertSetting($db, 'sms_twilio_from', $twilioFrom);

            AuditLog::log("Updated SMS configuration", "settings");
            Session::flash('success', 'SMS configuration updated successfully');
        } catch (Exception $e) {
            Session::flash('error', 'Error updating SMS configuration: ' . $e->getMessage());
        }

        $this->redirectSettings();
    }

    public function updateDatabaseConnection() {
        $this->isAdmin();

        if (!extension_loaded('pdo_pgsql')) {
            Session::flash('error', 'pdo_pgsql extension is not enabled. Enable pdo_pgsql in php.ini then restart Apache.');
            $this->redirectSettings();
        }

        $host = trim((string)($_POST['db_host'] ?? ''));
        $port = trim((string)($_POST['db_port'] ?? '5432'));
        $name = trim((string)($_POST['db_name'] ?? 'postgres'));
        $user = trim((string)($_POST['db_user'] ?? ''));
        $pass = (string)($_POST['db_pass'] ?? '');
        if ($pass === '') {
            $existing = (string)Env::get('DB_PASS', '');
            if (trim($existing) !== '') {
                $pass = $existing;
            }
        }
        $schema = trim((string)($_POST['db_schema'] ?? 'public'));
        $sslMode = trim((string)($_POST['db_sslmode'] ?? 'require'));

        if ($host === '' || $port === '' || $name === '' || $user === '') {
            Session::flash('error', 'Host, Port, Database, and User are required.');
            $this->redirectSettings();
        }

        if (!preg_match('/^\d+$/', $port)) {
            Session::flash('error', 'Port must be a number.');
            $this->redirectSettings();
        }

        if ($schema === '') {
            $schema = 'public';
        }

        $sslModeAllowed = ['disable', 'allow', 'prefer', 'require', 'verify-ca', 'verify-full'];
        if ($sslMode !== '' && !in_array(strtolower($sslMode), $sslModeAllowed, true)) {
            Session::flash('error', 'Invalid SSL Mode.');
            $this->redirectSettings();
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$name}";
        if ($sslMode !== '') {
            $dsn .= ";sslmode={$sslMode}";
        }

        try {
            $pdo = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            $pdo->query('SELECT 1');
        } catch (Throwable $e) {
            Session::flash('error', 'Connection test failed: ' . $e->getMessage());
            $this->redirectSettings();
        }

        try {
            $updates = [
                'DB_DRIVER' => 'pgsql',
                'DB_HOST' => $host,
                'DB_PORT' => $port,
                'DB_NAME' => $name,
                'DB_USER' => $user,
                'DB_PASS' => $pass,
                'DB_SCHEMA' => $schema,
                'DB_SSLMODE' => $sslMode
            ];

            $this->writeEnvUpdates($updates);
            foreach ($updates as $k => $v) {
                putenv($k . '=' . $v);
                $_ENV[$k] = $v;
                $_SERVER[$k] = $v;
            }
            Database::reset();

            AuditLog::log('Updated database connection settings (.env)', 'settings');
            Session::flash('success', 'Supabase connection saved to .env successfully.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to write .env: ' . $e->getMessage());
        }

        $this->redirectSettings();
    }

    private function ensureUserSchema($db) {
        SchemaState::once('users_schema', function () use ($db) {
            if (!$db->columnExists('users', 'username')) {
                $db->query("ALTER TABLE users ADD COLUMN username VARCHAR(60) NULL");
            }

            if (!$db->columnExists('users', 'photo_path')) {
                $db->query("ALTER TABLE users ADD COLUMN photo_path VARCHAR(255) NULL");
            }

            if (!$db->columnExists('users', 'department_id')) {
                $db->query("ALTER TABLE users ADD COLUMN department_id INT NULL");
            }
        });
    }

    private function writeEnvUpdates(array $updates) {
        $path = ROOT_PATH . '/.env';
        $lines = [];
        if (file_exists($path)) {
            $existing = file($path, FILE_IGNORE_NEW_LINES);
            if (is_array($existing)) {
                $lines = $existing;
            }
        }

        $found = [];
        foreach ($lines as $i => $line) {
            $raw = (string)$line;
            $trim = trim($raw);
            if ($trim === '' || str_starts_with($trim, '#')) {
                continue;
            }
            $parts = explode('=', $raw, 2);
            if (count($parts) !== 2) {
                continue;
            }
            $key = trim((string)$parts[0]);
            if ($key !== '' && array_key_exists($key, $updates)) {
                $lines[$i] = $key . '=' . $this->encodeEnvValue((string)$updates[$key]);
                $found[$key] = true;
            }
        }

        foreach ($updates as $k => $v) {
            if (!isset($found[$k])) {
                $lines[] = $k . '=' . $this->encodeEnvValue((string)$v);
            }
        }

        $content = implode(PHP_EOL, $lines) . PHP_EOL;
        if (file_put_contents($path, $content, LOCK_EX) === false) {
            throw new Exception('Unable to write .env file.');
        }
    }

    private function encodeEnvValue(string $value) {
        if ($value === '') {
            return '';
        }
        if (preg_match('/\s|#|=|"/', $value)) {
            $escaped = str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
            return '"' . $escaped . '"';
        }
        return $value;
    }

    private function handleUserPhotoUpload($file) {
        $targetDir = "public/uploads/users/";
        if (!is_dir(ROOT_PATH . '/' . $targetDir)) {
            mkdir(ROOT_PATH . '/' . $targetDir, 0777, true);
        }

        $fileExtension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $newFileName = uniqid('user_', true) . '.' . $fileExtension;
        $targetFile = $targetDir . $newFileName;

        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            throw new Exception("File is not an image.");
        }

        if ($file["size"] > 2000000) {
            throw new Exception("File is too large (max 2MB).");
        }

        if ($fileExtension != "jpg" && $fileExtension != "png" && $fileExtension != "jpeg" && $fileExtension != "gif") {
            throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed.");
        }

        if (move_uploaded_file($file["tmp_name"], ROOT_PATH . '/' . $targetFile)) {
            return $targetFile;
        }

        throw new Exception("Failed to move uploaded file.");
    }

    private function handleChurchLogoUpload($file, $oldLogoPath = '') {
        $targetDir = "public/uploads/branding/";
        if (!is_dir(ROOT_PATH . '/' . $targetDir)) {
            mkdir(ROOT_PATH . '/' . $targetDir, 0777, true);
        }

        $fileExtension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new Exception("Only JPG, JPEG, PNG, GIF, and WEBP files are allowed.");
        }

        $check = getimagesize($file["tmp_name"]);
        if ($check === false) {
            throw new Exception("Church logo must be a valid image.");
        }

        if (($file["size"] ?? 0) > 2000000) {
            throw new Exception("Church logo is too large (max 2MB).");
        }

        $newFileName = 'church_logo_' . date('YmdHis') . '.' . $fileExtension;
        $targetFile = $targetDir . $newFileName;

        if (!move_uploaded_file($file["tmp_name"], ROOT_PATH . '/' . $targetFile)) {
            throw new Exception("Failed to save church logo.");
        }

        $normalizedOld = ltrim((string)$oldLogoPath, '/');
        if ($normalizedOld !== '' && str_starts_with($normalizedOld, 'public/uploads/branding/') && file_exists(ROOT_PATH . '/' . $normalizedOld)) {
            @unlink(ROOT_PATH . '/' . $normalizedOld);
        }

        return $targetFile;
    }

    private function redirectSettings() {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/settings");
        exit;
    }

    private function upsertSetting($db, $key, $value) {
        $exists = $db->fetch("SELECT id FROM settings WHERE key_name = ?", [$key]);
        if ($exists) {
            $db->query("UPDATE settings SET value = ? WHERE key_name = ?", [$value, $key]);
        } else {
            $db->query("INSERT INTO settings (key_name, value) VALUES (?, ?)", [$key, $value]);
        }
        AppConfig::reset();
    }
}
