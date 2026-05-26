<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Attendance.php';
require_once __DIR__ . '/../Models/Member.php';

class AttendanceController extends BaseController {
    public function index() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $this->ensureAttendanceSchema();
        $attendanceModel = new Attendance();
        $mode = $this->getAttendanceMode();
        $biotimeConfigured = $this->isBioTimeConfigured();
        $cloudUrl = rtrim(trim((string)AppConfig::getSetting('attendance_cloud_url', '')), '/');
        $cloudTokenSet = trim((string)AppConfig::getSetting('attendance_cloud_token', '')) !== '';
        $cloudConfigured = ($cloudUrl !== '' && $cloudTokenSet);
        $cloudLastPushedAt = trim((string)AppConfig::getSetting('attendance_cloud_last_pushed_at', ''));
        
        View::render('attendance.index', [
            'title' => 'Attendance Management',
            'attendance_rate' => $attendanceModel->getAttendanceRate(),
            'recent_records' => $attendanceModel->getRecentWithMember(50),
            'attendance_mode' => $mode,
            'biotime_configured' => $biotimeConfigured,
            'biotime_url' => $this->getBioTimeUrl(),
            'cloud_configured' => $cloudConfigured,
            'cloud_url' => $cloudUrl,
            'cloud_last_pushed_at' => $cloudLastPushedAt,
            'cloud_last_result' => (string)Session::flash('attendance_cloud_last_result')
        ]);
    }

    public function mark() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        if ($this->getAttendanceMode() !== 'manual') {
            Session::flash('error', 'Manual attendance is disabled. Change Attendance Mode in Settings.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }
        $this->ensureAttendanceSchema();
        $memberModel = new Member();
        View::render('attendance.mark', [
            'title' => 'Mark Attendance',
            'members' => $memberModel->all('first_name ASC')
        ]);
    }

    public function store() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        if ($this->getAttendanceMode() !== 'manual') {
            Session::flash('error', 'Manual attendance is disabled. Change Attendance Mode in Settings.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }
        $this->ensureAttendanceSchema();
        $attendanceModel = new Attendance();
        
        $memberIds = $_POST['member_ids'] ?? [];
        $serviceDate = trim((string)($_POST['service_date'] ?? ''));
        $serviceType = trim((string)($_POST['service_type'] ?? ''));
        if ($serviceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
            Session::flash('error', 'Invalid service date.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance/mark");
            exit;
        }
        if ($serviceType === '') {
            Session::flash('error', 'Service type is required.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance/mark");
            exit;
        }
        $serviceType = mb_substr($serviceType, 0, 100);
        $memberIds = array_values(array_unique(array_filter(array_map('intval', (array)$memberIds))));
        if (empty($memberIds)) {
            Session::flash('error', 'Select at least one member.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance/mark");
            exit;
        }
        
        $created = 0;
        $skipped = 0;
        foreach ($memberIds as $memberId) {
            $memberId = (int)$memberId;
            if ($memberId <= 0) {
                continue;
            }
            if ($attendanceModel->existsForMemberService($memberId, $serviceDate, $serviceType)) {
                $skipped++;
                continue;
            }
            $attendanceModel->create([
                'member_id' => $memberId,
                'service_date' => $serviceDate,
                'service_type' => $serviceType,
                'status' => 'Present',
                'source' => 'manual',
                'imported_at' => date('Y-m-d H:i:s')
            ]);
            $created++;
        }
        
        AuditLog::log("Marked attendance for $serviceType on $serviceDate", "attendance");
        $msg = 'Attendance marked successfully';
        if ($created === 0 && $skipped > 0) {
            $msg = 'Attendance already marked for selected members.';
        } elseif ($skipped > 0) {
            $msg .= " ($created new, $skipped already marked)";
        }
        Session::flash('success', $msg);
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/attendance");
        exit;
    }

    public function syncBioTime() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        if ($this->getAttendanceMode() !== 'biotime') {
            Session::flash('error', 'BioTime sync is disabled. Change Attendance Mode to BioTime in Settings.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }
        $this->ensureAttendanceSchema();

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $biotimeUrl = $this->getBioTimeUrl();
        if ($biotimeUrl === '') {
            Session::flash('error', 'BioTime is not configured. Set it in Settings → Attendance.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }
        if (!function_exists('curl_init')) {
            Session::flash('error', 'cURL is not available on this server. Enable PHP curl extension.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $serviceDate = trim((string)($_POST['service_date'] ?? ''));
        $serviceType = trim((string)($_POST['service_type'] ?? 'Sunday Service'));
        if ($serviceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
            Session::flash('error', 'Invalid service date.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }
        if ($serviceType === '') {
            $serviceType = 'Sunday Service';
        }
        $serviceType = mb_substr($serviceType, 0, 100);

        $tzName = $this->getBioTimeTimezone();
        try {
            $tz = new DateTimeZone($tzName);
        } catch (Throwable $e) {
            $tz = new DateTimeZone('Africa/Accra');
            $tzName = 'Africa/Accra';
        }

        $start = new DateTimeImmutable($serviceDate . ' 00:00:00', $tz);
        $end = new DateTimeImmutable($serviceDate . ' 23:59:59', $tz);
        $startStr = $start->format('Y-m-d H:i:s');
        $endStr = $end->format('Y-m-d H:i:s');

        $db = Database::getInstance();
        $rows = $db->fetchAll("SELECT id, bio_id FROM members WHERE bio_id IS NOT NULL") ?: [];
        $bioMap = [];
        foreach ($rows as $r) {
            $bio = strtoupper(trim((string)($r['bio_id'] ?? '')));
            if ($bio === '') {
                continue;
            }
            $bioMap[$bio] = (int)($r['id'] ?? 0);
        }

        if (empty($bioMap)) {
            Session::flash('error', 'No members have BIO ID set. Set BIO ID for members first.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $token = $this->getBioTimeToken();
        if ($token === '') {
            $biotimeUser = $this->getBioTimeUsername();
            $biotimePass = $this->getBioTimePassword();
            if ($biotimeUser === '' || $biotimePass === '') {
                Session::flash('error', 'BioTime is not configured. Set token or username/password in Settings → Attendance.');
                $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                header("Location: $base/attendance");
                exit;
            }
            $token = $this->biotimeGetToken($biotimeUrl, $biotimeUser, $biotimePass);
            if ($token === '') {
                Session::flash('error', 'BioTime login failed. Check BIOTIME_URL/USERNAME/PASSWORD.');
                $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                header("Location: $base/attendance");
                exit;
            }
        }

        $itemsResult = $this->biotimeFetchTransactions($biotimeUrl, $token, $startStr, $endStr);
        if (!($itemsResult['ok'] ?? false) && (int)($itemsResult['status'] ?? 0) === 401) {
            $biotimeUser = $this->getBioTimeUsername();
            $biotimePass = $this->getBioTimePassword();
            if ($biotimeUser !== '' && $biotimePass !== '') {
                $freshToken = $this->biotimeGetToken($biotimeUrl, $biotimeUser, $biotimePass);
                if ($freshToken !== '') {
                    $itemsResult = $this->biotimeFetchTransactions($biotimeUrl, $freshToken, $startStr, $endStr);
                }
            }
        }
        if (!($itemsResult['ok'] ?? false)) {
            $err = trim((string)($itemsResult['error'] ?? 'Failed to fetch BioTime transactions.'));
            Session::flash('error', $err !== '' ? $err : 'Failed to fetch BioTime transactions.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $items = $itemsResult['items'] ?? [];
        $attendanceModel = new Attendance();

        $imported = 0;
        $duplicates = 0;
        $unmatched = 0;
        $invalid = 0;

        foreach ($items as $tx) {
            $parsed = $this->biotimeParseTransaction($tx);
            $bioId = strtoupper(trim((string)($parsed['bio_id'] ?? '')));
            if ($bioId === '') {
                $invalid++;
                continue;
            }

            $memberId = (int)($bioMap[$bioId] ?? 0);
            if ($memberId <= 0) {
                $unmatched++;
                continue;
            }

            if ($attendanceModel->existsForMemberService($memberId, $serviceDate, $serviceType)) {
                $duplicates++;
                continue;
            }

            $payload = json_encode($tx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (!is_string($payload)) {
                $payload = null;
            }

            $attendanceModel->create([
                'member_id' => $memberId,
                'service_date' => $serviceDate,
                'service_type' => $serviceType,
                'status' => 'Present',
                'source' => 'biotime',
                'bio_id' => $bioId,
                'device_time' => $parsed['device_time'] ?? null,
                'device_serial' => $parsed['device_serial'] ?? null,
                'punch_type' => $parsed['punch_type'] ?? null,
                'raw_payload' => $payload,
                'imported_at' => date('Y-m-d H:i:s')
            ]);
            $imported++;
        }

        AuditLog::log("BioTime sync attendance for $serviceType on $serviceDate ($tzName)", "attendance");
        $summary = "BioTime sync complete: $imported imported, $duplicates duplicates, $unmatched unmatched BIO IDs, $invalid invalid records.";
        Session::flash('success', $summary);
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/attendance");
        exit;
    }

    public function quick()
    {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $mode = $this->getAttendanceMode();
        if (!in_array($mode, ['qrcode', 'link'], true)) {
            Session::flash('error', 'Quick attendance is disabled. Change Attendance Mode in Settings.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }
        $this->ensureAttendanceSchema();

        $serviceDate = trim((string)($_GET['service_date'] ?? date('Y-m-d')));
        $serviceType = trim((string)($_GET['service_type'] ?? 'Sunday Service'));
        if ($serviceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
            $serviceDate = date('Y-m-d');
        }
        if ($serviceType === '') {
            $serviceType = 'Sunday Service';
        }
        $serviceType = mb_substr($serviceType, 0, 100);

        View::render('attendance.quick', [
            'title' => 'Quick Attendance',
            'attendance_mode' => $mode,
            'service_date' => $serviceDate,
            'service_type' => $serviceType
        ]);
    }

    public function quickMark()
    {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $mode = $this->getAttendanceMode();
        if (!in_array($mode, ['qrcode', 'link'], true)) {
            Session::flash('error', 'Quick attendance is disabled. Change Attendance Mode in Settings.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }
        $this->ensureAttendanceSchema();

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $serviceDate = trim((string)($_POST['service_date'] ?? ''));
        $serviceType = trim((string)($_POST['service_type'] ?? ''));
        $code = strtoupper(trim((string)($_POST['member_code'] ?? '')));

        if ($serviceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
            Session::flash('error', 'Invalid service date.');
            $this->redirectQuick($serviceDate, $serviceType);
        }
        if ($serviceType === '') {
            Session::flash('error', 'Service type is required.');
            $this->redirectQuick($serviceDate, $serviceType);
        }
        $serviceType = mb_substr($serviceType, 0, 100);

        if ($code === '') {
            Session::flash('error', 'Enter a Member Code or Bio ID.');
            $this->redirectQuick($serviceDate, $serviceType);
        }

        $memberModel = new Member();
        $member = $memberModel->findByBioId($code);
        if (!$member) {
            $member = $memberModel->findByMemberCode($code);
        }
        if (!$member) {
            Session::flash('error', 'Member not found for: ' . $code);
            $this->redirectQuick($serviceDate, $serviceType);
        }

        $memberId = (int)($member['id'] ?? 0);
        if ($memberId <= 0) {
            Session::flash('error', 'Member not found.');
            $this->redirectQuick($serviceDate, $serviceType);
        }

        $attendanceModel = new Attendance();
        if ($attendanceModel->existsForMemberService($memberId, $serviceDate, $serviceType)) {
            Session::flash('warning', 'Already marked present: ' . trim((string)($member['first_name'] ?? '') . ' ' . (string)($member['last_name'] ?? '')));
            $this->redirectQuick($serviceDate, $serviceType);
        }

        $attendanceModel->create([
            'member_id' => $memberId,
            'service_date' => $serviceDate,
            'service_type' => $serviceType,
            'status' => 'Present',
            'source' => $mode,
            'bio_id' => trim((string)($member['bio_id'] ?? '')) !== '' ? strtoupper(trim((string)($member['bio_id'] ?? ''))) : null,
            'imported_at' => date('Y-m-d H:i:s')
        ]);

        Session::flash('success', 'Marked present: ' . trim((string)($member['first_name'] ?? '') . ' ' . (string)($member['last_name'] ?? '')));
        $this->redirectQuick($serviceDate, $serviceType);
    }

    public function pushOnline()
    {
        $this->isAdmin();
        $this->ensureAttendanceSchema();

        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== 'POST') {
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $cloudUrl = rtrim(trim((string)AppConfig::getSetting('attendance_cloud_url', '')), '/');
        $token = trim((string)AppConfig::getSetting('attendance_cloud_token', ''));
        if ($cloudUrl === '' || $token === '') {
            Session::flash('error', 'Online push is not configured. Set Online Base URL and Sync Token in Settings → Attendance.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }
        if (!function_exists('curl_init')) {
            Session::flash('error', 'cURL is not available on this server. Enable PHP curl extension.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $serviceDate = trim((string)($_POST['service_date'] ?? ''));
        $serviceType = trim((string)($_POST['service_type'] ?? ''));
        if ($serviceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
            Session::flash('error', 'Invalid service date.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }
        if ($serviceType === '') {
            Session::flash('error', 'Service type is required.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }
        $serviceType = mb_substr($serviceType, 0, 100);

        $attendanceModel = new Attendance();
        $rows = $attendanceModel->getForServiceWithMember($serviceDate, $serviceType);
        if (empty($rows)) {
            Session::flash('warning', 'No attendance records found for this service to push.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $records = [];
        foreach ($rows as $r) {
            $memberCode = trim((string)($r['member_code'] ?? ''));
            $bioId = trim((string)($r['bio_id'] ?? ''));
            $status = trim((string)($r['status'] ?? 'Present'));
            $source = trim((string)($r['source'] ?? 'manual'));
            $records[] = [
                'member_code' => $memberCode !== '' ? $memberCode : null,
                'bio_id' => $bioId !== '' ? $bioId : null,
                'service_date' => (string)$serviceDate,
                'service_type' => (string)$serviceType,
                'status' => $status !== '' ? $status : 'Present',
                'source' => $source !== '' ? $source : 'manual',
                'device_time' => $r['device_time'] ?? null,
                'device_serial' => $r['device_serial'] ?? null,
                'punch_type' => $r['punch_type'] ?? null,
                'imported_at' => $r['imported_at'] ?? null,
            ];
        }

        $payload = json_encode([
            'service_date' => $serviceDate,
            'service_type' => $serviceType,
            'sent_at' => date('c'),
            'records' => $records
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($payload)) {
            Session::flash('error', 'Failed to encode payload.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $url = $cloudUrl . '/api/attendance/import';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'X-Sync-Token: ' . $token,
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $err = curl_error($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            Session::flash('error', 'Online push failed: ' . $err);
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $json = json_decode((string)$body, true);
        if ($code < 200 || $code >= 300 || !is_array($json) || empty($json['ok'])) {
            $msg = '';
            if (is_array($json)) {
                $msg = trim((string)($json['error'] ?? $json['message'] ?? ''));
            }
            if ($msg === '') {
                $msg = 'Online push failed (HTTP ' . $code . ').';
            }
            Session::flash('error', $msg);
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $imported = (int)($json['imported'] ?? 0);
        $duplicates = (int)($json['duplicates'] ?? 0);
        $unmatched = (int)($json['unmatched'] ?? 0);
        $resultText = "Pushed to online: $imported imported, $duplicates duplicates, $unmatched unmatched.";
        Session::flash('attendance_cloud_last_result', $resultText);

        try {
            $db = Database::getInstance();
            $exists = $db->fetch("SELECT id FROM settings WHERE key_name = ? LIMIT 1", ['attendance_cloud_last_pushed_at']);
            if ($exists) {
                $db->query("UPDATE settings SET value = ? WHERE key_name = ?", [date('c'), 'attendance_cloud_last_pushed_at']);
            } else {
                $db->query("INSERT INTO settings (key_name, value) VALUES (?, ?)", ['attendance_cloud_last_pushed_at', date('c')]);
            }
            AppConfig::reset();
        } catch (Throwable $e) {
        }

        Session::flash('success', 'Online push completed.');
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/attendance");
        exit;
    }

    private function redirectQuick(string $serviceDate, string $serviceType): void
    {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        $qs = http_build_query([
            'service_date' => $serviceDate !== '' ? $serviceDate : date('Y-m-d'),
            'service_type' => $serviceType !== '' ? $serviceType : 'Sunday Service',
        ]);
        header("Location: $base/attendance/quick?$qs");
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

    private function getAttendanceMode(): string
    {
        $mode = strtolower(trim((string)AppConfig::getSetting('attendance_mode', 'manual')));
        if (!in_array($mode, ['manual', 'biotime', 'qrcode', 'link'], true)) {
            $mode = 'manual';
        }
        return $mode;
    }

    private function isBioTimeConfigured(): bool
    {
        $url = $this->getBioTimeUrl();
        if ($url === '') {
            return false;
        }
        if ($this->getBioTimeToken() !== '') {
            return true;
        }
        return ($this->getBioTimeUsername() !== '' && $this->getBioTimePassword() !== '');
    }

    private function getBioTimeUrl(): string
    {
        $v = rtrim(trim((string)AppConfig::getSetting('attendance_biotime_url', '')), '/');
        if ($v !== '') {
            return $v;
        }
        return rtrim(trim((string)Env::get('BIOTIME_URL', '')), '/');
    }

    private function getBioTimeUsername(): string
    {
        $v = trim((string)AppConfig::getSetting('attendance_biotime_username', ''));
        if ($v !== '') {
            return $v;
        }
        return trim((string)Env::get('BIOTIME_USERNAME', ''));
    }

    private function getBioTimePassword(): string
    {
        $v = trim((string)AppConfig::getSetting('attendance_biotime_password', ''));
        if ($v !== '') {
            return $v;
        }
        return trim((string)Env::get('BIOTIME_PASSWORD', ''));
    }

    private function getBioTimeTimezone(): string
    {
        $v = trim((string)AppConfig::getSetting('attendance_biotime_tz', ''));
        if ($v !== '') {
            return $v;
        }
        return trim((string)Env::get('BIOTIME_TZ', 'Africa/Accra'));
    }

    private function getBioTimeToken(): string
    {
        $v = trim((string)AppConfig::getSetting('attendance_biotime_token', ''));
        if ($v !== '') {
            return $v;
        }
        return trim((string)Env::get('BIOTIME_TOKEN', ''));
    }

    private function biotimeGetToken(string $baseUrl, string $username, string $password): string
    {
        $baseUrl = rtrim($baseUrl, '/');
        $payload = json_encode(['username' => $username, 'password' => $password]);
        if (!is_string($payload)) {
            return '';
        }

        $paths = ['/jwt-api-token-auth/', '/api-token-auth/'];
        foreach ($paths as $path) {
            $res = $this->biotimeHttpJson(
                'POST',
                $baseUrl . $path,
                ['Content-Type: application/json', 'Accept: application/json'],
                $payload
            );
            if (!($res['ok'] ?? false)) {
                continue;
            }
            $json = $res['json'] ?? null;
            if (is_array($json)) {
                $token = trim((string)($json['token'] ?? $json['access'] ?? ''));
                if ($token !== '') {
                    return $token;
                }
            }
        }

        return '';
    }

    private function biotimeFetchTransactions(string $baseUrl, string $token, string $startTime, string $endTime): array
    {
        $baseUrl = rtrim($baseUrl, '/');
        $token = trim($token);
        $headers = [
            'Accept: application/json',
            'Authorization: JWT ' . $token
        ];

        $firstUrl = $baseUrl . '/iclock/api/transactions/?page_size=1000&start_time=' . rawurlencode($startTime) . '&end_time=' . rawurlencode($endTime);
        $items = [];
        $next = $firstUrl;

        for ($i = 0; $i < 30; $i++) {
            if (!$next) {
                break;
            }

            $res = $this->biotimeHttpJson('GET', $next, $headers, null);
            if (($res['status'] ?? 0) === 401) {
                $headers = [
                    'Accept: application/json',
                    'Authorization: Bearer ' . $token
                ];
                $res = $this->biotimeHttpJson('GET', $next, $headers, null);
            }

            if (!($res['ok'] ?? false)) {
                $err = trim((string)($res['error'] ?? ''));
                if ($err === '') {
                    $err = 'BioTime request failed.';
                }
                return ['ok' => false, 'error' => $err, 'status' => (int)($res['status'] ?? 0)];
            }

            $json = $res['json'] ?? null;
            $pageItems = [];
            $nextUrl = null;

            if (is_array($json)) {
                if (isset($json['data']) && is_array($json['data'])) {
                    $pageItems = $json['data'];
                } elseif (isset($json['results']) && is_array($json['results'])) {
                    $pageItems = $json['results'];
                }
                $nextUrl = $json['next'] ?? null;
            } elseif (is_array($json) || is_object($json)) {
                $pageItems = (array)$json;
            }

            foreach ($pageItems as $row) {
                if (is_array($row)) {
                    $items[] = $row;
                }
            }

            if (is_string($nextUrl) && trim($nextUrl) !== '') {
                $nextUrl = trim($nextUrl);
                if (strpos($nextUrl, 'http://') === 0 || strpos($nextUrl, 'https://') === 0) {
                    $next = $nextUrl;
                } else {
                    $next = $baseUrl . '/' . ltrim($nextUrl, '/');
                }
            } else {
                $next = null;
            }
        }

        return ['ok' => true, 'items' => $items];
    }

    private function biotimeParseTransaction($tx): array
    {
        $bioId = '';
        $deviceTime = null;
        $deviceSerial = null;
        $punchType = null;

        if (is_array($tx)) {
            $bioId = (string)($tx['emp_code'] ?? $tx['emp_id'] ?? $tx['pin'] ?? '');

            if ($bioId === '' && isset($tx['employee']) && is_array($tx['employee'])) {
                $bioId = (string)($tx['employee']['emp_code'] ?? $tx['employee']['emp_id'] ?? $tx['employee']['id'] ?? '');
            }

            $timeRaw = (string)($tx['punch_time'] ?? $tx['checktime'] ?? $tx['timestamp'] ?? $tx['time'] ?? '');
            $serialRaw = (string)($tx['terminal_sn'] ?? $tx['terminal'] ?? $tx['device_sn'] ?? '');
            $punchRaw = (string)($tx['punch_state'] ?? $tx['punch_type'] ?? $tx['state'] ?? '');

            $deviceSerial = trim($serialRaw) !== '' ? trim($serialRaw) : null;
            $punchType = trim($punchRaw) !== '' ? trim($punchRaw) : null;

            $timeRaw = trim($timeRaw);
            if ($timeRaw !== '') {
                try {
                    $dt = new DateTimeImmutable($timeRaw);
                    $deviceTime = $dt->format('Y-m-d H:i:s');
                } catch (Throwable $e) {
                    $deviceTime = null;
                }
            }
        }

        $bioId = trim($bioId);
        return [
            'bio_id' => $bioId,
            'device_time' => $deviceTime,
            'device_serial' => $deviceSerial,
            'punch_type' => $punchType
        ];
    }

    private function biotimeHttpJson(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $ch = curl_init();
        if ($ch === false) {
            return ['ok' => false, 'status' => 0, 'error' => 'Failed to init cURL.'];
        }

        $method = strtoupper(trim($method));
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_CONNECTTIMEOUT => 12
        ];

        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = $body ?? '';
        } else {
            $opts[CURLOPT_HTTPGET] = true;
        }

        if (!empty($headers)) {
            $opts[CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $err = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        if ($raw === false) {
            return ['ok' => false, 'status' => $status, 'error' => $err ?: 'cURL request failed.'];
        }

        $bodyStr = substr($raw, $headerSize);
        $json = json_decode((string)$bodyStr, true);

        if ($status < 200 || $status >= 300) {
            $msg = 'BioTime HTTP ' . $status;
            if (is_array($json) && isset($json['detail'])) {
                $msg .= ': ' . (string)$json['detail'];
            }
            return ['ok' => false, 'status' => $status, 'error' => $msg, 'body' => $bodyStr, 'json' => $json];
        }

        if ($json === null && trim((string)$bodyStr) !== '' && strtolower(trim((string)$bodyStr)) !== 'null') {
            return ['ok' => false, 'status' => $status, 'error' => 'BioTime returned non-JSON response.', 'body' => $bodyStr];
        }

        return ['ok' => true, 'status' => $status, 'body' => $bodyStr, 'json' => $json];
    }
}
