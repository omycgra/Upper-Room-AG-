<?php

class AuthController {
    public function showLogin() {
        if (Auth::check()) {
            $role = strtolower(trim((string)Session::get('user_role')));
            if ($role === 'auditor') {
                header('Location: auditor');
            } elseif ($role === 'pastor') {
                header('Location: pastor');
            } else {
                header('Location: dashboard');
            }
            exit;
        }
        
        // Render login view without the main layout
        $viewPath = __DIR__ . '/../Views/auth/login.php';
        if (file_exists($viewPath)) {
            $mode = 'login';
            require_once $viewPath;
        } else {
            die("Login view not found");
        }
    }

    public function login() {
        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';
        $loginType = $_POST['login_type'] ?? null;
        
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $result = Auth::login($login, $password, $loginType);
        if (!empty($result['success'])) {
            $role = strtolower(trim((string)Session::get('user_role')));
            if ($role === 'auditor') {
                header("Location: $base/auditor");
            } elseif ($role === 'pastor') {
                header("Location: $base/pastor");
            } else {
                header("Location: $base/dashboard");
            }
        } else {
            if (($result['reason'] ?? '') === 'permission_mismatch') {
                Session::flash('error', 'Permission level does not match this account. Please choose the correct permission level.');
            } else {
                Session::flash('error', 'Invalid username/email or password');
            }
            header("Location: $base/login");
        }
        exit;
    }

    public function logout() {
        Auth::logout();
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/login");
        exit;
    }

    public function importAttendance()
    {
        $expectedToken = trim((string)AppConfig::getSetting('attendance_cloud_token', ''));
        $provided = '';
        if (isset($_SERVER['HTTP_X_SYNC_TOKEN'])) {
            $provided = trim((string)$_SERVER['HTTP_X_SYNC_TOKEN']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $auth = trim((string)$_SERVER['HTTP_AUTHORIZATION']);
            if (stripos($auth, 'Bearer ') === 0) {
                $provided = trim(substr($auth, 7));
            }
        }

        if ($expectedToken === '' || $provided === '' || !hash_equals($expectedToken, $provided)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Forbidden'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            http_response_code(405);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Method Not Allowed'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $raw = file_get_contents('php://input');
        $json = json_decode((string)$raw, true);
        if (!is_array($json)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Invalid JSON'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $serviceDate = trim((string)($json['service_date'] ?? ''));
        $serviceType = trim((string)($json['service_type'] ?? ''));
        $records = $json['records'] ?? [];
        if ($serviceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate) || $serviceType === '' || !is_array($records)) {
            http_response_code(400);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => false, 'error' => 'Invalid payload'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $serviceType = function_exists('mb_substr') ? mb_substr($serviceType, 0, 100) : substr($serviceType, 0, 100);

        require_once __DIR__ . '/../Models/Attendance.php';
        require_once __DIR__ . '/../Models/Member.php';
        $this->ensureAttendanceSchema();

        $attendanceModel = new Attendance();
        $memberModel = new Member();

        $imported = 0;
        $duplicates = 0;
        $unmatched = 0;
        $invalid = 0;

        foreach ($records as $r) {
            if (!is_array($r)) {
                $invalid++;
                continue;
            }
            $bioId = strtoupper(trim((string)($r['bio_id'] ?? '')));
            $memberCode = strtoupper(trim((string)($r['member_code'] ?? '')));
            $status = trim((string)($r['status'] ?? 'Present'));
            $source = trim((string)($r['source'] ?? 'push'));

            if ($bioId === '' && $memberCode === '') {
                $invalid++;
                continue;
            }

            $member = null;
            if ($bioId !== '') {
                $member = $memberModel->findByBioId($bioId);
            }
            if (!$member && $memberCode !== '') {
                $member = $memberModel->findByMemberCode($memberCode);
            }
            if (!$member) {
                $unmatched++;
                continue;
            }

            $memberId = (int)($member['id'] ?? 0);
            if ($memberId <= 0) {
                $unmatched++;
                continue;
            }

            if ($attendanceModel->existsForMemberService($memberId, $serviceDate, $serviceType)) {
                $duplicates++;
                continue;
            }

            $payload = json_encode($r, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (!is_string($payload)) {
                $payload = null;
            }

            $attendanceModel->create([
                'member_id' => $memberId,
                'service_date' => $serviceDate,
                'service_type' => $serviceType,
                'status' => $status !== '' ? $status : 'Present',
                'source' => $source !== '' ? $source : 'push',
                'bio_id' => $bioId !== '' ? $bioId : null,
                'device_time' => $r['device_time'] ?? null,
                'device_serial' => $r['device_serial'] ?? null,
                'punch_type' => $r['punch_type'] ?? null,
                'raw_payload' => $payload,
                'imported_at' => date('Y-m-d H:i:s')
            ]);
            $imported++;
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => true,
            'imported' => $imported,
            'duplicates' => $duplicates,
            'unmatched' => $unmatched,
            'invalid' => $invalid
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function ensureAttendanceSchema(): void
    {
        $db = Database::getInstance();
        SchemaState::once('attendance_schema_v1', function () use ($db) {
            $columns = [
                'source' => "ALTER TABLE attendance ADD COLUMN source VARCHAR(20) NULL",
                'bio_id' => "ALTER TABLE attendance ADD COLUMN bio_id VARCHAR(50) NULL",
                'device_time' => "ALTER TABLE attendance ADD COLUMN device_time " . ($db->isPgsql() ? 'TIMESTAMP' : 'DATETIME') . " NULL",
                'device_serial' => "ALTER TABLE attendance ADD COLUMN device_serial VARCHAR(60) NULL",
                'punch_type' => "ALTER TABLE attendance ADD COLUMN punch_type VARCHAR(30) NULL",
                'raw_payload' => "ALTER TABLE attendance ADD COLUMN raw_payload " . ($db->isPgsql() ? 'TEXT' : 'TEXT') . " NULL",
                'imported_at' => "ALTER TABLE attendance ADD COLUMN imported_at " . ($db->isPgsql() ? 'TIMESTAMP' : 'DATETIME') . " NULL"
            ];

            foreach ($columns as $columnName => $sql) {
                if (!$db->columnExists('attendance', $columnName)) {
                    $db->query($sql);
                }
            }
        });
    }


    public function forgotPassword() {
        if (Auth::check()) {
            header('Location: dashboard');
            exit;
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $login = $_POST['login'] ?? '';
            $result = Auth::requestPasswordReset($login);
            if (!empty($result['success'])) {
                Session::flash('success', 'Reset request submitted. Please contact the admin to approve it.');
            } else {
                Session::flash('error', 'Account not found. Enter your username or email.');
            }
            header("Location: $base/forgot-password");
            exit;
        }

        $viewPath = __DIR__ . '/../Views/auth/login.php';
        if (file_exists($viewPath)) {
            $mode = 'forgot';
            require_once $viewPath;
        } else {
            die("Login view not found");
        }
    }

    public function resetPassword() {
        if (Auth::check()) {
            header('Location: dashboard');
            exit;
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['token'] ?? '';
            $password = (string)($_POST['password'] ?? '');
            $confirm = (string)($_POST['confirm_password'] ?? '');

            if ($password === '' || strlen($password) < 6) {
                Session::flash('error', 'Password must be at least 6 characters.');
                header("Location: $base/reset-password?token=" . urlencode((string)$token));
                exit;
            }

            if ($password !== $confirm) {
                Session::flash('error', 'Passwords do not match.');
                header("Location: $base/reset-password?token=" . urlencode((string)$token));
                exit;
            }

            $result = Auth::resetPasswordWithToken($token, $password);
            if (!empty($result['success'])) {
                Session::flash('success', 'Password updated. You can login now.');
                header("Location: $base/login");
                exit;
            }

            Session::flash('error', 'Reset link is invalid or expired. Generate a new one.');
            header("Location: $base/forgot-password");
            exit;
        }

        $token = $_GET['token'] ?? '';
        $viewPath = __DIR__ . '/../Views/auth/login.php';
        if (file_exists($viewPath)) {
            $mode = 'reset';
            require_once $viewPath;
        } else {
            die("Login view not found");
        }
    }
}
