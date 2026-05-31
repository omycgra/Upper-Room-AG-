<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Attendance.php';
require_once __DIR__ . '/../Models/Member.php';
require_once __DIR__ . '/../Models/Department.php';

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
        $customPublicUrl = rtrim(trim((string)AppConfig::getSetting('attendance_custom_public_url', '')), '/');

        $serviceDate = trim((string)($_GET['service_date'] ?? date('Y-m-d')));
        $serviceType = trim((string)($_GET['service_type'] ?? 'Sunday Service'));
        $departmentId = (int)($_GET['department_id'] ?? 0);
        if ($departmentId <= 0) {
            $departmentId = 0;
        }
        if ($serviceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
            $serviceDate = date('Y-m-d');
        }
        if ($serviceType === '') {
            $serviceType = 'Sunday Service';
        }
        $serviceType = mb_substr($serviceType, 0, 100);

        $dailyReport = $this->buildServiceAttendanceReport($serviceDate, $serviceType, $departmentId > 0 ? $departmentId : null);
        $deptModel = new Department();
        $departments = $deptModel->all('name ASC');

        $recent = $attendanceModel->getRecentWithMemberForServiceDate(date('Y-m-d'), 50);
        foreach ($recent as &$r) {
            $checkInRaw = trim((string)($r['device_time'] ?? ''));
            if ($checkInRaw === '') {
                $checkInRaw = trim((string)($r['imported_at'] ?? ''));
            }
            $r['computed_status'] = $this->computeAttendanceStatus($r['service_date'] ?? '', $checkInRaw !== '' ? $checkInRaw : null, $r['service_type'] ?? 'Sunday Service');
        }
        unset($r);
        
        $formattedServiceTimes = $this->getFormattedServiceTimes($serviceType);

        View::render('attendance.index', [
            'title' => 'Attendance Management',
            'attendance_rate' => $attendanceModel->getAttendanceRate(),
            'recent_records' => $recent,
            'attendance_mode' => $mode,
            'biotime_configured' => $biotimeConfigured,
            'biotime_url' => $this->getBioTimeUrl(),
            'cloud_configured' => $cloudConfigured,
            'cloud_url' => $cloudUrl,
            'cloud_last_pushed_at' => $cloudLastPushedAt,
            'cloud_last_result' => (string)Session::flash('attendance_cloud_last_result'),
            'service_date' => $serviceDate,
            'service_type' => $serviceType,
            'department_id' => $departmentId,
            'departments' => $departments,
            'daily_report' => $dailyReport,
            'can_manage_attendance' => Auth::isAdmin(),
            'can_download_attendance' => (Auth::isAdmin() || Auth::isPastor() || Auth::isVisitationTeam()),
            'attendance_page_route' => 'attendance',
            'formatted_service_times' => $formattedServiceTimes,
            'custom_public_url' => $customPublicUrl
        ]);
    }

    public function view()
    {
        if (!Auth::isPastor() && !Auth::isVisitationTeam() && !Auth::isAdmin()) {
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
        $customPublicUrl = rtrim(trim((string)AppConfig::getSetting('attendance_custom_public_url', '')), '/');

        $serviceDate = trim((string)($_GET['service_date'] ?? date('Y-m-d')));
        $serviceType = trim((string)($_GET['service_type'] ?? 'Sunday Service'));
        $departmentId = (int)($_GET['department_id'] ?? 0);
        if ($departmentId <= 0) {
            $departmentId = 0;
        }
        if ($serviceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
            $serviceDate = date('Y-m-d');
        }
        if ($serviceType === '') {
            $serviceType = 'Sunday Service';
        }
        $serviceType = mb_substr($serviceType, 0, 100);

        $dailyReport = $this->buildServiceAttendanceReport($serviceDate, $serviceType, $departmentId > 0 ? $departmentId : null);
        $deptModel = new Department();
        $departments = $deptModel->all('name ASC');

        $recent = $attendanceModel->getRecentWithMemberForServiceDate(date('Y-m-d'), 50);
        foreach ($recent as &$r) {
            $checkInRaw = trim((string)($r['device_time'] ?? ''));
            if ($checkInRaw === '') {
                $checkInRaw = trim((string)($r['imported_at'] ?? ''));
            }
            $r['computed_status'] = $this->computeAttendanceStatus($r['service_date'] ?? '', $checkInRaw !== '' ? $checkInRaw : null, $r['service_type'] ?? 'Sunday Service');
        }
        unset($r);

        $formattedServiceTimes = $this->getFormattedServiceTimes($serviceType);

        View::render('attendance.index', [
            'title' => 'Attendance',
            'attendance_rate' => $attendanceModel->getAttendanceRate(),
            'today_attendance_rate' => $attendanceModel->getTodayAttendanceRate(),
            'weekly_attendance_rate' => $attendanceModel->getWeeklyAttendanceRate(),
            'monthly_attendance_rate' => $attendanceModel->getMonthlyAttendanceRate(),
            'recent_records' => $recent,
            'attendance_mode' => $mode,
            'biotime_configured' => $biotimeConfigured,
            'biotime_url' => $this->getBioTimeUrl(),
            'cloud_configured' => $cloudConfigured,
            'cloud_url' => $cloudUrl,
            'cloud_last_pushed_at' => $cloudLastPushedAt,
            'cloud_last_result' => '',
            'service_date' => $serviceDate,
            'service_type' => $serviceType,
            'department_id' => $departmentId,
            'departments' => $departments,
            'daily_report' => $dailyReport,
            'can_manage_attendance' => false,
            'can_download_attendance' => true,
            'attendance_page_route' => 'attendance/view',
            'formatted_service_times' => $formattedServiceTimes
        ]);
    }

    public function department()
    {
        if (!Auth::isDepartmentHead()) {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }

        $myDeptId = (int)(Session::get('user_department_id') ?? 0);
        if ($myDeptId <= 0) {
            Session::flash('error', 'Department access is not configured for this account.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }

        $this->ensureAttendanceSchema();
        $attendanceModel = new Attendance();

        $serviceDate = trim((string)($_GET['service_date'] ?? date('Y-m-d')));
        $serviceType = trim((string)($_GET['service_type'] ?? 'Sunday Service'));
        if ($serviceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
            $serviceDate = date('Y-m-d');
        }
        if ($serviceType === '') {
            $serviceType = 'Sunday Service';
        }
        $serviceType = mb_substr($serviceType, 0, 100);

        $deptModel = new Department();
        $departments = $deptModel->all('name ASC');
        $dailyReport = $this->buildServiceAttendanceReport($serviceDate, $serviceType, $myDeptId);

        $formattedServiceTimes = $this->getFormattedServiceTimes($serviceType);

        View::render('attendance.index', [
            'title' => 'Attendance',
            'attendance_rate' => $attendanceModel->getAttendanceRate(),
            'today_attendance_rate' => $attendanceModel->getTodayAttendanceRate(),
            'weekly_attendance_rate' => $attendanceModel->getWeeklyAttendanceRate(),
            'monthly_attendance_rate' => $attendanceModel->getMonthlyAttendanceRate(),
            'recent_records' => [],
            'attendance_mode' => $this->getAttendanceMode(),
            'biotime_configured' => false,
            'biotime_url' => '',
            'cloud_configured' => false,
            'cloud_url' => '',
            'cloud_last_pushed_at' => '',
            'cloud_last_result' => '',
            'service_date' => $serviceDate,
            'service_type' => $serviceType,
            'department_id' => $myDeptId,
            'departments' => $departments,
            'daily_report' => $dailyReport,
            'can_manage_attendance' => true,
            'can_download_attendance' => true,
            'attendance_page_route' => 'attendance/department',
            'formatted_service_times' => $formattedServiceTimes
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
        $db = Database::getInstance();
        $deptModel = new Department();
        $departments = $deptModel->all('name ASC');
        $departmentId = (int)($_GET['department_id'] ?? 0);
        if ($departmentId <= 0) {
            $departmentId = 0;
        }

        $hasMemberDepartments = $db->tableExists('member_departments');
        $sql = "SELECT
                    m.id,
                    m.member_code,
                    m.bio_id,
                    m.photo_path,
                    m.first_name,
                    m.last_name,
                    d.name AS department_name
                FROM members m
                LEFT JOIN departments d ON m.department_id = d.id
                WHERE 1=1";
        $params = [];
        if ($departmentId > 0) {
            if ($hasMemberDepartments) {
                $sql .= " AND (
                            m.department_id = ?
                            OR EXISTS (
                                SELECT 1
                                FROM member_departments md
                                WHERE md.member_id = m.id
                                  AND md.department_id = ?
                            )
                        )";
                $params[] = $departmentId;
                $params[] = $departmentId;
            } else {
                $sql .= " AND m.department_id = ?";
                $params[] = $departmentId;
            }
        }
        $sql .= " ORDER BY m.first_name ASC, m.last_name ASC";
        $members = $db->fetchAll($sql, $params) ?: [];
        $members = $this->attachDepartmentNames($members);
        View::render('attendance.mark', [
            'title' => 'Mark Attendance',
            'members' => $members,
            'departments' => $departments,
            'department_id' => $departmentId
        ]);
    }

    public function store() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        if (!Auth::isAdmin()) {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
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
        $tz = $this->resolveAttendanceTimezone();
        $deviceTime = (new DateTimeImmutable($serviceDate . ' 08:00:00', $tz))->format('Y-m-d H:i:s');
        $status = $this->computeAttendanceStatus($serviceDate, $deviceTime, $serviceType);
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
                'status' => $status,
                'source' => 'manual',
                'device_time' => $deviceTime,
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
        if (!Auth::isAdmin()) {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
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

        $bestByMemberId = [];
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

            $deviceTime = trim((string)($parsed['device_time'] ?? ''));
            if ($deviceTime === '') {
                $invalid++;
                continue;
            }

            if (!isset($bestByMemberId[$memberId])) {
                $bestByMemberId[$memberId] = ['tx' => $tx, 'parsed' => $parsed, 'bio_id' => $bioId];
            } else {
                $bestTime = (string)($bestByMemberId[$memberId]['parsed']['device_time'] ?? '');
                if ($bestTime === '' || strcmp($deviceTime, $bestTime) < 0) {
                    $bestByMemberId[$memberId] = ['tx' => $tx, 'parsed' => $parsed, 'bio_id' => $bioId];
                }
            }
        }

        foreach ($bestByMemberId as $memberId => $picked) {
            $memberId = (int)$memberId;
            if ($attendanceModel->existsForMemberService($memberId, $serviceDate, $serviceType)) {
                $duplicates++;
                continue;
            }

            $parsed = (array)($picked['parsed'] ?? []);
            $bioId = (string)($picked['bio_id'] ?? '');
            $payload = json_encode($picked['tx'] ?? null, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (!is_string($payload)) {
                $payload = null;
            }

            $deviceTime = trim((string)($parsed['device_time'] ?? ''));
            $status = $this->computeAttendanceStatus($serviceDate, $deviceTime !== '' ? $deviceTime : null, $serviceType);

            $attendanceModel->create([
                'member_id' => $memberId,
                'service_date' => $serviceDate,
                'service_type' => $serviceType,
                'status' => $status,
                'source' => 'biotime',
                'bio_id' => $bioId,
                'device_time' => $deviceTime !== '' ? $deviceTime : null,
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
        $mode = $this->getAttendanceMode();
        if (!in_array($mode, ['qrcode', 'link'], true)) {
            $mode = 'link';
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
        ], false);
    }

    public function quickMark()
    {
        $mode = $this->getAttendanceMode();
        if (!in_array($mode, ['qrcode', 'link'], true)) {
            $mode = 'link';
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
            Session::flash('warning', 'Already marked: ' . trim((string)($member['first_name'] ?? '') . ' ' . (string)($member['last_name'] ?? '')));
            $this->redirectQuick($serviceDate, $serviceType);
        }

        $tz = $this->resolveAttendanceTimezone();
        $now = new DateTimeImmutable('now', $tz);
        $checkIn = new DateTimeImmutable($serviceDate . ' ' . $now->format('H:i:s'), $tz);
        $deviceTime = $checkIn->format('Y-m-d H:i:s');
        $status = $this->computeAttendanceStatus($serviceDate, $deviceTime, $serviceType);

        $attendanceModel->create([
            'member_id' => $memberId,
            'service_date' => $serviceDate,
            'service_type' => $serviceType,
            'status' => $status,
            'source' => $mode,
            'bio_id' => trim((string)($member['bio_id'] ?? '')) !== '' ? strtoupper(trim((string)($member['bio_id'] ?? ''))) : null,
            'device_time' => $deviceTime,
            'imported_at' => date('Y-m-d H:i:s')
        ]);

        Session::flash('success', 'Marked ' . strtolower($status) . ': ' . trim((string)($member['first_name'] ?? '') . ' ' . (string)($member['last_name'] ?? '')));
        $this->redirectQuick($serviceDate, $serviceType);
    }

    public function download()
    {
        if (!Auth::isAdmin() && !Auth::isPastor() && !Auth::isVisitationTeam() && !Auth::isDepartmentHead()) {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/attendance");
            exit;
        }

        $serviceDate = trim((string)($_GET['service_date'] ?? ''));
        $serviceType = trim((string)($_GET['service_type'] ?? ''));
        $departmentId = (int)($_GET['department_id'] ?? 0);
        if ($departmentId <= 0) {
            $departmentId = 0;
        }
        if (Auth::isDepartmentHead() && !Auth::isAdmin()) {
            $myDeptId = (int)(Session::get('user_department_id') ?? 0);
            if ($myDeptId <= 0) {
                Session::flash('error', 'Department access is not configured for this account.');
                $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                header("Location: $base/dashboard");
                exit;
            }
            $departmentId = $myDeptId;
        }
        $statusFilter = strtolower(trim((string)($_GET['status'] ?? 'all')));
        if (!in_array($statusFilter, ['all', 'present', 'late', 'absent'], true)) {
            $statusFilter = 'all';
        }
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

        $this->ensureAttendanceSchema();
        $report = $this->buildServiceAttendanceReport($serviceDate, $serviceType, $departmentId > 0 ? $departmentId : null);
        $rows = $report['rows'] ?? [];

        $deptPart = $departmentId > 0 ? ('dept_' . $departmentId . '_') : '';
        $filename = 'attendance_' . $deptPart . preg_replace('/[^a-zA-Z0-9_-]+/', '_', strtolower($serviceType)) . '_' . $serviceDate . '_' . $statusFilter . '_' . date('His') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if (!$out) {
            exit;
        }

        fputcsv($out, [
            'member code',
            'bio id',
            'name',
            'gender',
            'department name',
            'group name',
            'stays at',
            'address',
            'phone',
            'attendance status',
            'check-in time',
            'source'
        ]);

        foreach ($rows as $row) {
            $status = trim((string)($row['status'] ?? 'Absent'));
            $statusKey = strtolower($status);
            if ($statusFilter !== 'all' && $statusKey !== $statusFilter) {
                continue;
            }

            $m = is_array($row['member'] ?? null) ? $row['member'] : [];
            $memberCode = trim((string)($m['member_code'] ?? ''));
            $bioId = trim((string)($m['bio_id'] ?? ''));
            $name = trim((string)($m['first_name'] ?? '') . ' ' . (string)($m['last_name'] ?? ''));
            $gender = trim((string)($m['gender'] ?? ''));
            $departmentName = trim((string)($m['department_name'] ?? ''));
            $groupName = trim((string)($m['group_name'] ?? ''));
            $staysAt = trim((string)($m['stays_at'] ?? ''));
            $address = trim((string)($m['address'] ?? ''));
            $phone = trim((string)($m['phone'] ?? ''));

            $checkIn = trim((string)($row['check_in'] ?? ''));
            $checkInTime = '';
            if ($checkIn !== '') {
                $checkInTime = date('H:i:s', strtotime($checkIn));
            }

            fputcsv($out, [
                $memberCode,
                $bioId,
                $name,
                $gender,
                $departmentName,
                $groupName,
                $staysAt,
                $address,
                $phone,
                $status,
                $checkInTime,
                (string)($row['source'] ?? '')
            ]);
        }

        fclose($out);
        exit;
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

        SchemaState::once('attendance_schema_v2', function () use ($db) {
            // Update status ENUM to include Late
            if (!$db->isPgsql()) {
                $db->query("ALTER TABLE attendance MODIFY COLUMN status ENUM('Present', 'Absent', 'Excused', 'Late') DEFAULT 'Present'");
            }

            // Update service_type ENUM to include new options
            if (!$db->isPgsql()) {
                $db->query("ALTER TABLE attendance MODIFY COLUMN service_type ENUM('Sunday Service', 'Mid-week Service', 'Midweek Service', 'Youth Meeting', 'Youth Service', 'Children Service', 'Special Event') DEFAULT 'Sunday Service'");
            }
        });
    }

    private function resolveAttendanceTimezone(): DateTimeZone
    {
        $tzName = $this->getBioTimeTimezone();
        try {
            return new DateTimeZone($tzName !== '' ? $tzName : 'Africa/Accra');
        } catch (Throwable $e) {
            return new DateTimeZone('Africa/Accra');
        }
    }

    private function computeAttendanceStatus(string $serviceDate, ?string $checkInDateTime, string $serviceType = 'Sunday Service'): string
    {
        $serviceDate = trim($serviceDate);
        if ($serviceDate === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $serviceDate)) {
            return 'Absent';
        }
        $checkInDateTime = $checkInDateTime !== null ? trim($checkInDateTime) : null;
        if ($checkInDateTime === null || $checkInDateTime === '') {
            return 'Absent';
        }

        // Backwards compatibility: map old service type names to new ones
        $serviceTypeMap = [
            'Mid-week Service' => 'Midweek Service',
            'Youth Meeting' => 'Youth Service',
            'Special Event' => 'Sunday Service' // default to Sunday Service for Special Event
        ];
        $serviceType = $serviceTypeMap[$serviceType] ?? $serviceType;

        $tz = $this->resolveAttendanceTimezone();
        try {
            $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $checkInDateTime, $tz);
            if ($dt === false) {
                $dt = new DateTimeImmutable($checkInDateTime, $tz);
            }
            $dt = $dt->setTimezone($tz);
        } catch (Throwable $e) {
            return 'Absent';
        }

        // Get service time configuration
        $serviceTimesJson = AppConfig::getSetting('attendance_service_times', '{}');
        $serviceTimes = json_decode($serviceTimesJson, true);
        if (!is_array($serviceTimes)) $serviceTimes = [];
        
        // Default times if no config
        $presentStartTime = '08:00:00';
        $presentEndTime = '10:30:59';
        $lateStartTime = '10:31:00';
        $lateEndTime = '12:00:00';
        
        if (isset($serviceTimes[$serviceType])) {
            $config = $serviceTimes[$serviceType];
            if (isset($config['present_start'])) {
                $presentStartTime = $config['present_start'] . ':00';
            }
            if (isset($config['present_end'])) {
                $presentEndTime = $config['present_end'] . ':59';
            }
            if (isset($config['late_start'])) {
                $lateStartTime = $config['late_start'] . ':00';
            }
            if (isset($config['late_end'])) {
                $lateEndTime = $config['late_end'] . ':00';
            }
        }

        $presentStart = new DateTimeImmutable($serviceDate . ' ' . $presentStartTime, $tz);
        $presentEnd = new DateTimeImmutable($serviceDate . ' ' . $presentEndTime, $tz);
        $lateStart = new DateTimeImmutable($serviceDate . ' ' . $lateStartTime, $tz);
        $lateEnd = new DateTimeImmutable($serviceDate . ' ' . $lateEndTime, $tz);

        if ($dt > $lateEnd) {
            return 'Absent';
        }
        if ($dt >= $presentStart && $dt <= $presentEnd) {
            return 'Present';
        }
        // If after present end but before or at late end, it's Late
        if ($dt > $presentEnd && $dt <= $lateEnd) {
            return 'Late';
        }
        return 'Absent';
    }

    private function getFormattedServiceTimes(string $serviceType): array
    {
        // Backwards compatibility: map old service type names to new ones
        $serviceTypeMap = [
            'Mid-week Service' => 'Midweek Service',
            'Youth Meeting' => 'Youth Service',
            'Special Event' => 'Sunday Service'
        ];
        $serviceType = $serviceTypeMap[$serviceType] ?? $serviceType;

        // Get service time configuration
        $serviceTimesJson = AppConfig::getSetting('attendance_service_times', '{}');
        $serviceTimes = json_decode($serviceTimesJson, true);
        if (!is_array($serviceTimes)) $serviceTimes = [];

        // Default times if no config
        $presentStart = '07:00';
        $presentEnd = '10:30';
        $lateStart = '10:31';
        $lateEnd = '12:00';

        if (isset($serviceTimes[$serviceType])) {
            $config = $serviceTimes[$serviceType];
            $presentStart = $config['present_start'] ?? $presentStart;
            $presentEnd = $config['present_end'] ?? $presentEnd;
            $lateStart = $config['late_start'] ?? $lateStart;
            $lateEnd = $config['late_end'] ?? $lateEnd;
        }

        return [
            'present_start' => $presentStart,
            'present_end' => $presentEnd,
            'late_start' => $lateStart,
            'late_end' => $lateEnd
        ];
    }

    private function buildServiceAttendanceReport(string $serviceDate, string $serviceType, ?int $departmentId = null): array
    {
        $db = Database::getInstance();
        $hasMemberDepartments = $db->tableExists('member_departments');
        $sql = "SELECT m.id,
                       m.member_code,
                       m.bio_id,
                       m.photo_path,
                       m.first_name,
                       m.last_name,
                       m.gender,
                       m.stays_at,
                       m.address,
                       m.phone,
                       d.name AS department_name,
                       c.name AS group_name
                FROM members m
                LEFT JOIN departments d ON m.department_id = d.id
                LEFT JOIN clusters c ON m.cluster_id = c.id
                WHERE 1=1";
        $params = [];
        if (!empty($departmentId) && (int)$departmentId > 0) {
            if ($hasMemberDepartments) {
                $sql .= " AND (
                            m.department_id = ?
                            OR EXISTS (
                                SELECT 1
                                FROM member_departments md
                                WHERE md.member_id = m.id
                                  AND md.department_id = ?
                            )
                        )";
                $params[] = (int)$departmentId;
                $params[] = (int)$departmentId;
            } else {
                $sql .= " AND m.department_id = ?";
                $params[] = (int)$departmentId;
            }
        }
        $sql .= " ORDER BY m.last_name ASC, m.first_name ASC";
        $members = $db->fetchAll($sql, $params) ?: [];
        $members = $this->attachDepartmentNames($members);
        if (empty($members)) {
            return [
                'service_date' => $serviceDate,
                'service_type' => $serviceType,
                'counts' => ['present' => 0, 'late' => 0, 'absent' => 0, 'total' => 0],
                'rows' => []
            ];
        }

        // Backwards compatibility: map old service type names to new ones
        $serviceTypeMap = [
            'Mid-week Service' => 'Midweek Service',
            'Youth Meeting' => 'Youth Service',
            'Special Event' => 'Sunday Service'
        ];
        // Create reverse mapping: new service type → [old names]
        $reverseServiceTypeMap = [];
        foreach ($serviceTypeMap as $old => $new) {
            if (!isset($reverseServiceTypeMap[$new])) {
                $reverseServiceTypeMap[$new] = [];
            }
            $reverseServiceTypeMap[$new][] = $old;
        }
        $serviceTypesToFetch = [$serviceType];
        if (isset($reverseServiceTypeMap[$serviceType])) {
            $serviceTypesToFetch = array_merge($serviceTypesToFetch, $reverseServiceTypeMap[$serviceType]);
        }
        $serviceTypesToFetch = array_unique($serviceTypesToFetch);
        $placeholders = implode(',', array_fill(0, count($serviceTypesToFetch), '?'));
        $attendanceParams = [$serviceDate];
        $attendanceParams = array_merge($attendanceParams, $serviceTypesToFetch);
        $attendanceRows = $db->fetchAll(
            "SELECT a.*
             FROM attendance a
             WHERE a.service_date = ?
               AND a.service_type IN ($placeholders)",
            $attendanceParams
        ) ?: [];

        $attByMemberId = [];
        foreach ($attendanceRows as $a) {
            $mid = (int)($a['member_id'] ?? 0);
            if ($mid > 0 && !isset($attByMemberId[$mid])) {
                $attByMemberId[$mid] = $a;
            }
        }

        $present = 0;
        $late = 0;
        $absent = 0;
        $rows = [];

        foreach ($members as $m) {
            $mid = (int)($m['id'] ?? 0);
            if ($mid <= 0) {
                continue;
            }

            $a = $attByMemberId[$mid] ?? null;
            $source = '';
            $checkIn = '';
            $status = 'Absent';

            if (is_array($a)) {
                $source = trim((string)($a['source'] ?? ''));
                $checkIn = trim((string)($a['device_time'] ?? ''));
                if ($checkIn === '') {
                    $checkIn = trim((string)($a['imported_at'] ?? ''));
                }
                $status = $this->computeAttendanceStatus($serviceDate, $checkIn !== '' ? $checkIn : null, $serviceType);
            }

            if ($status === 'Present') $present++;
            elseif ($status === 'Late') $late++;
            else $absent++;

            $rows[] = [
                'status' => $status,
                'check_in' => $checkIn,
                'source' => $source,
                'member' => $m
            ];
        }

        return [
            'service_date' => $serviceDate,
            'service_type' => $serviceType,
            'counts' => [
                'present' => $present,
                'late' => $late,
                'absent' => $absent,
                'total' => $present + $late + $absent
            ],
            'rows' => $rows
        ];
    }

    private function attachDepartmentNames(array $members): array
    {
        if (empty($members)) {
            return $members;
        }

        $db = Database::getInstance();
        if (!$db->tableExists('member_departments')) {
            return $members;
        }

        $memberIds = [];
        foreach ($members as $m) {
            $id = (int)($m['id'] ?? 0);
            if ($id > 0) {
                $memberIds[] = $id;
            }
        }
        $memberIds = array_values(array_unique($memberIds));
        if (empty($memberIds)) {
            return $members;
        }

        $extraByMember = [];
        foreach (array_chunk($memberIds, 500) as $chunk) {
            $placeholders = implode(',', array_fill(0, count($chunk), '?'));
            $rows = $db->fetchAll(
                "SELECT md.member_id, d.name as department_name
                 FROM member_departments md
                 INNER JOIN departments d ON d.id = md.department_id
                 WHERE md.member_id IN ($placeholders)
                 ORDER BY md.member_id ASC, d.name ASC",
                $chunk
            ) ?: [];
            foreach ($rows as $r) {
                $mid = (int)($r['member_id'] ?? 0);
                $name = trim((string)($r['department_name'] ?? ''));
                if ($mid <= 0 || $name === '') {
                    continue;
                }
                if (!isset($extraByMember[$mid])) {
                    $extraByMember[$mid] = [];
                }
                $extraByMember[$mid][] = $name;
            }
        }

        foreach ($members as &$m) {
            $mid = (int)($m['id'] ?? 0);
            $primary = trim((string)($m['department_name'] ?? ''));
            $names = [];
            if ($primary !== '') {
                $names[] = $primary;
            }
            if ($mid > 0 && !empty($extraByMember[$mid])) {
                foreach ($extraByMember[$mid] as $n) {
                    $names[] = $n;
                }
            }
            $names = array_values(array_unique(array_filter(array_map('trim', $names))));
            if (!empty($names)) {
                $m['department_name'] = implode(', ', $names);
            }
        }
        unset($m);

        return $members;
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

    public function searchMembers()
    {
        $this->ensureAttendanceSchema();
        $term = trim((string)($_GET['q'] ?? ''));
        $memberModel = new Member();
        $members = $memberModel->searchAndFilter($term);
        
        $results = [];
        foreach ($members as $m) {
            $results[] = [
                'id' => (int)($m['id'] ?? 0),
                'first_name' => trim((string)($m['first_name'] ?? '')),
                'last_name' => trim((string)($m['last_name'] ?? '')),
                'member_code' => trim((string)($m['member_code'] ?? '')),
                'bio_id' => trim((string)($m['bio_id'] ?? ''))
            ];
        }
        
        header('Content-Type: application/json');
        echo json_encode($results);
        exit;
    }
}
