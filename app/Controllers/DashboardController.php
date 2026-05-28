<?php

require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Member.php';
require_once __DIR__ . '/../Models/Visitor.php';
require_once __DIR__ . '/../Models/Attendance.php';
require_once __DIR__ . '/../Models/Finance.php';
require_once __DIR__ . '/../Models/Department.php';

class DashboardController extends BaseController {
    public function index() {
        $cacheTtl = 90; // seconds
        $currentUserId = (int)(Session::get('user_id') ?? 0);
        $currentRole = (string)(Session::get('user_role') ?? 'guest');
        $cacheKey = 'dashboard_cache_' . $currentRole . '_' . $currentUserId;
        $cached = Session::get($cacheKey);
        if (is_array($cached) && isset($cached['generated_at'], $cached['payload'])) {
            $age = time() - (int)$cached['generated_at'];
            if ($age >= 0 && $age <= $cacheTtl) {
                View::render('dashboard.index', $cached['payload']);
                return;
            }
        }

        $memberModel = new Member();
        $visitorModel = new Visitor();
        $attendanceModel = new Attendance();
        $financeModel = new Finance();
        $deptModel = new Department();

        $memberStats = $memberModel->getStats();
        $isDeptHead = Auth::isDepartmentHead();
        $isStaff = Auth::isStaff();
        $isFinance = Auth::isFinance();
        $isFinanceHead = Auth::isFinanceHead();
        $isVisitationTeam = Auth::isVisitationTeam();
        $myDeptId = $isDeptHead ? (int)(Session::get('user_department_id') ?? 0) : 0;
        $deptContext = null;
        $staffContext = null;
        $financeDeptContext = null;
        $visitationContext = null;
        $visitorInsights = null;
        $changeRequestContext = [
            'pending' => [],
            'mine' => []
        ];

        if ($isVisitationTeam || (!$isDeptHead && !$isStaff && !$isVisitationTeam)) {
            $this->ensureVisitorSchema();
        }

        if ($isStaff) {
            if ($isFinance) {
                $db = Database::getInstance();
                $this->ensureDepartmentExpenseRequestSchema();
                $month = (int)date('m');
                $year = (int)date('Y');

                $monthlyIncome = $financeModel->getMonthlyIncome($month, $year, null, null);
                $monthlyExpenses = $financeModel->getMonthlyExpenses($month, $year, null, null);
                $monthlyBalance = $monthlyIncome - $monthlyExpenses;
                $generalAllTime = $financeModel->getGeneralTotals();
                $departmentAllTime = $financeModel->getDepartmentAggregateTotalsAllTime();
                $combinedAllTime = [
                    'income_total' => (float)($generalAllTime['income_total'] ?? 0) + (float)($departmentAllTime['income_total'] ?? 0),
                    'expense_total' => (float)($generalAllTime['expense_total'] ?? 0) + (float)($departmentAllTime['expense_total'] ?? 0),
                    'balance' => (float)($generalAllTime['balance'] ?? 0) + (float)($departmentAllTime['balance'] ?? 0)
                ];

                $pendingDeptExpenseRequests = [];
                try {
                    if ($db->tableExists('department_expense_requests')) {
                        $pendingDeptExpenseRequests = $db->fetchAll(
                            "SELECT r.*,
                                    d.name as department_name,
                                    requester.name as requested_by_name
                             FROM department_expense_requests r
                             INNER JOIN departments d ON d.id = r.department_id
                             INNER JOIN users requester ON requester.id = r.requested_by
                             WHERE LOWER(COALESCE(r.status, 'pending')) = 'pending'
                             ORDER BY r.created_at DESC, r.id DESC
                             LIMIT 12"
                        ) ?: [];
                    }
                } catch (Throwable $e) {
                    $pendingDeptExpenseRequests = [];
                }

                $financeDeptContext = [
                    'month_label' => date('F Y'),
                    'monthly_income' => $monthlyIncome,
                    'monthly_expenses' => $monthlyExpenses,
                    'monthly_balance' => $monthlyBalance,
                    'combined_all_time' => $combinedAllTime,
                    'recent_transactions' => $financeModel->getRecentTransactionsWithMeta(15),
                    'department_savings' => $financeModel->getDepartmentSavingsSummary($month, $year, null, null),
                    'pending_dept_expense_requests' => $pendingDeptExpenseRequests
                ];
            } else {
                $allowedTypes = ['Offering', 'Tithe', 'Departmental Savings', 'Welfare'];
                $staffSummary = $financeModel->getRecorderTotals((int)Session::get('user_id'), date('m'), date('Y'), $allowedTypes);
                $staffTransactions = $financeModel->getRecentTransactionsByRecorderWithMeta((int)Session::get('user_id'), 12, $allowedTypes);
                $staffChangeRequests = $financeModel->getChangeRequestsForRequester((int)Session::get('user_id'), 8);
                
                // Fetch members and departments for quick entry
                $members = $memberModel->all('first_name ASC, last_name ASC');
                $departments = $deptModel->all('name ASC');

                $staffContext = [
                    'summary' => $staffSummary,
                    'transactions' => $staffTransactions,
                    'allowed_types' => $allowedTypes,
                    'month_label' => date('F Y'),
                    'change_requests' => $staffChangeRequests,
                    'members' => $members,
                    'departments' => $departments
                ];
            }
        }

        if ($isVisitationTeam) {
            $assignedVisitors = $visitorModel->getVisitationAssignments(null, (int)Session::get('user_id'));
            $allVisitorsTotal = 0;
            try {
                $db = Database::getInstance();
                $row = $db->fetch("SELECT COUNT(*) as c FROM visitors");
                $allVisitorsTotal = (int)($row['c'] ?? 0);
            } catch (Throwable $e) {
                $allVisitorsTotal = 0;
            }
            $pending = 0;
            $completed = 0;
            $firstTime = 0;
            $scheduled = 0;

            foreach ($assignedVisitors as $visitor) {
                if (($visitor['follow_up_status'] ?? '') === 'Completed') {
                    $completed++;
                } else {
                    $pending++;
                }

                if (!empty($visitor['is_first_time'])) {
                    $firstTime++;
                }

                if (!empty($visitor['follow_up_date'])) {
                    $scheduled++;
                }
            }

            $visitationContext = [
                'visitors' => $assignedVisitors,
                'summary' => [
                    'all_visitors_total' => $allVisitorsTotal,
                    'assigned_total' => count($assignedVisitors),
                    'pending_total' => $pending,
                    'completed_total' => $completed,
                    'first_time_total' => $firstTime,
                    'scheduled_total' => $scheduled,
                ],
            ];
        }

        if (!$isStaff && $isDeptHead && $myDeptId > 0) {
            $db = Database::getInstance();
            $this->ensureDepartmentExpenseRequestSchema();
            $dept = $deptModel->find($myDeptId);
            $deptMembers = $db->fetchAll(
                "SELECT id, first_name, last_name, phone, photo_path
                 FROM members
                 WHERE department_id = ?
                 ORDER BY first_name ASC, last_name ASC",
                [$myDeptId]
            );

            $month = (int)date('m');
            $year = (int)date('Y');
            $deptIncome = $financeModel->getMonthlyIncome($month, $year, $myDeptId, null);
            $deptExpenses = $financeModel->getMonthlyExpenses($month, $year, $myDeptId, null);
            $deptBalance = $deptIncome - $deptExpenses;
            $deptTotalsAllTime = $financeModel->getDepartmentTotals($myDeptId, null);
            $deptTx = $financeModel->getRecentDepartmentTransactionsWithMeta($myDeptId, 15, null);
            $deptExpenseRequests = [];
            try {
                if ($db->tableExists('department_expense_requests')) {
                    $deptExpenseRequests = $db->fetchAll(
                        "SELECT r.*,
                                approver.name as approved_by_name,
                                rejecter.name as rejected_by_name
                         FROM department_expense_requests r
                         LEFT JOIN users approver ON approver.id = r.approved_by
                         LEFT JOIN users rejecter ON rejecter.id = r.rejected_by
                         WHERE r.department_id = ?
                         ORDER BY r.created_at DESC, r.id DESC
                         LIMIT 25",
                        [$myDeptId]
                    ) ?: [];
                }
            } catch (Throwable $e) {
                $deptExpenseRequests = [];
            }

            $deptContext = [
                'department' => $dept,
                'members' => $deptMembers,
                'month_label' => date('F Y'),
                'income' => $deptIncome,
                'expenses' => $deptExpenses,
                'net' => $deptBalance,
                'total_balance' => (float)($deptTotalsAllTime['balance'] ?? 0),
                'transactions' => $deptTx,
                'expense_requests' => $deptExpenseRequests
            ];
        }

        if (!$isDeptHead && !$isStaff && !$isVisitationTeam) {
            $allVisitors = $visitorModel->getAllWithAssignee();
            $recentVisitors = array_slice($allVisitors, 0, 5);
            $changeRequestContext['pending'] = $financeModel->getPendingChangeRequests(8);
            $today = date('Y-m-d');
            $currentMonth = date('Y-m');
            $visitorSummary = [
                'total' => count($allVisitors),
                'pending' => 0,
                'completed' => 0,
                'first_time' => 0,
                'today' => 0,
                'this_month' => 0,
            ];

            foreach ($allVisitors as $visitor) {
                $visitDate = (string)($visitor['visit_date'] ?? '');
                if (($visitor['follow_up_status'] ?? '') === 'Completed') {
                    $visitorSummary['completed']++;
                } else {
                    $visitorSummary['pending']++;
                }

                if (!empty($visitor['is_first_time'])) {
                    $visitorSummary['first_time']++;
                }

                if ($visitDate === $today) {
                    $visitorSummary['today']++;
                }

                if ($visitDate !== '' && strpos($visitDate, $currentMonth) === 0) {
                    $visitorSummary['this_month']++;
                }
            }

            $visitorInsights = [
                'summary' => $visitorSummary,
                'recent' => $recentVisitors,
            ];
        }

        $data = [
            'title' => 'Dashboard Overview',
            'isDeptHead' => $isDeptHead,
            'isStaff' => $isStaff,
            'isFinance' => $isFinance,
            'isFinanceHead' => $isFinanceHead,
            'isVisitationTeam' => $isVisitationTeam,
            'dept' => $deptContext,
            'staff' => $staffContext,
            'financeDept' => $financeDeptContext,
            'visitation' => $visitationContext,
            'visitorInsights' => $visitorInsights,
            'changeRequests' => $changeRequestContext,
            'stats' => [
                'total_members' => $memberStats['total'],
                'recent_growth' => $memberStats['new'],
                'attendance_rate' => $attendanceModel->getAttendanceRate(),
                'monthly_donations' => '$' . number_format($financeModel->getMonthlyTotal(), 2),
                'birthday_count' => $memberModel->getBirthdayCountThisMonth()
            ],
            'birthdays' => $memberModel->getUpcomingBirthdays(10),
            'demographics' => [
                'gender' => $memberModel->getGenderDistribution(),
                'age' => $memberModel->getAgeDistribution()
            ]
        ];

        Session::set($cacheKey, [
            'generated_at' => time(),
            'payload' => $data
        ]);
        
        View::render('dashboard.index', $data);
    }

    public function birthdaysThisMonth() {
        $memberModel = new Member();
        $birthdays = $memberModel->getBirthdaysThisMonth();

        header('Content-Type: application/json');
        echo json_encode([
            'count' => count($birthdays),
            'birthdays' => $birthdays
        ]);
        exit;
    }

    private function ensureDepartmentExpenseRequestSchema() {
        $db = Database::getInstance();
        SchemaState::once('department_expense_request_schema', function () use ($db) {
            if (!$db->tableExists('department_expense_requests')) {
                if ($db->isPgsql()) {
                    $db->rawExec(
                        "CREATE TABLE IF NOT EXISTS department_expense_requests (
                            id BIGSERIAL PRIMARY KEY,
                            department_id INTEGER NOT NULL REFERENCES departments(id) ON DELETE CASCADE,
                            requested_by INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                            amount NUMERIC(14,2) NOT NULL,
                            purpose TEXT NOT NULL,
                            status VARCHAR(20) NOT NULL DEFAULT 'pending',
                            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            approved_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
                            approved_at TIMESTAMPTZ NULL,
                            rejected_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
                            rejected_at TIMESTAMPTZ NULL,
                            finance_id INTEGER NULL REFERENCES finances(id) ON DELETE SET NULL
                        );
                        CREATE INDEX IF NOT EXISTS idx_dep_expense_requests_department_id ON department_expense_requests (department_id);
                        CREATE INDEX IF NOT EXISTS idx_dep_expense_requests_status ON department_expense_requests (status);
                        CREATE INDEX IF NOT EXISTS idx_dep_expense_requests_created_at ON department_expense_requests (created_at);"
                    );
                } else {
                    $db->rawExec(
                        "CREATE TABLE IF NOT EXISTS department_expense_requests (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            department_id INT NOT NULL,
                            requested_by INT NOT NULL,
                            amount DECIMAL(14,2) NOT NULL,
                            purpose TEXT NOT NULL,
                            status VARCHAR(20) NOT NULL DEFAULT 'pending',
                            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            approved_by INT NULL,
                            approved_at DATETIME NULL,
                            rejected_by INT NULL,
                            rejected_at DATETIME NULL,
                            finance_id INT NULL,
                            KEY idx_dep_expense_requests_department_id (department_id),
                            KEY idx_dep_expense_requests_status (status),
                            KEY idx_dep_expense_requests_created_at (created_at),
                            CONSTRAINT fk_dep_expense_requests_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
                            CONSTRAINT fk_dep_expense_requests_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
                            CONSTRAINT fk_dep_expense_requests_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
                            CONSTRAINT fk_dep_expense_requests_rejected_by FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL,
                            CONSTRAINT fk_dep_expense_requests_finance FOREIGN KEY (finance_id) REFERENCES finances(id) ON DELETE SET NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                    );
                }
            }
        });
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
                'approved_by' => "ALTER TABLE visitors ADD COLUMN approved_by INT NULL",
                'approved_at' => "ALTER TABLE visitors ADD COLUMN approved_at " . ($db->isPgsql() ? "TIMESTAMPTZ NULL" : "DATETIME NULL")
            ];

            foreach ($columns as $columnName => $sql) {
                if (!$db->columnExists('visitors', $columnName)) {
                    $db->query($sql);
                }
            }
        });
    }
}
