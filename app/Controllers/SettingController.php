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
        $activeUsers = [];
        $activeCutoffSeconds = 10 * 60;
        $nowTs = time();
        foreach (($users ?: []) as $u) {
            $lastActivity = (string)($u['last_activity_at'] ?? '');
            if ($lastActivity === '') {
                continue;
            }
            $ts = strtotime($lastActivity);
            if ($ts !== false && ($nowTs - $ts) <= $activeCutoffSeconds) {
                $activeUsers[] = $u;
            }
        }
        $passwordResetRequests = [];
        if (Auth::isAdmin()) {
            $this->ensurePasswordResetRequestSchema($db);
            $passwordResetRequests = $db->fetchAll(
                "SELECT r.id, r.user_id, r.requested_login, r.status, r.requested_at,
                        u.name, u.email, u.username, u.role
                 FROM password_reset_requests r
                 INNER JOIN users u ON u.id = r.user_id
                 WHERE r.status = 'pending'
                 ORDER BY r.requested_at DESC, r.id DESC
                 LIMIT 50"
            ) ?: [];
        }
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
        $storageConfig = [
            'supabase_url' => trim((string)Env::get('SUPABASE_URL', '')),
            'bucket' => trim((string)Env::get('SUPABASE_STORAGE_BUCKET', '')) !== '' ? trim((string)Env::get('SUPABASE_STORAGE_BUCKET', '')) : 'uploads',
            'has_service_role_key' => trim((string)Env::get('SUPABASE_SERVICE_ROLE_KEY', '')) !== '',
        ];
        $storageConfig['enabled'] = ($storageConfig['supabase_url'] !== '' && $storageConfig['has_service_role_key']);
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
            'storageConfig' => $storageConfig,
            'dbConfig' => $dbConfig,
            'me' => $me,
            'activeUsers' => $activeUsers,
            'passwordResetRequests' => $passwordResetRequests
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

    public function updateUserUsername() {
        $this->isAdmin();

        $db = Database::getInstance();
        $this->ensureUserSchema($db);

        $userId = (int)($_POST['user_id'] ?? 0);
        $username = trim((string)($_POST['username'] ?? ''));

        if ($userId <= 0) {
            Session::flash('error', 'Invalid user selected.');
            $this->redirectSettings();
        }

        if ($username === '' || strlen($username) < 3) {
            Session::flash('error', 'Username must be at least 3 characters.');
            $this->redirectSettings();
        }

        if (strlen($username) > 60) {
            Session::flash('error', 'Username is too long.');
            $this->redirectSettings();
        }

        if (!preg_match('/^[a-zA-Z0-9._-]+$/', $username)) {
            Session::flash('error', 'Username can only contain letters, numbers, dot, underscore, and hyphen.');
            $this->redirectSettings();
        }

        $user = $db->fetch("SELECT id, email, username FROM users WHERE id = ? LIMIT 1", [$userId]);
        if (!$user) {
            Session::flash('error', 'User not found.');
            $this->redirectSettings();
        }

        $existing = $db->fetch("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1", [$username, $userId]);
        if ($existing) {
            Session::flash('error', 'Username is already taken.');
            $this->redirectSettings();
        }

        try {
            $db->query("UPDATE users SET username = ? WHERE id = ?", [$username, $userId]);
            AuditLog::log("Updated username for user: " . ($user['email'] ?? 'Unknown'), "users", $userId);
            Session::flash('success', 'Username updated successfully.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to update username: ' . $e->getMessage());
        }

        $this->redirectSettings();
    }

    public function updateUserPhoto() {
        $this->isAdmin();

        $db = Database::getInstance();
        $this->ensureUserSchema($db);

        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId <= 0) {
            Session::flash('error', 'Invalid user selected.');
            $this->redirectSettings();
        }

        if (empty($_FILES['photo']['name'])) {
            Session::flash('error', 'Please select a photo.');
            $this->redirectSettings();
        }

        $user = $db->fetch("SELECT id, email, photo_path FROM users WHERE id = ? LIMIT 1", [$userId]);
        if (!$user) {
            Session::flash('error', 'User not found.');
            $this->redirectSettings();
        }

        try {
            $photoPath = $this->normalizeUploadPath($this->handleUserPhotoUpload($_FILES['photo']));
            $oldPath = trim((string)($user['photo_path'] ?? ''));
            if ($oldPath !== '') {
                $this->supabaseDeleteByPublicUrl($oldPath);
                $oldFile = $this->resolveUploadFilePath($oldPath);
                if ($oldFile !== '' && file_exists($oldFile)) {
                    @unlink($oldFile);
                }
            }
            $db->query("UPDATE users SET photo_path = ? WHERE id = ?", [$photoPath, $userId]);
            AuditLog::log("Updated profile photo for user: " . ($user['email'] ?? 'Unknown'), "users", $userId);
            Session::flash('success', 'Profile photo updated successfully.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to update photo: ' . $e->getMessage());
        }

        $this->redirectSettings();
    }

    public function updateUserRole() {
        $this->isAdmin();

        $db = Database::getInstance();
        $this->ensureUserSchema($db);

        $userId = (int)($_POST['user_id'] ?? 0);
        $role = strtolower(trim((string)($_POST['role'] ?? '')));
        $departmentId = (int)($_POST['department_id'] ?? 0);

        if ($userId <= 0) {
            Session::flash('error', 'Invalid user selected.');
            $this->redirectSettings();
        }

        if ($userId === (int)Session::get('user_id')) {
            Session::flash('error', 'You cannot change your own permission level.');
            $this->redirectSettings();
        }

        $allowedRoles = [
            'finance_staff',
            'finance_head',
            'dept_head',
            'visitation_team',
            'auditor',
            'pastor',
            'admin'
        ];
        if (!in_array($role, $allowedRoles, true)) {
            Session::flash('error', 'Invalid permission level.');
            $this->redirectSettings();
        }

        if (in_array($role, ['dept_head', 'visitation_team'], true)) {
            if ($departmentId <= 0) {
                Session::flash('error', 'Department is required for this permission level.');
                $this->redirectSettings();
            }
        } else {
            $departmentId = 0;
        }

        $user = $db->fetch("SELECT id, email, role FROM users WHERE id = ? LIMIT 1", [$userId]);
        if (!$user) {
            Session::flash('error', 'User not found.');
            $this->redirectSettings();
        }

        try {
            $db->query(
                "UPDATE users SET role = ?, department_id = ? WHERE id = ?",
                [$role, $departmentId > 0 ? $departmentId : null, $userId]
            );
            AuditLog::log("Updated permission level for user: " . ($user['email'] ?? 'Unknown'), "users", $userId);
            Session::flash('success', 'Permission level updated successfully.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to update permission level: ' . $e->getMessage());
        }

        $this->redirectSettings();
    }

    public function approvePasswordReset() {
        $this->isAdmin();

        $db = Database::getInstance();
        $this->ensureUserSchema($db);
        $this->ensurePasswordResetRequestSchema($db);

        $requestId = (int)($_POST['request_id'] ?? 0);
        if ($requestId <= 0) {
            Session::flash('error', 'Invalid reset request.');
            $this->redirectSettings();
        }

        $req = $db->fetch(
            "SELECT r.id, r.user_id, r.requested_login, r.status, u.email
             FROM password_reset_requests r
             INNER JOIN users u ON u.id = r.user_id
             WHERE r.id = ? LIMIT 1",
            [$requestId]
        );
        if (!$req || strtolower((string)($req['status'] ?? '')) !== 'pending') {
            Session::flash('error', 'Reset request is not pending.');
            $this->redirectSettings();
        }

        $token = bin2hex(random_bytes(20));
        $tokenHash = hash('sha256', $token);
        $expiresAt = date('Y-m-d H:i:s', time() + 30 * 60);

        try {
            $db->query("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE id = ?", [$tokenHash, $expiresAt, (int)$req['user_id']]);
            $db->query(
                "UPDATE password_reset_requests
                 SET status = 'approved', approved_by = ?, approved_at = NOW(), token_hash = ?, token_expires_at = ?
                 WHERE id = ?",
                [(int)Session::get('user_id'), $tokenHash, $expiresAt, $requestId]
            );

            $resetLink = rtrim((string)BASE_URL, '/') . '/reset-password?token=' . urlencode($token);
            Session::flash('admin_reset_link', $resetLink);
            Session::flash('admin_reset_target', (string)($req['email'] ?? ''));
            AuditLog::log("Approved password reset request for user: " . ($req['email'] ?? 'Unknown'), "password_reset_requests", $requestId);
            Session::flash('success', 'Reset approved. Copy the reset link below and send it to the user.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to approve reset: ' . $e->getMessage());
        }

        $this->redirectSettings();
    }

    public function rejectPasswordReset() {
        $this->isAdmin();

        $db = Database::getInstance();
        $this->ensurePasswordResetRequestSchema($db);

        $requestId = (int)($_POST['request_id'] ?? 0);
        if ($requestId <= 0) {
            Session::flash('error', 'Invalid reset request.');
            $this->redirectSettings();
        }

        try {
            $db->query(
                "UPDATE password_reset_requests
                 SET status = 'rejected', approved_by = ?, approved_at = NOW()
                 WHERE id = ? AND status = 'pending'",
                [(int)Session::get('user_id'), $requestId]
            );
            Session::flash('success', 'Reset request rejected.');
        } catch (Throwable $e) {
            Session::flash('error', 'Failed to reject reset request: ' . $e->getMessage());
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
                $photoPath = $this->normalizeUploadPath($this->handleUserPhotoUpload($_FILES['photo']));
                if (!empty($old['photo_path'])) {
                    $this->supabaseDeleteByPublicUrl((string)$old['photo_path']);
                    $oldFile = $this->resolveUploadFilePath($old['photo_path']);
                    if ($oldFile !== '' && file_exists($oldFile)) {
                        @unlink($oldFile);
                    }
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

            if (!$db->columnExists('users', 'last_activity_at')) {
                $db->query("ALTER TABLE users ADD COLUMN last_activity_at " . ($db->isPgsql() ? 'TIMESTAMP' : 'DATETIME') . " NULL");
            }
        });
    }

    private function ensurePasswordResetRequestSchema($db) {
        SchemaState::once('password_reset_requests_schema', function () use ($db) {
            if ($db->tableExists('password_reset_requests')) {
                return;
            }

            if ($db->isPgsql()) {
                $db->rawExec(
                    "CREATE TABLE IF NOT EXISTS public.password_reset_requests (
                        id integer generated by default as identity primary key,
                        user_id integer not null references public.users(id) on delete cascade,
                        requested_login varchar(100) null,
                        status varchar(20) not null default 'pending',
                        requested_at timestamptz not null default timezone('utc', now()),
                        approved_by integer null references public.users(id) on delete set null,
                        approved_at timestamptz null,
                        token_hash varchar(64) null,
                        token_expires_at timestamptz null,
                        consumed_at timestamptz null
                    )"
                );
                $db->rawExec("CREATE INDEX IF NOT EXISTS idx_prr_status ON public.password_reset_requests (status)");
                $db->rawExec("CREATE INDEX IF NOT EXISTS idx_prr_user ON public.password_reset_requests (user_id)");
                return;
            }

            $db->rawExec(
                "CREATE TABLE IF NOT EXISTS password_reset_requests (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    requested_login VARCHAR(100) NULL,
                    status VARCHAR(20) NOT NULL DEFAULT 'pending',
                    requested_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    approved_by INT NULL,
                    approved_at DATETIME NULL,
                    token_hash VARCHAR(64) NULL,
                    token_expires_at DATETIME NULL,
                    consumed_at DATETIME NULL,
                    INDEX idx_prr_status (status),
                    INDEX idx_prr_user (user_id)
                )"
            );
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
        if (!is_array($file) || !isset($file['error'])) {
            throw new Exception("Upload failed.");
        }
        $err = (int)$file['error'];
        if ($err !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE => 'File is too large.',
                UPLOAD_ERR_FORM_SIZE => 'File is too large.',
                UPLOAD_ERR_PARTIAL => 'File upload was interrupted.',
                UPLOAD_ERR_NO_FILE => 'No file selected.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server temporary folder missing.',
                UPLOAD_ERR_CANT_WRITE => 'Server failed to write file.',
                UPLOAD_ERR_EXTENSION => 'Upload blocked by server extension.',
            ];
            throw new Exception($map[$err] ?? 'Upload failed.');
        }

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

        if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new Exception("Only JPG, JPEG, PNG, GIF & WEBP files are allowed.");
        }

        $supabaseUrl = trim((string)Env::get('SUPABASE_URL', ''));
        $supabaseServiceRoleKey = trim((string)Env::get('SUPABASE_SERVICE_ROLE_KEY', ''));
        $bucket = trim((string)Env::get('SUPABASE_STORAGE_BUCKET', ''));
        if ($bucket === '') $bucket = 'uploads';

        if ($supabaseUrl !== '' && $supabaseServiceRoleKey !== '') {
            $mime = $this->resolveMimeType($file["tmp_name"], $fileExtension);
            $objectPath = 'users/' . date('Y') . '/' . date('m') . '/' . $newFileName;
            $publicUrl = $this->supabaseUploadObject($supabaseUrl, $supabaseServiceRoleKey, $bucket, $objectPath, $file["tmp_name"], $mime);
            return $publicUrl;
        }

        if (!is_uploaded_file($file["tmp_name"])) {
            throw new Exception("Upload failed.");
        }

        if (!move_uploaded_file($file["tmp_name"], ROOT_PATH . '/' . $targetFile)) {
            throw new Exception("Failed to save uploaded file.");
        }

        return $targetFile;
    }

    private function normalizeUploadPath($path) {
        $pRaw = trim((string)$path);
        if ($pRaw !== '' && preg_match('#^https?://#i', $pRaw)) {
            return $pRaw;
        }
        $p = str_replace('\\', '/', $pRaw);
        $p = ltrim(trim($p), '/');
        $posPublic = strpos($p, 'public/uploads/');
        if ($posPublic !== false) {
            return substr($p, $posPublic);
        }
        $posUploads = strpos($p, 'uploads/');
        if ($posUploads !== false) {
            return 'public/' . substr($p, $posUploads);
        }
        return $p;
    }

    private function resolveUploadFilePath($storedPath) {
        $p = $this->normalizeUploadPath($storedPath);
        if ($p === '') return '';
        if (preg_match('#^https?://#i', $p)) return '';
        $full = ROOT_PATH . '/' . $p;
        if (file_exists($full)) return $full;
        $alt = ROOT_PATH . '/public/' . ltrim($p, '/');
        if (file_exists($alt)) return $alt;
        return $full;
    }

    private function resolveMimeType($tmpFile, $extension) {
        $mime = '';
        if (function_exists('finfo_open')) {
            try {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                if ($finfo) {
                    $detected = finfo_file($finfo, $tmpFile);
                    finfo_close($finfo);
                    if (is_string($detected)) $mime = $detected;
                }
            } catch (Throwable $e) {
            }
        }
        if ($mime !== '') return $mime;
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        ];
        return $map[strtolower((string)$extension)] ?? 'application/octet-stream';
    }

    private function supabaseUploadObject($supabaseUrl, $serviceRoleKey, $bucket, $objectPath, $tmpFile, $contentType) {
        $base = rtrim((string)$supabaseUrl, '/');
        $bucket = trim((string)$bucket);
        $objectPath = ltrim((string)$objectPath, '/');
        $encodedPath = implode('/', array_map('rawurlencode', array_filter(explode('/', $objectPath), 'strlen')));
        $url = $base . '/storage/v1/object/' . rawurlencode($bucket) . '/' . $encodedPath;
        $data = file_get_contents($tmpFile);
        if ($data === false) {
            throw new Exception('Failed to read upload file.');
        }

        $headers = [
            'Authorization: Bearer ' . $serviceRoleKey,
            'apikey: ' . $serviceRoleKey,
            'x-upsert: true',
            'Content-Type: ' . $contentType,
        ];

        $ok = false;
        $status = 0;
        $body = '';
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_HEADER, false);
            $resp = curl_exec($ch);
            if ($resp !== false) {
                $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $ok = $status >= 200 && $status < 300;
                $body = is_string($resp) ? $resp : '';
            } else {
                $body = (string)curl_error($ch);
            }
            curl_close($ch);
        } else {
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => implode("\r\n", $headers),
                    'content' => $data,
                    'ignore_errors' => true,
                ]
            ]);
            $resp = @file_get_contents($url, false, $context);
            $body = is_string($resp) ? $resp : '';
            $status = 0;
            if (isset($http_response_header) && is_array($http_response_header)) {
                foreach ($http_response_header as $h) {
                    if (preg_match('#^HTTP/\\S+\\s+(\\d{3})#', $h, $m)) {
                        $status = (int)$m[1];
                        break;
                    }
                }
            }
            $ok = $status >= 200 && $status < 300;
        }

        if (!$ok) {
            $hint = '';
            $decoded = json_decode((string)$body, true);
            if (is_array($decoded)) {
                $hint = (string)($decoded['message'] ?? $decoded['error'] ?? '');
            }
            if ($hint === '' && $body !== '') {
                $hint = substr(trim((string)$body), 0, 180);
            }
            $msg = 'Cloud upload failed';
            if ($status > 0) $msg .= " (HTTP $status)";
            if ($hint !== '') $msg .= ': ' . $hint;
            throw new Exception($msg);
        }

        return $base . '/storage/v1/object/public/' . rawurlencode($bucket) . '/' . $encodedPath;
    }

    private function supabaseDeleteByPublicUrl($publicUrl) {
        $publicUrl = trim((string)$publicUrl);
        if ($publicUrl === '') return;

        $supabaseUrl = trim((string)Env::get('SUPABASE_URL', ''));
        $supabaseServiceRoleKey = trim((string)Env::get('SUPABASE_SERVICE_ROLE_KEY', ''));
        $bucket = trim((string)Env::get('SUPABASE_STORAGE_BUCKET', ''));
        if ($bucket === '') $bucket = 'uploads';
        if ($supabaseUrl === '' || $supabaseServiceRoleKey === '') return;

        $base = rtrim($supabaseUrl, '/');
        $prefix = $base . '/storage/v1/object/public/' . $bucket . '/';
        if (strpos($publicUrl, $prefix) !== 0) return;
        $objectPath = substr($publicUrl, strlen($prefix));
        if ($objectPath === '') return;
        $objectPath = implode('/', array_map('rawurlencode', array_filter(explode('/', rawurldecode($objectPath)), 'strlen')));
        $url = $base . '/storage/v1/object/' . rawurlencode($bucket) . '/' . $objectPath;

        $headers = [
            'Authorization: Bearer ' . $supabaseServiceRoleKey,
            'apikey: ' . $supabaseServiceRoleKey,
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_exec($ch);
            curl_close($ch);
            return;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'DELETE',
                'header' => implode("\r\n", $headers),
                'ignore_errors' => true,
            ]
        ]);
        @file_get_contents($url, false, $context);
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
        if ($normalizedOld !== '' && substr($normalizedOld, 0, strlen('public/uploads/branding/')) === 'public/uploads/branding/' && file_exists(ROOT_PATH . '/' . $normalizedOld)) {
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
