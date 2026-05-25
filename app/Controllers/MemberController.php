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

        if (Session::get('user_role') === 'dept_head') {
            $deptFilter = (string)(Session::get('user_department_id') ?? '');
        }
        
        $members = $memberModel->searchAndFilter($searchTerm, $deptFilter, $statusFilter, $sort);
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
                'sort' => $sort
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
                    $data['photo_path'] = $photoPath;
                    // Delete old photo if exists
                    if ($oldMember['photo_path'] && file_exists(ROOT_PATH . '/' . $oldMember['photo_path'])) {
                        unlink(ROOT_PATH . '/' . $oldMember['photo_path']);
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
            "NATIONALITY", "PHONE NUMBER", "ADDRESS", "HOME TOWN", "MARITAL STATUS",
            "NAME OF SPOUSE", "MOTHER NAME", "FATHER NAME", "HAVE YOU BEEN BAPTIZED",
            "PASTOR WHO BAPTIZED YOU AND CHURCH", "ARE YOU WORKING", "NAME OF WORK",
            "GROUP", "DEPARTMENT"
        ];

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fputcsv($output, $headers);
        
        // Add an example row
        fputcsv($output, [
            "BIO-001", "CHRISTOPHER", "AGYEI", "MALE", "1990-01-01",
            "GHANAIAN", "0240000000", "MAMPONG ESTATE", "KUMASI", "MARRIED",
            "ABENA AGYEI", "GRACE AGYEI", "MICHAEL AGYEI", "YES",
            "PASTOR MENSAH - UPPER ROOM", "YES", "TEACHER", "YOUTH", "VISITATION"
        ]);
        
        fclose($output);
        exit;
    }

    public function importExcel() {
        $this->isAdmin();
        $this->ensureMemberSchema();
        
        if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
            Session::flash('error', 'Please select a valid CSV/Excel file');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/members");
            exit;
        }

        $file = $_FILES['excel_file']['tmp_name'];
        $handle = fopen($file, "r");
        
        // Skip header row
        $header = fgetcsv($handle);
        
        $memberModel = new Member();
        $count = 0;
        $skipped = 0;
        $errors = [];

        while (($data = fgetcsv($handle)) !== FALSE) {
            if (empty($data[1]) || empty($data[2])) continue; // Skip empty rows or missing names

            try {
                $memberData = [
                    'member_code' => $this->generateUniqueMemberCode(),
                    'bio_id' => $this->resolveBioId($this->uppercaseImportedValue($data[0] ?? '')),
                    'first_name' => $this->uppercaseImportedValue($data[1] ?? ''),
                    'last_name' => $this->uppercaseImportedValue($data[2] ?? ''),
                    'gender' => $this->normalizeImportedOption($data[3] ?? 'male', 'male'),
                    'date_of_birth' => !empty($data[4]) ? $data[4] : null,
                    'nationality' => $this->uppercaseImportedValue($data[5] ?? null),
                    'phone' => $this->uppercaseImportedValue($data[6] ?? null),
                    'address' => $this->uppercaseImportedValue($data[7] ?? null),
                    'home_town' => $this->uppercaseImportedValue($data[8] ?? null),
                    'marital_status' => $this->normalizeImportedOption($data[9] ?? 'single', 'single'),
                    'spouse_name' => $this->uppercaseImportedValue($data[10] ?? null),
                    'mother_name' => $this->uppercaseImportedValue($data[11] ?? null),
                    'father_name' => $this->uppercaseImportedValue($data[12] ?? null),
                    'is_baptized' => $this->toBooleanValue($data[13] ?? false),
                    'baptism_pastor_church' => $this->uppercaseImportedValue($data[14] ?? null),
                    'currently_working' => $this->toBooleanValue($data[15] ?? false),
                    'work_name' => $this->uppercaseImportedValue($data[16] ?? null),
                    'cluster_id' => $this->resolveClusterIdByName($data[17] ?? null),
                    'membership_status' => 'ACTIVE',
                    'join_date' => date('Y-m-d')
                ];

                $departmentAssignment = $this->resolveDepartmentAssignmentsByName($data[18] ?? null);
                $memberData['department_id'] = $departmentAssignment['primary_department_id'];

                $existing = $this->findExistingMember($memberModel, $memberData);
                if ($existing) {
                    $skipped++;
                    continue;
                }

                $conn = Database::getInstance()->getConnection();
                $conn->beginTransaction();
                $memberId = $memberModel->create($memberData);
                $memberModel->syncAdditionalDepartments($memberId, $departmentAssignment['additional_department_ids'], $memberData['department_id'] ?? null);
                $conn->commit();
                $count++;
            } catch (Exception $e) {
                $conn = Database::getInstance()->getConnection();
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                $errors[] = "Error importing row for " . trim(($data[1] ?? '') . ' ' . ($data[2] ?? 'Unknown')) . ": " . $e->getMessage();
            }
        }

        fclose($handle);

        if ($count > 0) {
            AuditLog::log("Imported $count members via Excel", "members");
            $suffix = $skipped > 0 ? (" (Skipped existing: $skipped)") : '';
            Session::flash('success', "Successfully imported $count members" . $suffix);
        }
        
        if (!empty($errors)) {
            Session::flash('error', implode("<br>", array_slice($errors, 0, 3)) . (count($errors) > 3 ? "... and more" : ""));
        }

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
        SchemaState::once('members_schema_v2', function () use ($db) {
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
        $row = $db->fetch("SELECT id FROM clusters WHERE UPPER(name) = UPPER(?) LIMIT 1", [$trimmed]);
        if (!$row) {
            throw new Exception('Group not found: ' . $trimmed);
        }

        return (int)$row['id'];
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
                throw new Exception('Department not found: ' . $name);
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
        if($fileExtension != "jpg" && $fileExtension != "png" && $fileExtension != "jpeg" && $fileExtension != "gif" ) {
            throw new Exception("Only JPG, JPEG, PNG & GIF files are allowed.");
        }

        if (move_uploaded_file($file["tmp_name"], ROOT_PATH . '/' . $targetFile)) {
            return $targetFile;
        } else {
            throw new Exception("Failed to move uploaded file.");
        }
    }
}
