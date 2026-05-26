<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Member.php';
require_once __DIR__ . '/../Models/Cluster.php';
require_once __DIR__ . '/../Models/Department.php';

class MemberController extends BaseController {
    public function index() {
        $this->ensureMemberSchema();
        $memberModel = new Member();
        $deptModel = new Department();
        $clusterModel = new Cluster();
        
        $searchTerm = $_GET['search'] ?? '';
        $deptFilter = $_GET['department'] ?? '';
        $statusFilter = $_GET['status'] ?? '';
        $sort = $_GET['sort'] ?? '';
        $added = $_GET['added'] ?? '';

        if (Session::get('user_role') === 'dept_head') {
            $deptFilter = (string)(Session::get('user_department_id') ?? '');
        }
        
        $members = $memberModel->searchAndFilter($searchTerm, $deptFilter, $statusFilter, $sort, $added);
        $stats = $memberModel->getStats();
        $departments = $deptModel->all();
        $clusters = $clusterModel->all();
        
        View::render('members.index', [
            'title' => 'Members Directory',
            'members' => $members,
            'stats' => $stats,
            'departments' => $departments,
            'clusters' => $clusters,
            'filters' => [
                'search' => $searchTerm,
                'department' => $deptFilter,
                'status' => $statusFilter,
                'sort' => $sort,
                'added' => $added
            ]
        ]);
    }

    public function add() {
        if (!Auth::isAdmin() && Session::get('user_role') !== 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $this->ensureMemberSchema();
        $clusterModel = new Cluster();
        $deptModel = new Department();

        $isDeptHead = (Session::get('user_role') === 'dept_head');
        $myDeptId = $isDeptHead ? (int)(Session::get('user_department_id') ?? 0) : 0;
        
        View::render('members.add', [
            'title' => 'Add New Member',
            'clusters' => $clusterModel->all(),
            'departments' => $isDeptHead && $myDeptId > 0 ? $deptModel->where('id', $myDeptId) : $deptModel->all(),
            'isDeptHead' => $isDeptHead,
            'myDeptId' => $myDeptId
        ]);
    }

    public function store() {
        if (!Auth::isAdmin() && Session::get('user_role') !== 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $this->ensureMemberSchema();
        $memberModel = new Member();
        
        // Debug: Log raw POST data
        error_log("Raw POST Data: " . print_r($_POST, true));
        
        // Ensure values match database ENUMs (lowercase)
        $gender = strtolower($_POST['gender'] ?? 'male');
        $marital_status = strtolower($_POST['marital_status'] ?? 'single');
        $isBaptized = $this->toBooleanValue($_POST['is_baptized'] ?? false);
        $currentlyWorking = $this->toBooleanValue($_POST['currently_working'] ?? false);
        $primaryDepartmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $additionalDepartmentIds = $this->parseDepartmentIds($_POST['additional_department_ids'] ?? []);
        
        try {
            if (!$primaryDepartmentId && !empty($additionalDepartmentIds)) {
                throw new Exception('Select a primary department before adding additional departments.');
            }

            // Handle Photo Upload
            $photoPath = null;
            if (!empty($_FILES['photo']['name'])) {
                $photoPath = $this->handleUpload($_FILES['photo']);
                $photoPath = $this->normalizeUploadPath($photoPath);
            }

            $data = [
                'member_code' => $this->generateUniqueMemberCode(),
                'bio_id' => $this->resolveBioId($_POST['bio_id'] ?? ''),
                'first_name' => $_POST['first_name'] ?? 'Unknown',
                'last_name' => $_POST['last_name'] ?? 'Member',
                'email' => $_POST['email'] ?? null,
                'phone' => $_POST['phone'] ?? null,
                'nationality' => $_POST['nationality'] ?? null,
                'address' => $_POST['address'] ?? null,
                'stays_at' => $_POST['stays_at'] ?? null,
                'home_town' => $_POST['home_town'] ?? null,
                'gender' => $gender,
                'marital_status' => $marital_status,
                'spouse_name' => $_POST['spouse_name'] ?? null,
                'mother_name' => $_POST['mother_name'] ?? null,
                'father_name' => $_POST['father_name'] ?? null,
                'is_baptized' => $isBaptized,
                'baptism_pastor_church' => $_POST['baptism_pastor_church'] ?? null,
                'currently_working' => $currentlyWorking,
                'work_name' => $_POST['work_name'] ?? null,
                'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
                'position' => !empty($_POST['position']) ? $_POST['position'] : null,
                'cluster_id' => !empty($_POST['cluster_id']) ? (int)$_POST['cluster_id'] : null,
                'department_id' => $primaryDepartmentId,
                'membership_status' => $_POST['membership_status'] ?? 'Active',
                'join_date' => !empty($_POST['join_date']) ? $_POST['join_date'] : date('Y-m-d'),
                'photo_path' => $photoPath
            ];

            if (Session::get('user_role') === 'dept_head') {
                $myDeptId = (int)(Session::get('user_department_id') ?? 0);
                if ($myDeptId <= 0) {
                    throw new Exception('Department access is not configured for this account.');
                }
                $data['department_id'] = $myDeptId;
                $additionalDepartmentIds = [];
            }

            $existing = $this->findExistingMember($memberModel, $data);
            if ($existing) {
                $label = trim((string)($existing['first_name'] ?? '') . ' ' . (string)($existing['last_name'] ?? ''));
                $hint = '';
                if (!empty($existing['bio_id'])) {
                    $hint = 'BIO ID: ' . $existing['bio_id'];
                } elseif (!empty($existing['phone'])) {
                    $hint = 'PHONE: ' . $existing['phone'];
                } elseif (!empty($existing['member_code'])) {
                    $hint = 'CODE: ' . $existing['member_code'];
                }
                $msg = 'This member already exists' . ($label !== '' ? (': ' . $label) : '') . ($hint !== '' ? (' (' . $hint . ')') : '') . '.';
                Session::flash('error', $msg);
                $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                header("Location: $base/members/add");
                exit;
            }
            
            // Log the data being sent to the database
            error_log("Attempting to insert member with data: " . print_r($data, true));
            
            $conn = Database::getInstance()->getConnection();
            $conn->beginTransaction();
            $memberId = $memberModel->create($data);
            if ($memberId) {
                $memberModel->syncAdditionalDepartments($memberId, $additionalDepartmentIds, $data['department_id'] ?? null);
            }
            $conn->commit();
            
            if ($memberId) {
                AuditLog::log("Added member: " . $data['first_name'] . " " . $data['last_name'], "members", $memberId, null, $data);
                Session::flash('success', 'Member added successfully');
                $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                header("Location: $base/members");
            } else {
                throw new Exception("The system could not retrieve the new member's ID.");
            }
        } catch (Exception $e) {
            $conn = Database::getInstance()->getConnection();
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Member Creation Error: " . $e->getMessage());
            Session::flash('error', 'Database Error: ' . $e->getMessage());
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/members");
        }
        exit;
    }

    public function update() {
        $this->isAdmin();
        $this->ensureMemberSchema();
        $id = $_POST['id'] ?? null;
        if (!$id) {
            Session::flash('error', 'Member ID missing');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/members");
            exit;
        }

        $memberModel = new Member();
        $oldMember = $memberModel->find($id);

        $gender = strtolower($_POST['gender'] ?? 'male');
        $marital_status = strtolower($_POST['marital_status'] ?? 'single');
        $isBaptized = $this->toBooleanValue($_POST['is_baptized'] ?? false);
        $currentlyWorking = $this->toBooleanValue($_POST['currently_working'] ?? false);
        $primaryDepartmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $additionalDepartmentIds = $this->parseDepartmentIds($_POST['additional_department_ids'] ?? []);

        try {
            if (!$primaryDepartmentId && !empty($additionalDepartmentIds)) {
                throw new Exception('Select a primary department before adding additional departments.');
            }

            $data = [
                'bio_id' => $this->resolveBioId($_POST['bio_id'] ?? '', (int)$id),
                'first_name' => $_POST['first_name'],
                'last_name' => $_POST['last_name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'nationality' => $_POST['nationality'] ?? null,
                'address' => $_POST['address'],
                'stays_at' => $_POST['stays_at'] ?? null,
                'home_town' => $_POST['home_town'] ?? null,
                'gender' => $gender,
                'marital_status' => $marital_status,
                'spouse_name' => $_POST['spouse_name'] ?? null,
                'mother_name' => $_POST['mother_name'] ?? null,
                'father_name' => $_POST['father_name'] ?? null,
                'is_baptized' => $isBaptized,
                'baptism_pastor_church' => $_POST['baptism_pastor_church'] ?? null,
                'currently_working' => $currentlyWorking,
                'work_name' => $_POST['work_name'] ?? null,
                'date_of_birth' => !empty($_POST['date_of_birth']) ? $_POST['date_of_birth'] : null,
                'position' => !empty($_POST['position']) ? $_POST['position'] : null,
                'cluster_id' => !empty($_POST['cluster_id']) ? (int)$_POST['cluster_id'] : null,
                'department_id' => $primaryDepartmentId,
                'membership_status' => $_POST['membership_status'] ?? 'Active'
            ];

            // Handle Photo Upload
            if (!empty($_FILES['photo']['name'])) {
                $photoPath = $this->handleUpload($_FILES['photo']);
                if ($photoPath) {
                    $photoPath = $this->normalizeUploadPath($photoPath);
                    $data['photo_path'] = $photoPath;
                    // Delete old photo if exists
                    if (!empty($oldMember['photo_path'])) {
                        $this->supabaseDeleteByPublicUrl((string)$oldMember['photo_path']);
                        $oldFile = $this->resolveUploadFilePath($oldMember['photo_path']);
                        if ($oldFile !== '' && file_exists($oldFile)) {
                            @unlink($oldFile);
                        }
                    }
                }
            }

            $conn = Database::getInstance()->getConnection();
            $conn->beginTransaction();
            $memberModel->update($id, $data);
            $memberModel->syncAdditionalDepartments((int)$id, $additionalDepartmentIds, $data['department_id'] ?? null);
            $conn->commit();
            AuditLog::log("Updated member: " . $data['first_name'], "members", $id, $oldMember, $data);
            Session::flash('success', 'Member updated successfully');
        } catch (Exception $e) {
            $conn = Database::getInstance()->getConnection();
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            Session::flash('error', 'Update Error: ' . $e->getMessage());
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/members");
        exit;
    }

    public function delete() {
        $this->isAdmin();
        $id = $_GET['id'] ?? null;
        if ($id) {
            $memberModel = new Member();
            $member = $memberModel->find($id);
            $memberModel->delete($id);
            AuditLog::log("Deleted member: " . ($member['first_name'] ?? 'Unknown'), "members", $id);
            Session::flash('success', 'Member deleted successfully');
        }
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/members");
        exit;
    }

    public function viewAjax() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['error' => 'No ID provided']);
            exit;
        }

        $memberModel = new Member();
        $member = $memberModel->getWithDetails($id);

        if (Session::get('user_role') === 'dept_head') {
            $myDeptId = (int)(Session::get('user_department_id') ?? 0);
            if ($myDeptId > 0 && (int)($member['department_id'] ?? 0) !== $myDeptId) {
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Member not found']);
                exit;
            }
        }
        
        header('Content-Type: application/json');
        if ($member) {
            echo json_encode($member);
        } else {
            echo json_encode(['error' => 'Member not found']);
        }
        exit;
    }

    public function downloadTemplate() {
        $filename = "member_import_template.csv";
        $headers = [
            "BIO ID", "FIRST NAME", "LAST NAME", "GENDER", "DATE OF BIRTH",
            "NATIONALITY", "PHONE NUMBER", "ADDRESS", "STAYS AT", "HOME TOWN", "MARITAL STATUS",
            "NAME OF SPOUSE", "MOTHER NAME", "FATHER NAME", "HAVE YOU BEEN BAPTIZED",
            "PASTOR WHO BAPTIZED YOU AND CHURCH", "ARE YOU WORKING", "NAME OF WORK",
            "GROUP", "DEPARTMENT", "INITIAL STATUS"
        ];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        
        // Add an example row
        fputcsv($output, [
            "BIO-001", "CHRISTOPHER", "AGYEI", "MALE", "1990-01-01",
            "GHANAIAN", "0240000000", "MAMPONG ESTATE", "MAMPONG", "KUMASI", "MARRIED",
            "ABENA AGYEI", "GRACE AGYEI", "MICHAEL AGYEI", "YES",
            "PASTOR MENSAH - UPPER ROOM", "YES", "TEACHER", "YOUTH", "VISITATION", "Active"
        ]);
        
        fclose($output);
        exit;
    }

    public function importExcel() {
        $this->isAdmin();
        $this->ensureMemberSchema();
        @set_time_limit(0);

        // #region debug-point member-import-timeout-import
        $dbgEnabled = false;
        $dbgRunId = '';
        $dbgStart = 0.0;
        $dbgLogPath = '';
        try {
            $dbgFlag = ROOT_PATH . '/debug-member-import-timeout.md';
            if (file_exists($dbgFlag)) {
                $dbgEnabled = true;
                $dbgStart = microtime(true);
                $dbgRunId = bin2hex(random_bytes(6));
                $dir = ROOT_PATH . '/.dbg';
                if (!is_dir($dir)) {
                    @mkdir($dir, 0777, true);
                }
                $dbgLogPath = $dir . '/trae-debug-log-member-import-timeout.ndjson';
            }
        } catch (Throwable $e) {
            $dbgEnabled = false;
        }
        $dbgWrite = function (string $point, array $data = []) use (&$dbgEnabled, &$dbgLogPath, &$dbgRunId) {
            if (!$dbgEnabled || $dbgLogPath === '') return;
            $evt = [
                'ts' => date('c'),
                'sessionId' => 'member-import-timeout',
                'runId' => $dbgRunId,
                'point' => $point,
                'route' => (string)($_SERVER['REQUEST_URI'] ?? ''),
                'data' => $data
            ];
            @file_put_contents($dbgLogPath, json_encode($evt, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        };
        // #endregion debug-point member-import-timeout-import
        
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            // #region debug-point member-import-timeout-upload
            $dbgWrite('upload_invalid', [
                'error' => isset($_FILES['excel_file']) ? (int)($_FILES['excel_file']['error'] ?? -1) : null,
                'name' => isset($_FILES['excel_file']) ? (string)($_FILES['excel_file']['name'] ?? '') : null,
                'size' => isset($_FILES['excel_file']) ? (int)($_FILES['excel_file']['size'] ?? 0) : null,
            ]);
            // #endregion debug-point member-import-timeout-upload
            Session::flash('error', 'Please select a valid CSV/Excel file');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/members");
            exit;
        }

        $file = (string)($_FILES['excel_file']['tmp_name'] ?? '');
        $originalName = (string)($_FILES['excel_file']['name'] ?? '');
        $result = null;
        try {
            $result = $this->readMemberImportRows($file, $originalName);
        } catch (Throwable $e) {
            // #region debug-point member-import-timeout-parse-fail
            $dbgWrite('parse_fail', ['message' => (string)$e->getMessage()]);
            // #endregion debug-point member-import-timeout-parse-fail
            Session::flash('error', $e->getMessage() ?: 'Failed to read import file.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/members");
            exit;
        }

        $header = (array)($result['header'] ?? []);
        $rows = (array)($result['rows'] ?? []);
        $headerIndex = $this->buildImportHeaderIndex($header);
        $hasStaysAt = array_key_exists('STAYS AT', $headerIndex);

        // #region debug-point member-import-timeout-start
        $dbgWrite('start', [
            'file' => $originalName,
            'rows' => count($rows),
            'has_stays_at' => $hasStaysAt,
        ]);
        // #endregion debug-point member-import-timeout-start

        $db = Database::getInstance();
        $memberModel = new Member();

        $clustersByName = [];
        $departmentsByName = [];
        try {
            foreach (($db->fetchAll("SELECT id, name FROM clusters") ?: []) as $c) {
                $n = strtoupper(trim((string)($c['name'] ?? '')));
                if ($n !== '') {
                    $clustersByName[$n] = (int)($c['id'] ?? 0);
                }
            }
            foreach (($db->fetchAll("SELECT id, name FROM departments") ?: []) as $d) {
                $n = strtoupper(trim((string)($d['name'] ?? '')));
                if ($n !== '') {
                    $departmentsByName[$n] = (int)($d['id'] ?? 0);
                }
            }
        } catch (Throwable $e) {
        }

        $existingBio = [];
        $existingPhone = [];
        $existingNameDob = [];
        try {
            $existingRows = $db->fetchAll("SELECT bio_id, phone, first_name, last_name, date_of_birth FROM members") ?: [];
            foreach ($existingRows as $r) {
                $bio = strtoupper(trim((string)($r['bio_id'] ?? '')));
                if ($bio !== '') {
                    $existingBio[$bio] = true;
                }
                $p = $this->normalizePhoneDigits((string)($r['phone'] ?? ''));
                if ($p !== '') {
                    $existingPhone[$p] = true;
                }
                $dob = trim((string)($r['date_of_birth'] ?? ''));
                if ($dob !== '') {
                    $fn = strtoupper(trim((string)($r['first_name'] ?? '')));
                    $ln = strtoupper(trim((string)($r['last_name'] ?? '')));
                    if ($fn !== '' && $ln !== '') {
                        $existingNameDob[$fn . '|' . $ln . '|' . $dob] = true;
                    }
                }
            }
        } catch (Throwable $e) {
        }

        $conn = $db->getConnection();
        $useSavepoints = true;
        try {
            if (!$conn->inTransaction()) {
                $conn->beginTransaction();
            }
        } catch (Throwable $e) {
            $useSavepoints = false;
        }

        $count = 0;
        $skipped = 0;
        $errors = [];
        $rowIndex = 0;

        foreach ($rows as $data) {
            if (!is_array($data)) {
                continue;
            }
            $rowIndex++;

            $rowLabel = trim((string)($this->getImportValue($data, $headerIndex, 'FIRST NAME', 1) ?? '') . ' ' . (string)($this->getImportValue($data, $headerIndex, 'LAST NAME', 2) ?? ''));
            try {
                $bioRaw = $this->getImportValue($data, $headerIndex, 'BIO ID', 0);
                $firstNameRaw = $this->getImportValue($data, $headerIndex, 'FIRST NAME', 1);
                $lastNameRaw = $this->getImportValue($data, $headerIndex, 'LAST NAME', 2);
                if (trim((string)$firstNameRaw) === '' || trim((string)$lastNameRaw) === '') {
                    continue;
                }
                $genderRaw = $this->getImportValue($data, $headerIndex, 'GENDER', 3);
                $dobRaw = $this->getImportValue($data, $headerIndex, 'DATE OF BIRTH', 4);
                $nationalityRaw = $this->getImportValue($data, $headerIndex, 'NATIONALITY', 5);
                $phoneRaw = $this->getImportValue($data, $headerIndex, 'PHONE NUMBER', 6);
                $addressRaw = $this->getImportValue($data, $headerIndex, 'ADDRESS', 7);
                $staysAtRaw = $this->getImportValue($data, $headerIndex, 'STAYS AT', $hasStaysAt ? 8 : -1);
                $homeTownRaw = $this->getImportValue($data, $headerIndex, 'HOME TOWN', $hasStaysAt ? 9 : 8);
                $maritalRaw = $this->getImportValue($data, $headerIndex, 'MARITAL STATUS', $hasStaysAt ? 10 : 9);
                $spouseRaw = $this->getImportValue($data, $headerIndex, 'NAME OF SPOUSE', $hasStaysAt ? 11 : 10);
                $motherRaw = $this->getImportValue($data, $headerIndex, 'MOTHER NAME', $hasStaysAt ? 12 : 11);
                $fatherRaw = $this->getImportValue($data, $headerIndex, 'FATHER NAME', $hasStaysAt ? 13 : 12);
                $baptizedRaw = $this->getImportValue($data, $headerIndex, 'HAVE YOU BEEN BAPTIZED', $hasStaysAt ? 14 : 13);
                $baptismPastorRaw = $this->getImportValue($data, $headerIndex, 'PASTOR WHO BAPTIZED YOU AND CHURCH', $hasStaysAt ? 15 : 14);
                $workingRaw = $this->getImportValue($data, $headerIndex, 'ARE YOU WORKING', $hasStaysAt ? 16 : 15);
                $workNameRaw = $this->getImportValue($data, $headerIndex, 'NAME OF WORK', $hasStaysAt ? 17 : 16);
                $groupRaw = $this->getImportValue($data, $headerIndex, 'GROUP', $hasStaysAt ? 18 : 17);
                $departmentRaw = $this->getImportValue($data, $headerIndex, 'DEPARTMENT', $hasStaysAt ? 19 : 18);
                $statusRaw = $this->getImportValue($data, $headerIndex, 'INITIAL STATUS', $hasStaysAt ? 20 : 19);
                if ($statusRaw === null) {
                    $statusRaw = $this->getImportValue($data, $headerIndex, 'MEMBERSHIP STATUS', $hasStaysAt ? 20 : 19);
                }
                if ($staysAtRaw === null && !$hasStaysAt) {
                    $staysAtRaw = null;
                }

                $bioNorm = strtoupper(trim((string)($this->uppercaseImportedValue($bioRaw) ?? '')));
                if ($bioNorm !== '' && isset($existingBio[$bioNorm])) {
                    $skipped++;
                    continue;
                }
                $phoneNorm = $this->normalizePhoneDigits((string)($this->uppercaseImportedValue($phoneRaw) ?? ''));
                if ($phoneNorm !== '' && isset($existingPhone[$phoneNorm])) {
                    $skipped++;
                    continue;
                }
                $dobNorm = (string)($this->normalizeImportedDate($dobRaw) ?? '');
                if ($dobNorm !== '') {
                    $fnKey = strtoupper(trim((string)($this->uppercaseImportedValue($firstNameRaw) ?? '')));
                    $lnKey = strtoupper(trim((string)($this->uppercaseImportedValue($lastNameRaw) ?? '')));
                    if ($fnKey !== '' && $lnKey !== '' && isset($existingNameDob[$fnKey . '|' . $lnKey . '|' . $dobNorm])) {
                        $skipped++;
                        continue;
                    }
                }

                $clusterId = null;
                $grp = strtoupper(trim((string)$groupRaw));
                if ($grp !== '') {
                    $candidates = [$grp];
                    if (preg_match('/\(([^)]+)\)/', $grp, $m)) {
                        $inside = strtoupper(trim((string)($m[1] ?? '')));
                        if ($inside !== '') $candidates[] = $inside;
                    }
                    if (strpos($grp, '-') !== false) {
                        $parts = array_values(array_filter(array_map('trim', explode('-', $grp))));
                        if (!empty($parts)) {
                            $candidates[] = strtoupper((string)end($parts));
                        }
                    }
                    foreach (array_values(array_unique($candidates)) as $cand) {
                        if (isset($clustersByName[$cand]) && (int)$clustersByName[$cand] > 0) {
                            $clusterId = (int)$clustersByName[$cand];
                            break;
                        }
                    }
                }

                $deptAssignment = ['primary_department_id' => null, 'additional_department_ids' => []];
                $rawDept = trim((string)$departmentRaw);
                if ($rawDept !== '') {
                    $parts = preg_split('/\s*[,;|]\s*/', $rawDept);
                    $names = array_values(array_filter(array_map('trim', (array)$parts)));
                    $deptIds = [];
                    foreach ($names as $n) {
                        $k = strtoupper(trim((string)$n));
                        if ($k !== '' && isset($departmentsByName[$k]) && (int)$departmentsByName[$k] > 0) {
                            $deptIds[] = (int)$departmentsByName[$k];
                        }
                    }
                    $deptIds = array_values(array_unique($deptIds));
                    $deptAssignment['primary_department_id'] = array_shift($deptIds) ?: null;
                    $deptAssignment['additional_department_ids'] = $deptIds;
                }

                if ($useSavepoints) {
                    $sp = 'sp_import_' . $rowIndex;
                    $db->rawExec("SAVEPOINT {$sp}");
                }

                $memberData = [
                    'member_code' => $this->generateUniqueMemberCode(),
                    'bio_id' => $this->resolveBioId($this->uppercaseImportedValue($bioRaw)),
                    'first_name' => $this->uppercaseImportedValue($firstNameRaw ?? ''),
                    'last_name' => $this->uppercaseImportedValue($lastNameRaw ?? ''),
                    'gender' => $this->normalizeImportedOption($genderRaw ?? 'male', 'male'),
                    'date_of_birth' => $dobNorm !== '' ? $dobNorm : null,
                    'nationality' => $this->uppercaseImportedValue($nationalityRaw),
                    'phone' => $this->uppercaseImportedValue($phoneRaw),
                    'address' => $this->uppercaseImportedValue($addressRaw),
                    'stays_at' => $this->uppercaseImportedValue($staysAtRaw),
                    'home_town' => $this->uppercaseImportedValue($homeTownRaw),
                    'marital_status' => $this->normalizeImportedOption($maritalRaw ?? 'single', 'single'),
                    'spouse_name' => $this->uppercaseImportedValue($spouseRaw),
                    'mother_name' => $this->uppercaseImportedValue($motherRaw),
                    'father_name' => $this->uppercaseImportedValue($fatherRaw),
                    'is_baptized' => $this->toBooleanValue($baptizedRaw ?? false),
                    'baptism_pastor_church' => $this->uppercaseImportedValue($baptismPastorRaw),
                    'currently_working' => $this->toBooleanValue($workingRaw ?? false),
                    'work_name' => $this->uppercaseImportedValue($workNameRaw),
                    'cluster_id' => $clusterId,
                    'membership_status' => $this->normalizeMembershipStatus($statusRaw),
                    'join_date' => date('Y-m-d')
                ];

                $memberData['department_id'] = $deptAssignment['primary_department_id'];
                $memberId = $memberModel->create($memberData);
                $memberModel->syncAdditionalDepartments($memberId, $deptAssignment['additional_department_ids'], $memberData['department_id'] ?? null);
                $count++;

                if ($bioNorm !== '') $existingBio[$bioNorm] = true;
                if ($phoneNorm !== '') $existingPhone[$phoneNorm] = true;
                if ($dobNorm !== '' && isset($fnKey, $lnKey) && $fnKey !== '' && $lnKey !== '') {
                    $existingNameDob[$fnKey . '|' . $lnKey . '|' . $dobNorm] = true;
                }
            } catch (Exception $e) {
                if ($useSavepoints) {
                    try {
                        $sp = 'sp_import_' . $rowIndex;
                        $db->rawExec("ROLLBACK TO SAVEPOINT {$sp}");
                    } catch (Throwable $e2) {
                        if ($conn->inTransaction()) {
                            $conn->rollBack();
                        }
                        $useSavepoints = false;
                    }
                }
                $errors[] = "Error importing row for " . ($rowLabel !== '' ? $rowLabel : 'Unknown') . ": " . $e->getMessage();
            }

            // #region debug-point member-import-timeout-progress
            if ($dbgEnabled && (($count + $skipped + count($errors)) % 50) === 0) {
                $dbgWrite('progress', [
                    'imported' => $count,
                    'skipped' => $skipped,
                    'errors' => count($errors),
                    'ms' => (int)round((microtime(true) - $dbgStart) * 1000)
                ]);
            }
            // #endregion debug-point member-import-timeout-progress
        }

        if ($count > 0) {
            AuditLog::log("Imported $count members via Excel", "members");
            $suffix = $skipped > 0 ? (" (Skipped existing: $skipped)") : '';
            Session::flash('success', "Successfully imported $count members" . $suffix);
        } elseif ($skipped > 0 && empty($errors)) {
            Session::flash('success', "No new members imported (Skipped existing: $skipped).");
        } elseif ($skipped === 0 && empty($errors)) {
            Session::flash('error', 'No valid rows found. Make sure FIRST NAME and LAST NAME are filled and the file matches the template.');
        }
        
        if (!empty($errors)) {
            $first = (string)($errors[0] ?? 'Import failed.');
            $more = count($errors) - 1;
            Session::flash('error', $more > 0 ? ($first . " (+{$more} more)") : $first);
        }

        try {
            if ($conn->inTransaction()) {
                $conn->commit();
            }
        } catch (Throwable $e) {
        }

        // #region debug-point member-import-timeout-done
        $dbgWrite('done', [
            'imported' => $count,
            'skipped' => $skipped,
            'errors' => count($errors),
            'ms' => $dbgStart > 0 ? (int)round((microtime(true) - $dbgStart) * 1000) : null
        ]);
        // #endregion debug-point member-import-timeout-done

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/members");
        exit;
    }

    private function findExistingMember(Member $memberModel, array $data) {
        $bioId = trim((string)($data['bio_id'] ?? ''));
        if ($bioId !== '') {
            $existing = $memberModel->findByBioId($bioId);
            if ($existing) return $existing;
        }

        $rawPhone = trim((string)($data['phone'] ?? ''));
        $normalizedPhone = $this->normalizePhoneDigits($rawPhone);
        if ($normalizedPhone !== '') {
            $fragment = substr($normalizedPhone, -9);
            $candidates = $memberModel->findPotentialByPhoneFragment($fragment, 25);
            foreach ($candidates as $row) {
                $candidatePhone = $this->normalizePhoneDigits((string)($row['phone'] ?? ''));
                if ($candidatePhone !== '' && $candidatePhone === $normalizedPhone) {
                    return $row;
                }
            }
        }

        $dob = trim((string)($data['date_of_birth'] ?? ''));
        if ($dob !== '') {
            $existing = $memberModel->findByNameDob($data['first_name'] ?? '', $data['last_name'] ?? '', $dob);
            if ($existing) return $existing;
        }

        return null;
    }

    private function normalizePhoneDigits($phone) {
        $phone = trim((string)$phone);
        if ($phone === '') return '';
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if ($phone === '') return '';
        if (str_starts_with($phone, '+')) {
            $phone = ltrim($phone, '+');
        }

        if (preg_match('/^0\\d{9}$/', $phone)) {
            return '233' . substr($phone, 1);
        }
        if (preg_match('/^2330\\d{9}$/', $phone)) {
            return '233' . substr($phone, 4);
        }
        if (preg_match('/^233\\d{9}$/', $phone)) {
            return $phone;
        }
        if (preg_match('/^\\d{9}$/', $phone)) {
            return '233' . $phone;
        }

        return '';
    }

    public function exportAll() {
        $this->isAdmin();
        $this->ensureMemberSchema();

        $memberModel = new Member();
        $members = $memberModel->getAllWithDetails();
        
        $filename = "church_members_export_" . date('Y-m-d') . ".csv";
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, [
            'BIO ID', 'FIRST NAME', 'LAST NAME', 'GENDER', 'DATE OF BIRTH',
            'NATIONALITY', 'PHONE NUMBER', 'ADDRESS', 'HOME TOWN', 'MARITAL STATUS',
            'NAME OF SPOUSE', 'MOTHER NAME', 'FATHER NAME', 'HAVE YOU BEEN BAPTIZED',
            'PASTOR WHO BAPTIZED YOU AND CHURCH', 'ARE YOU WORKING', 'NAME OF WORK',
            'GROUP', 'DEPARTMENT'
        ]);
        
        foreach ($members as $member) {
            fputcsv($output, [
                strtoupper((string)($member['bio_id'] ?? '')),
                strtoupper((string)($member['first_name'] ?? '')),
                strtoupper((string)($member['last_name'] ?? '')),
                strtoupper((string)($member['gender'] ?? '')),
                strtoupper((string)($member['date_of_birth'] ?? '')),
                strtoupper((string)($member['nationality'] ?? '')),
                strtoupper((string)($member['phone'] ?? '')),
                strtoupper((string)($member['address'] ?? '')),
                strtoupper((string)($member['home_town'] ?? '')),
                strtoupper((string)($member['marital_status'] ?? '')),
                strtoupper((string)($member['spouse_name'] ?? '')),
                strtoupper((string)($member['mother_name'] ?? '')),
                strtoupper((string)($member['father_name'] ?? '')),
                !empty($member['is_baptized']) ? 'YES' : 'NO',
                strtoupper((string)($member['baptism_pastor_church'] ?? '')),
                !empty($member['currently_working']) ? 'YES' : 'NO',
                strtoupper((string)($member['work_name'] ?? '')),
                strtoupper((string)($member['cluster_name'] ?? '')),
                strtoupper((string)($member['department_names'] ?? ''))
            ]);
        }
        
        fclose($output);
        exit;
    }

    private function ensureMemberSchema() {
        $db = Database::getInstance();
        SchemaState::once('members_schema_v3', function () use ($db) {
            if (!$db->columnExists('members', 'occupation')) {
                $db->query("ALTER TABLE members ADD COLUMN occupation VARCHAR(100) NULL");
            }

            if (!$db->columnExists('members', 'position')) {
                $db->query("ALTER TABLE members ADD COLUMN position VARCHAR(100) NULL");
            }

            $columns = [
                'bio_id' => "ALTER TABLE members ADD COLUMN bio_id VARCHAR(50) NULL",
                'nationality' => "ALTER TABLE members ADD COLUMN nationality VARCHAR(100) NULL",
                'stays_at' => "ALTER TABLE members ADD COLUMN stays_at VARCHAR(255) NULL",
                'home_town' => "ALTER TABLE members ADD COLUMN home_town VARCHAR(120) NULL",
                'spouse_name' => "ALTER TABLE members ADD COLUMN spouse_name VARCHAR(150) NULL",
                'mother_name' => "ALTER TABLE members ADD COLUMN mother_name VARCHAR(150) NULL",
                'father_name' => "ALTER TABLE members ADD COLUMN father_name VARCHAR(150) NULL",
                'is_baptized' => "ALTER TABLE members ADD COLUMN is_baptized BOOLEAN NOT NULL DEFAULT FALSE",
                'baptism_pastor_church' => "ALTER TABLE members ADD COLUMN baptism_pastor_church VARCHAR(255) NULL",
                'currently_working' => "ALTER TABLE members ADD COLUMN currently_working BOOLEAN NOT NULL DEFAULT FALSE",
                'work_name' => "ALTER TABLE members ADD COLUMN work_name VARCHAR(150) NULL",
                'photo_path' => "ALTER TABLE members ADD COLUMN photo_path VARCHAR(255) NULL",
                'membership_status' => "ALTER TABLE members ADD COLUMN membership_status VARCHAR(30) NULL DEFAULT 'Active'",
                'join_date' => "ALTER TABLE members ADD COLUMN join_date DATE NULL",
            ];

            foreach ($columns as $columnName => $sql) {
                if (!$db->columnExists('members', $columnName)) {
                    $db->query($sql);
                }
            }

            if (!$db->tableExists('member_departments')) {
                if ($db->isPgsql()) {
                    $db->rawExec(
                        "CREATE TABLE IF NOT EXISTS member_departments (
                            member_id integer NOT NULL,
                            department_id integer NOT NULL,
                            created_at timestamptz NOT NULL DEFAULT timezone('utc', now()),
                            PRIMARY KEY (member_id, department_id),
                            CONSTRAINT fk_member_departments_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
                            CONSTRAINT fk_member_departments_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
                        );
                        CREATE INDEX IF NOT EXISTS idx_member_departments_department ON member_departments (department_id);"
                    );
                } else {
                    $db->rawExec(
                        "CREATE TABLE IF NOT EXISTS member_departments (
                            member_id INT NOT NULL,
                            department_id INT NOT NULL,
                            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            PRIMARY KEY (member_id, department_id),
                            KEY idx_member_departments_department (department_id),
                            CONSTRAINT fk_member_departments_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
                            CONSTRAINT fk_member_departments_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
                    );
                }
            }
        });
    }

    private function normalizeMembershipStatus($value): string
    {
        $v = strtolower(trim((string)$value));
        if ($v === '') {
            return 'Active';
        }

        if (in_array($v, ['active', 'act', 'a', 'yes', 'y', '1', 'true'], true)) {
            return 'Active';
        }

        if (in_array($v, ['inactive', 'in active', 'deactive', 'deactivated', 'disabled', 'no', 'n', '0', 'false'], true)) {
            return 'Inactive';
        }

        return 'Active';
    }

    private function buildImportHeaderIndex(array $header): array
    {
        $map = [];
        foreach ($header as $i => $h) {
            $key = $this->normalizeImportHeaderKey($h);
            if ($key === '') {
                continue;
            }
            if (!array_key_exists($key, $map)) {
                $map[$key] = (int)$i;
            }
        }
        return $map;
    }

    private function normalizeImportHeaderKey($value): string
    {
        $v = strtoupper(trim((string)$value));
        if ($v === '') {
            return '';
        }
        $v = preg_replace('/\s+/', ' ', $v);
        return trim((string)$v);
    }

    private function getImportValue(array $row, array $headerIndex, string $headerName, int $fallbackIndex)
    {
        $k = $this->normalizeImportHeaderKey($headerName);
        if ($k !== '' && array_key_exists($k, $headerIndex)) {
            $idx = (int)$headerIndex[$k];
            return $row[$idx] ?? null;
        }
        return $row[$fallbackIndex] ?? null;
    }

    private function readMemberImportRows(string $tmpPath, string $originalName): array
    {
        $tmpPath = trim($tmpPath);
        if ($tmpPath === '' || !file_exists($tmpPath)) {
            throw new Exception('Import file not found.');
        }

        $name = strtolower(trim($originalName));
        $ext = '';
        if (($pos = strrpos($name, '.')) !== false) {
            $ext = substr($name, $pos + 1);
        }

        if ($ext === 'xlsx') {
            return $this->readXlsxRows($tmpPath);
        }

        $fh = @fopen($tmpPath, 'rb');
        if ($fh) {
            $sig = fread($fh, 2);
            fclose($fh);
            if ($sig === "PK") {
                return $this->readXlsxRows($tmpPath);
            }
        }

        return $this->readCsvRows($tmpPath);
    }

    private function readCsvRows(string $path): array
    {
        $handle = fopen($path, 'r');
        if (!$handle) {
            throw new Exception('Failed to open CSV file.');
        }

        $header = fgetcsv($handle);
        if (!is_array($header) || empty($header)) {
            fclose($handle);
            throw new Exception('CSV header row is missing.');
        }

        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            if (!is_array($row)) {
                continue;
            }
            $rows[] = $row;
        }
        fclose($handle);

        return ['header' => $header, 'rows' => $rows];
    }

    private function readXlsxRows(string $path): array
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('XLSX import is not available on this server. Please upload CSV instead.');
        }

        $zip = new ZipArchive();
        $ok = $zip->open($path);
        if ($ok !== true) {
            throw new Exception('Failed to open XLSX file.');
        }

        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if (is_string($sharedXml) && trim($sharedXml) !== '') {
            $sx = @simplexml_load_string($sharedXml);
            if ($sx) {
                foreach ($sx->si as $si) {
                    $text = '';
                    if (isset($si->t)) {
                        $text = (string)$si->t;
                    } elseif (isset($si->r)) {
                        foreach ($si->r as $r) {
                            $text .= (string)($r->t ?? '');
                        }
                    }
                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetPath = 'xl/worksheets/sheet1.xml';
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if (is_string($workbookXml) && is_string($relsXml)) {
            $wb = @simplexml_load_string($workbookXml);
            $rels = @simplexml_load_string($relsXml);
            if ($wb && $rels) {
                $wb->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                $sheets = $wb->xpath('//ns:sheets/ns:sheet');
                if (is_array($sheets) && isset($sheets[0])) {
                    $sheet = $sheets[0];
                    $ridAttr = $sheet->attributes('http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                    $rid = (string)($ridAttr['id'] ?? '');
                    if ($rid !== '') {
                        $rels->registerXPathNamespace('r', 'http://schemas.openxmlformats.org/package/2006/relationships');
                        $relsNodes = $rels->xpath("//r:Relationship[@Id='{$rid}']");
                        if (is_array($relsNodes) && isset($relsNodes[0])) {
                            $target = (string)($relsNodes[0]['Target'] ?? '');
                            $target = ltrim($target, '/');
                            if ($target !== '') {
                                $sheetPath = 'xl/' . $target;
                            }
                        }
                    }
                }
            }
        }

        $sheetXml = $zip->getFromName($sheetPath);
        $zip->close();
        if (!is_string($sheetXml) || trim($sheetXml) === '') {
            throw new Exception('XLSX sheet data not found.');
        }

        $sx = @simplexml_load_string($sheetXml);
        if (!$sx) {
            throw new Exception('Failed to parse XLSX sheet.');
        }
        $sx->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
        $rowsXml = $sx->xpath('//ns:sheetData/ns:row');
        if (!is_array($rowsXml) || empty($rowsXml)) {
            throw new Exception('XLSX has no rows.');
        }

        $rows = [];
        $maxCols = 0;
        foreach ($rowsXml as $rowNode) {
            $cells = [];
            foreach ($rowNode->c as $c) {
                $r = (string)($c['r'] ?? '');
                $colIndex = $this->xlsxColIndexFromRef($r);
                if ($colIndex < 0) {
                    continue;
                }
                $t = (string)($c['t'] ?? '');
                $v = isset($c->v) ? (string)$c->v : '';
                $value = $v;
                if ($t === 's') {
                    $si = (int)$v;
                    $value = $sharedStrings[$si] ?? '';
                } elseif ($t === 'inlineStr') {
                    $value = (string)($c->is->t ?? '');
                }
                $cells[$colIndex] = $value;
                if ($colIndex + 1 > $maxCols) {
                    $maxCols = $colIndex + 1;
                }
            }
            $row = [];
            for ($i = 0; $i < $maxCols; $i++) {
                $row[$i] = $cells[$i] ?? null;
            }
            $rows[] = $row;
        }

        if (count($rows) < 2) {
            throw new Exception('XLSX has no data rows.');
        }

        $header = $rows[0];
        $dataRows = array_slice($rows, 1);
        return ['header' => $header, 'rows' => $dataRows];
    }

    private function xlsxColIndexFromRef(string $ref): int
    {
        if ($ref === '') {
            return -1;
        }
        $ref = strtoupper($ref);
        $letters = preg_replace('/[^A-Z]/', '', $ref);
        if ($letters === '') {
            return -1;
        }
        $n = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $n = $n * 26 + (ord($letters[$i]) - 64);
        }
        return $n - 1;
    }

    private function toBooleanValue($value) {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $normalized = strtolower(trim((string)$value));
        return in_array($normalized, ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    private function uppercaseImportedValue($value) {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string)$value);
        if ($trimmed === '') {
            return null;
        }

        return function_exists('mb_strtoupper') ? mb_strtoupper($trimmed, 'UTF-8') : strtoupper($trimmed);
    }

    private function normalizeImportedOption($value, $default = '') {
        $trimmed = trim((string)$value);
        return $trimmed === '' ? $default : strtolower($trimmed);
    }

    private function normalizeImportedDate($value): ?string
    {
        $v = trim((string)$value);
        if ($v === '') {
            return null;
        }

        if (preg_match('/^\d+(\.\d+)?$/', $v)) {
            $days = (int)floor((float)$v);
            if ($days > 20000 && $days < 60000) {
                $unix = ($days - 25569) * 86400;
                return gmdate('Y-m-d', $unix);
            }
        }

        $ts = strtotime($v);
        if ($ts === false) {
            return null;
        }
        return date('Y-m-d', $ts);
    }

    private function parseDepartmentIds($values) {
        if (!is_array($values)) {
            $values = [$values];
        }

        return array_values(array_unique(array_filter(array_map('intval', $values))));
    }

    private function resolveClusterIdByName($name) {
        $trimmed = trim((string)$name);
        if ($trimmed === '') {
            return null;
        }

        $db = Database::getInstance();
        $candidates = [$trimmed];
        if (preg_match('/\(([^)]+)\)/', $trimmed, $m)) {
            $inside = trim((string)($m[1] ?? ''));
            if ($inside !== '') {
                $candidates[] = $inside;
            }
        }
        if (strpos($trimmed, '-') !== false) {
            $parts = array_values(array_filter(array_map('trim', explode('-', $trimmed))));
            if (!empty($parts)) {
                $candidates[] = end($parts);
            }
        }
        $candidates = array_values(array_unique(array_filter($candidates, fn($v) => trim((string)$v) !== '')));

        foreach ($candidates as $candidate) {
            $row = $db->fetch("SELECT id FROM clusters WHERE UPPER(name) = UPPER(?) LIMIT 1", [$candidate]);
            if ($row) {
                return (int)$row['id'];
            }
        }

        return null;
    }

    private function resolveDepartmentAssignmentsByName($value) {
        $raw = trim((string)$value);
        if ($raw === '') {
            return [
                'primary_department_id' => null,
                'additional_department_ids' => [],
            ];
        }

        $parts = preg_split('/\s*[,;|]\s*/', $raw);
        $names = array_values(array_filter(array_map('trim', $parts)));
        if (empty($names)) {
            return [
                'primary_department_id' => null,
                'additional_department_ids' => [],
            ];
        }

        $db = Database::getInstance();
        $departmentIds = [];
        foreach ($names as $name) {
            $row = $db->fetch("SELECT id FROM departments WHERE UPPER(name) = UPPER(?) LIMIT 1", [$name]);
            if (!$row) {
                continue;
            }
            $departmentIds[] = (int)$row['id'];
        }

        $departmentIds = array_values(array_unique($departmentIds));
        $primaryDepartmentId = array_shift($departmentIds);

        return [
            'primary_department_id' => $primaryDepartmentId ?: null,
            'additional_department_ids' => $departmentIds,
        ];
    }

    private function resolveBioId($submittedCode, $ignoreId = null) {
        $bioId = trim((string)$submittedCode);
        if ($bioId === '') {
            return null;
        }

        $memberModel = new Member();
        $existing = $memberModel->findByBioId($bioId);
        if ($existing && (int)($existing['id'] ?? 0) !== (int)$ignoreId) {
            throw new Exception('Bio ID already exists. Please enter a different one.');
        }

        return $bioId;
    }

    private function generateUniqueMemberCode() {
        $memberModel = new Member();

        do {
            $memberCode = 'MEM' . strtoupper(substr(uniqid(), -6));
            $existing = $memberModel->findByMemberCode($memberCode);
        } while ($existing);

        return $memberCode;
    }


    private function handleUpload($file) {
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

        $targetDir = "public/uploads/members/";
        if (!is_dir(ROOT_PATH . '/' . $targetDir)) {
            mkdir(ROOT_PATH . '/' . $targetDir, 0777, true);
        }

        $fileExtension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
        $newFileName = uniqid('profile_', true) . '.' . $fileExtension;
        $targetFile = $targetDir . $newFileName;

        // Check if image file is an actual image
        $check = getimagesize($file["tmp_name"]);
        if($check === false) {
            throw new Exception("File is not an image.");
        }

        // Check file size (limit to 2MB)
        if ($file["size"] > 2000000) {
            throw new Exception("File is too large (max 2MB).");
        }

        // Allow certain file formats
        if (!in_array($fileExtension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            throw new Exception("Only JPG, JPEG, PNG, GIF & WEBP files are allowed.");
        }

        $supabaseUrl = trim((string)Env::get('SUPABASE_URL', ''));
        $supabaseServiceRoleKey = trim((string)Env::get('SUPABASE_SERVICE_ROLE_KEY', ''));
        $bucket = trim((string)Env::get('SUPABASE_STORAGE_BUCKET', ''));
        if ($bucket === '') $bucket = 'uploads';

        if ($supabaseUrl !== '' && $supabaseServiceRoleKey !== '') {
            $mime = $this->resolveMimeType($file["tmp_name"], $fileExtension);
            $objectPath = 'members/' . date('Y') . '/' . date('m') . '/' . $newFileName;
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
}
