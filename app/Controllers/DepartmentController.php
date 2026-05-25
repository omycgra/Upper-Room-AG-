<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Department.php';
require_once __DIR__ . '/../Models/Member.php';

class DepartmentController extends BaseController {
    public function index() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $this->ensureSchema();
        $db = Database::getInstance();

        $departments = $db->fetchAll(
            "SELECT d.*,
                    m.first_name as head_first_name,
                    m.last_name as head_last_name,
                    m.member_code as head_member_code
             FROM departments d
             LEFT JOIN members m ON d.head_member_id = m.id
             ORDER BY d.name ASC"
        );

        $members = $db->fetchAll(
            "SELECT id, first_name, last_name, member_code
             FROM members
             ORDER BY first_name ASC, last_name ASC"
        );

        View::render('departments.index', [
            'title' => 'Departments',
            'departments' => $departments,
            'members' => $members
        ]);
    }

    public function store() {
        $this->isAdmin();
        $this->ensureSchema();

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Department name is required');
            $this->redirectToIndex();
        }

        $data = [
            'name' => $name,
            'description' => $_POST['description'] ?? null,
            'head_member_id' => !empty($_POST['head_member_id']) ? (int)$_POST['head_member_id'] : null
        ];

        $departmentModel = new Department();
        try {
            $id = $departmentModel->create($data);
            AuditLog::log("Created department: " . $name, "departments", $id, null, $data);
            Session::flash('success', 'Department created successfully');
        } catch (Exception $e) {
            Session::flash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirectToIndex();
    }

    public function update() {
        $this->isAdmin();
        $this->ensureSchema();

        $id = $_POST['id'] ?? null;
        if (!$id) {
            Session::flash('error', 'Department ID missing');
            $this->redirectToIndex();
        }

        $departmentModel = new Department();
        $old = $departmentModel->find($id);

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            Session::flash('error', 'Department name is required');
            $this->redirectToIndex();
        }

        $data = [
            'name' => $name,
            'description' => $_POST['description'] ?? null,
            'head_member_id' => !empty($_POST['head_member_id']) ? (int)$_POST['head_member_id'] : null
        ];

        try {
            $departmentModel->update($id, $data);
            AuditLog::log("Updated department: " . $name, "departments", $id, $old, $data);
            Session::flash('success', 'Department updated successfully');
        } catch (Exception $e) {
            Session::flash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirectToIndex();
    }

    public function delete() {
        $this->isAdmin();

        $id = $_GET['id'] ?? null;
        if ($id) {
            $departmentModel = new Department();
            $old = $departmentModel->find($id);
            try {
                $departmentModel->delete($id);
                AuditLog::log("Deleted department: " . ($old['name'] ?? 'Unknown'), "departments", $id, $old, null);
                Session::flash('success', 'Department deleted successfully');
            } catch (Exception $e) {
                Session::flash('error', 'Error: ' . $e->getMessage());
            }
        }

        $this->redirectToIndex();
    }

    private function ensureSchema() {
        $db = Database::getInstance();
        if (!$db->columnExists('departments', 'head_member_id')) {
            $db->query("ALTER TABLE departments ADD COLUMN head_member_id INT NULL");
        }
    }

    private function redirectToIndex() {
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/departments");
        exit;
    }
}
