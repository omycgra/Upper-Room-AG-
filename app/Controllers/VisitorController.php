<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Visitor.php';

class VisitorController extends BaseController {
    public function index() {
        $this->denyIfDepartmentHead();
        $this->ensureVisitorSchema();
        $visitorModel = new Visitor();
        $visitors = $visitorModel->getAllWithAssignee();
        
        View::render('visitors.index', [
            'title' => 'Visitor Tracking',
            'visitors' => $visitors,
            'stats' => $this->buildVisitorStats($visitors)
        ]);
    }

    public function add() {
        $this->denyIfDepartmentHead();
        $this->ensureVisitorSchema();
        View::render('visitors.add', [
            'title' => 'Register New Visitor',
            'followupUsers' => $this->getFollowupUsers()
        ]);
    }

    public function store() {
        $this->denyIfDepartmentHead();
        $this->ensureVisitorSchema();
        $visitorModel = new Visitor();
        $followupUsers = $this->getFollowupUsers();
        $allowedAssigneeIds = array_map('intval', array_column($followupUsers, 'id'));

        $firstName = trim((string)($_POST['first_name'] ?? ''));
        $lastName = trim((string)($_POST['last_name'] ?? ''));
        if ($firstName === '' || $lastName === '') {
            Session::flash('error', 'First name and last name are required.');
            header('Location: ' . BASE_URL . '/visitors/add');
            exit;
        }

        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        if ($assignedTo === null) {
            Session::flash('error', 'Please assign this visitor to a visitation team member.');
            header('Location: ' . BASE_URL . '/visitors/add');
            exit;
        }

        if (!in_array($assignedTo, $allowedAssigneeIds, true)) {
            Session::flash('error', 'Visitor assignment must be to a visitation team member only.');
            header('Location: ' . BASE_URL . '/visitors/add');
            exit;
        }

        $data = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone' => trim((string)($_POST['phone'] ?? '')) ?: null,
            'email' => trim((string)($_POST['email'] ?? '')) ?: null,
            'visit_date' => !empty($_POST['visit_date']) ? $_POST['visit_date'] : date('Y-m-d'),
            'invited_by' => trim((string)($_POST['invited_by'] ?? '')) ?: null,
            'service_attended' => trim((string)($_POST['service_attended'] ?? '')) ?: null,
            'gender' => !empty($_POST['gender']) ? strtolower(trim((string)$_POST['gender'])) : null,
            'address' => trim((string)($_POST['address'] ?? '')) ?: null,
            'is_first_time' => in_array((string)($_POST['is_first_time'] ?? '1'), ['1', 'true', 'yes'], true) ? 1 : 0,
            'preferred_contact_method' => trim((string)($_POST['preferred_contact_method'] ?? '')) ?: null,
            'follow_up_date' => !empty($_POST['follow_up_date']) ? $_POST['follow_up_date'] : null,
            'assigned_to' => $assignedTo,
            'follow_up_notes' => trim((string)($_POST['follow_up_notes'] ?? '')) ?: null,
            'prayer_request' => trim((string)($_POST['prayer_request'] ?? '')) ?: null,
            'follow_up_status' => 'Pending'
        ];
        
        $visitorId = $visitorModel->create($data);
        
        if ($visitorId) {
            AuditLog::log(
                'Registered visitor: ' . $data['first_name'] . ' ' . $data['last_name'],
                'visitors',
                $visitorId,
                null,
                $data
            );
            Session::flash('success', 'Visitor registered successfully.');
            header('Location: ' . BASE_URL . '/visitors');
        } else {
            Session::flash('error', 'Failed to register visitor.');
            header('Location: ' . BASE_URL . '/visitors/add');
        }
        exit;
    }

    public function exportAssigned() {
        $this->ensureVisitorSchema();
        if (!Auth::isAdmin() && !Auth::isVisitationTeam()) {
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $visitorModel = new Visitor();
        $visitors = $visitorModel->getVisitationAssignments();
        $filename = 'visitation_assigned_visitors_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        if (!$output) {
            exit;
        }

        fputcsv($output, [
            'Visitor Name',
            'Visit Date',
            'Service Attended',
            'First-Time Visitor',
            'Phone',
            'Email',
            'Preferred Contact',
            'Invited By',
            'Follow-Up Status',
            'Follow-Up Date',
            'Assigned To',
            'Assigned Department',
            'Address',
            'Follow-Up Notes',
            'Prayer Request'
        ]);

        foreach ($visitors as $visitor) {
            fputcsv($output, [
                trim(($visitor['first_name'] ?? '') . ' ' . ($visitor['last_name'] ?? '')),
                $visitor['visit_date'] ?? '',
                $visitor['service_attended'] ?? '',
                !empty($visitor['is_first_time']) ? 'Yes' : 'No',
                $visitor['phone'] ?? '',
                $visitor['email'] ?? '',
                $visitor['preferred_contact_method'] ?? '',
                $visitor['invited_by'] ?? '',
                $visitor['follow_up_status'] ?? '',
                $visitor['follow_up_date'] ?? '',
                $visitor['assigned_to_name'] ?? '',
                $visitor['assigned_department_name'] ?? '',
                $visitor['address'] ?? '',
                $visitor['follow_up_notes'] ?? '',
                $visitor['prayer_request'] ?? '',
            ]);
        }

        fclose($output);
        exit;
    }

    private function denyIfDepartmentHead() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
    }

    private function getFollowupUsers() {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT u.id,
                    COALESCE(NULLIF(u.name, ''), NULLIF(u.username, ''), u.email) AS display_name,
                    u.role,
                    d.name AS department_name
             FROM users u
             LEFT JOIN departments d ON u.department_id = d.id
             WHERE LOWER(COALESCE(u.role, '')) = 'visitation_team'
               AND LOWER(COALESCE(d.name, '')) LIKE '%visitation%'
             ORDER BY display_name ASC"
        );
    }

    private function buildVisitorStats(array $visitors) {
        $stats = [
            'total' => count($visitors),
            'pending' => 0,
            'completed' => 0,
            'first_time' => 0,
            'assigned' => 0,
        ];

        foreach ($visitors as $visitor) {
            if (($visitor['follow_up_status'] ?? '') === 'Completed') {
                $stats['completed']++;
            } else {
                $stats['pending']++;
            }

            if (!empty($visitor['is_first_time'])) {
                $stats['first_time']++;
            }

            if (!empty($visitor['assigned_to'])) {
                $stats['assigned']++;
            }
        }

        return $stats;
    }

    private function ensureVisitorSchema() {
        $db = Database::getInstance();
        SchemaState::once('visitors_schema', function () use ($db) {
            $columns = [
                'service_attended' => "ALTER TABLE visitors ADD COLUMN service_attended VARCHAR(100) NULL",
                'gender' => "ALTER TABLE visitors ADD COLUMN gender VARCHAR(20) NULL",
                'address' => "ALTER TABLE visitors ADD COLUMN address TEXT NULL",
                'is_first_time' => "ALTER TABLE visitors ADD COLUMN is_first_time BOOLEAN NULL DEFAULT TRUE",
                'preferred_contact_method' => "ALTER TABLE visitors ADD COLUMN preferred_contact_method VARCHAR(30) NULL",
                'follow_up_date' => "ALTER TABLE visitors ADD COLUMN follow_up_date DATE NULL",
                'follow_up_notes' => "ALTER TABLE visitors ADD COLUMN follow_up_notes TEXT NULL",
            ];

            foreach ($columns as $columnName => $sql) {
                if (!$db->columnExists('visitors', $columnName)) {
                    $db->query($sql);
                }
            }
        });
    }
}
