<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Member.php';
require_once __DIR__ . '/../Models/Finance.php';
require_once __DIR__ . '/../Models/Department.php';

class PastorController extends BaseController {
    public function index() {
        if (!Auth::isPastor()) {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }

        $db = Database::getInstance();
        $memberModel = new Member();
        $financeModel = new Finance();
        $deptModel = new Department();

        $searchTerm = (string)($_GET['search'] ?? '');
        $deptFilter = (string)($_GET['department'] ?? '');
        $statusFilter = (string)($_GET['status'] ?? '');
        $sort = (string)($_GET['sort'] ?? '');

        $members = $memberModel->searchAndFilter($searchTerm, $deptFilter, $statusFilter, $sort);
        $stats = $memberModel->getStats();
        $departments = $deptModel->all();
        $recentTransactions = $financeModel->getRecentTransactionsWithMeta(200);
        $pendingChangeRequests = $financeModel->getPendingChangeRequests(120);

        $pendingDepartmentExpenseRequests = [];
        try {
            if ($db->tableExists('department_expense_requests')) {
                $pendingDepartmentExpenseRequests = $db->fetchAll(
                    "SELECT r.*,
                            d.name as department_name,
                            requester.name as requested_by_name
                     FROM department_expense_requests r
                     INNER JOIN departments d ON d.id = r.department_id
                     INNER JOIN users requester ON requester.id = r.requested_by
                     WHERE LOWER(COALESCE(r.status, 'pending')) = 'pending'
                     ORDER BY r.created_at DESC, r.id DESC
                     LIMIT 120"
                ) ?: [];
            }
        } catch (Throwable $e) {
            $pendingDepartmentExpenseRequests = [];
        }

        $upcomingBirthdays = $memberModel->getUpcomingBirthdays(12);

        $currency = strtoupper(trim((string)(AppConfig::getSetting('finance_currency', 'GHS'))));
        if (!preg_match('/^[A-Z]{2,5}$/', $currency)) $currency = 'GHS';

        View::render('pastor.index', [
            'title' => 'Pastor Dashboard',
            'currency' => $currency,
            'members' => $members,
            'stats' => $stats,
            'departments' => $departments,
            'filters' => [
                'search' => $searchTerm,
                'department' => $deptFilter,
                'status' => $statusFilter,
                'sort' => $sort
            ],
            'recent_transactions' => $recentTransactions,
            'pending_change_requests' => $pendingChangeRequests,
            'pending_department_expense_requests' => $pendingDepartmentExpenseRequests,
            'upcoming_birthdays' => $upcomingBirthdays
        ]);
    }
}
