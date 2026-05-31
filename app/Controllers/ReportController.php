<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Finance.php';
require_once __DIR__ . '/../Models/Member.php';
class ReportController extends BaseController {
    public function index() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        if (Auth::isStaff() && !Auth::isFinanceHead()) {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }

        $db = Database::getInstance();
        $this->ensureMemberDepartmentSchema($db);
        $series = $this->buildSixMonthSeries($db);
        $monthLabels = $series['monthLabels'];
        $incomeSeries = $series['incomeSeries'];
        $expenseSeries = $series['expenseSeries'];
        $newMembersSeries = $series['newMembersSeries'];

        $genderRows = $db->fetchAll("SELECT LOWER(gender) as gender, COUNT(*) as c FROM members GROUP BY LOWER(gender)");
        $genderMap = ['male' => 0, 'female' => 0, 'other' => 0];
        foreach ($genderRows as $r) {
            $g = (string)($r['gender'] ?? '');
            if ($g === 'm') $g = 'male';
            if ($g === 'f') $g = 'female';
            if (!array_key_exists($g, $genderMap)) $g = 'other';
            $genderMap[$g] += (int)($r['c'] ?? 0);
        }

        $deptRows = $db->fetchAll(
            "SELECT d.name as department_name, COUNT(DISTINCT assigned.member_id) as member_count
             FROM departments d
             LEFT JOIN (
                 SELECT id as member_id, department_id
                 FROM members
                 WHERE department_id IS NOT NULL
                 UNION ALL
                 SELECT member_id, department_id
                 FROM member_departments
             ) assigned ON assigned.department_id = d.id
             GROUP BY d.id, d.name
             ORDER BY member_count DESC, d.name ASC
             LIMIT 10"
        );

        $typeRows = $db->fetchAll(
            "SELECT transaction_type, COALESCE(SUM(amount), 0) as total
             FROM finances
             WHERE transaction_type <> 'Expense'
             GROUP BY transaction_type
             ORDER BY total DESC
             LIMIT 8"
        );

        $totals = $this->getDashboardTotals($db);
        $totals['balance'] = $totals['income'] - $totals['expenses'];

        $currency = strtoupper(trim((string)(AppConfig::getSetting('finance_currency', 'GHS'))));
        if (!preg_match('/^[A-Z]{2,5}$/', $currency)) $currency = 'GHS';

        View::render('reports.index', [
            'title' => 'Reports & Analytics',
            'currency' => $currency,
            'monthLabels' => $monthLabels,
            'incomeSeries' => $incomeSeries,
            'expenseSeries' => $expenseSeries,
            'newMembersSeries' => $newMembersSeries,
            'genderMap' => $genderMap,
            'deptRows' => $deptRows,
            'typeRows' => $typeRows,
            'totals' => $totals
        ]);
    }

    public function download() {
        if (!Auth::isAdmin() && !Auth::isAuditor() && !Auth::isFinanceHead() && !Auth::isPastor()) {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/reports");
            exit;
        }
        $type = strtolower(trim($_GET['type'] ?? ''));

        $allowed = [
            'finance_summary',
            'income_expense_6m',
            'members_summary',
            'department_members'
        ];

        if (!in_array($type, $allowed, true)) {
            Session::flash('error', 'Invalid report type.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/reports");
            exit;
        }

        $db = Database::getInstance();
        $this->ensureMemberDepartmentSchema($db);
        $filename = $type . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if (!$out) exit;

        if ($type === 'finance_summary') {
            fputcsv($out, ['metric', 'value']);
            $totals = $this->getDashboardTotals($db);
            fputcsv($out, ['total_income', $totals['income']]);
            fputcsv($out, ['total_expenses', $totals['expenses']]);
            fputcsv($out, ['balance', $totals['income'] - $totals['expenses']]);
            fputcsv($out, ['transactions_count', $totals['transactions']]);
            fclose($out);
            exit;
        }

        if ($type === 'income_expense_6m') {
            fputcsv($out, ['month', 'income', 'expenses', 'new_members']);
            $series = $this->buildSixMonthSeries($db);
            $rowCount = count($series['monthLabels']);
            for ($i = 0; $i < $rowCount; $i++) {
                fputcsv($out, [
                    $series['monthLabels'][$i],
                    $series['incomeSeries'][$i],
                    $series['expenseSeries'][$i],
                    $series['newMembersSeries'][$i]
                ]);
            }
            fclose($out);
            exit;
        }

        if ($type === 'members_summary') {
            fputcsv($out, ['metric', 'value']);
            $memberSummary = $db->fetch(
                "SELECT
                    COUNT(*) as total_members,
                    COALESCE(SUM(CASE WHEN membership_status = 'Active' THEN 1 ELSE 0 END), 0) as active_members,
                    COALESCE(SUM(CASE WHEN LOWER(gender) IN ('male', 'm') THEN 1 ELSE 0 END), 0) as male_members,
                    COALESCE(SUM(CASE WHEN LOWER(gender) IN ('female', 'f') THEN 1 ELSE 0 END), 0) as female_members
                 FROM members"
            ) ?: [];
            $total = (int)($memberSummary['total_members'] ?? 0);
            $male = (int)($memberSummary['male_members'] ?? 0);
            $female = (int)($memberSummary['female_members'] ?? 0);
            fputcsv($out, ['total_members', $total]);
            fputcsv($out, ['active_members', (int)($memberSummary['active_members'] ?? 0)]);
            fputcsv($out, ['male', $male]);
            fputcsv($out, ['female', $female]);
            fputcsv($out, ['other', max(0, $total - $male - $female)]);
            fclose($out);
            exit;
        }

        if ($type === 'department_members') {
            fputcsv($out, ['department', 'member_count']);
            $rows = $db->fetchAll(
                "SELECT d.name as department_name, COUNT(DISTINCT assigned.member_id) as member_count
                 FROM departments d
                 LEFT JOIN (
                     SELECT id as member_id, department_id
                     FROM members
                     WHERE department_id IS NOT NULL
                     UNION ALL
                     SELECT member_id, department_id
                     FROM member_departments
                 ) assigned ON assigned.department_id = d.id
                 GROUP BY d.id, d.name
                 ORDER BY member_count DESC, d.name ASC"
            );
            foreach ($rows as $r) {
                fputcsv($out, [(string)($r['department_name'] ?? ''), (int)($r['member_count'] ?? 0)]);
            }
            fclose($out);
            exit;
        }

        fclose($out);
        exit;
    }

    private function buildSixMonthSeries($db) {
        $months = [];
        $monthLabels = [];
        $incomeSeries = [];
        $expenseSeries = [];
        $newMembersSeries = [];

        $startTs = strtotime(date('Y-m-01') . ' -5 months');
        $rangeStart = date('Y-m-01', $startTs);
        $rangeEnd = date('Y-m-01', strtotime(date('Y-m-01') . ' +1 month'));

        $financeRows = $db->fetchAll(
            ($db->isPgsql()
                ? "SELECT
                    TO_CHAR(transaction_date, 'YYYY-MM-01') as month_key,
                    COALESCE(SUM(CASE WHEN transaction_type <> 'Expense' THEN amount ELSE 0 END), 0) as income_total,
                    COALESCE(SUM(CASE WHEN transaction_type = 'Expense' THEN amount ELSE 0 END), 0) as expense_total
                 FROM finances
                 WHERE transaction_date >= ?
                   AND transaction_date < ?
                 GROUP BY TO_CHAR(transaction_date, 'YYYY-MM-01')"
                : "SELECT
                    DATE_FORMAT(transaction_date, '%Y-%m-01') as month_key,
                    COALESCE(SUM(CASE WHEN transaction_type <> 'Expense' THEN amount ELSE 0 END), 0) as income_total,
                    COALESCE(SUM(CASE WHEN transaction_type = 'Expense' THEN amount ELSE 0 END), 0) as expense_total
                 FROM finances
                 WHERE transaction_date >= ?
                   AND transaction_date < ?
                 GROUP BY DATE_FORMAT(transaction_date, '%Y-%m-01')"),
            [$rangeStart, $rangeEnd]
        );
        $financeMap = [];
        foreach ($financeRows as $row) {
            $financeMap[(string)($row['month_key'] ?? '')] = [
                'income' => (float)($row['income_total'] ?? 0),
                'expenses' => (float)($row['expense_total'] ?? 0)
            ];
        }

        $memberRows = $db->fetchAll(
            ($db->isPgsql()
                ? "SELECT
                    TO_CHAR(join_date, 'YYYY-MM-01') as month_key,
                    COUNT(*) as total
                 FROM members
                 WHERE join_date >= ?
                   AND join_date < ?
                 GROUP BY TO_CHAR(join_date, 'YYYY-MM-01')"
                : "SELECT
                    DATE_FORMAT(join_date, '%Y-%m-01') as month_key,
                    COUNT(*) as total
                 FROM members
                 WHERE join_date >= ?
                   AND join_date < ?
                 GROUP BY DATE_FORMAT(join_date, '%Y-%m-01')"),
            [$rangeStart, $rangeEnd]
        );
        $memberMap = [];
        foreach ($memberRows as $row) {
            $memberMap[(string)($row['month_key'] ?? '')] = (int)($row['total'] ?? 0);
        }

        for ($i = 0; $i < 6; $i++) {
            $ts = strtotime($rangeStart . " +$i months");
            $monthKey = date('Y-m-01', $ts);
            $months[] = $monthKey;
            $monthLabels[] = date('M Y', $ts);
            $incomeSeries[] = (float)($financeMap[$monthKey]['income'] ?? 0);
            $expenseSeries[] = (float)($financeMap[$monthKey]['expenses'] ?? 0);
            $newMembersSeries[] = (int)($memberMap[$monthKey] ?? 0);
        }

        return [
            'months' => $months,
            'monthLabels' => $monthLabels,
            'incomeSeries' => $incomeSeries,
            'expenseSeries' => $expenseSeries,
            'newMembersSeries' => $newMembersSeries
        ];
    }

    private function getDashboardTotals($db) {
        $memberTotals = $db->fetch(
            "SELECT
                COUNT(*) as members,
                COALESCE(SUM(CASE WHEN membership_status = 'Active' THEN 1 ELSE 0 END), 0) as active_members
             FROM members"
        ) ?: [];

        $financeTotals = $db->fetch(
            "SELECT
                COUNT(*) as transactions,
                COALESCE(SUM(CASE WHEN transaction_type <> 'Expense' THEN amount ELSE 0 END), 0) as income,
                COALESCE(SUM(CASE WHEN transaction_type = 'Expense' THEN amount ELSE 0 END), 0) as expenses
             FROM finances"
        ) ?: [];

        return [
            'members' => (int)($memberTotals['members'] ?? 0),
            'active_members' => (int)($memberTotals['active_members'] ?? 0),
            'transactions' => (int)($financeTotals['transactions'] ?? 0),
            'income' => (float)($financeTotals['income'] ?? 0),
            'expenses' => (float)($financeTotals['expenses'] ?? 0)
        ];
    }

    private function ensureMemberDepartmentSchema($db) {
        if ($db->tableExists('member_departments')) {
            return;
        }

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
}
