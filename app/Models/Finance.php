<?php
require_once __DIR__ . '/BaseModel.php';

class Finance extends BaseModel {
    protected $table = 'finances';

    public function getMonthlyTotal($type = 'Offering', $month = null, $year = null) {
        [$startDate, $endDate] = $this->getMonthDateRange($month, $year);
        
        $sql = "SELECT SUM(amount) as total FROM finances 
                WHERE transaction_type = ? 
                AND transaction_date >= ?
                AND transaction_date < ?";
        $result = $this->db->fetch($sql, [$type, $startDate, $endDate]);
        return $result['total'] ?? 0;
    }

    public function getBalance() {
        $income = $this->db->fetch("SELECT SUM(amount) as total FROM finances WHERE transaction_type <> 'Expense'")['total'] ?? 0;
        $expense = $this->db->fetch("SELECT SUM(amount) as total FROM finances WHERE transaction_type = 'Expense'")['total'] ?? 0;

        return (float)$income - (float)$expense;
    }

    public function getChurchTotals($paymentMethod = null) {
        $where = " WHERE 1=1";
        $params = [];
        if ($paymentMethod !== null) {
            $where .= " AND payment_method = ?";
            $params[] = (string)$paymentMethod;
        }

        $incomeRow = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM finances
             $where
               AND transaction_type <> 'Expense'",
            $params
        );
        $expenseRow = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM finances
             $where
               AND transaction_type = 'Expense'",
            $params
        );
        $countRow = $this->db->fetch(
            "SELECT COUNT(*) as total
             FROM finances
             $where",
            $params
        );

        $income = (float)($incomeRow['total'] ?? 0);
        $expense = (float)($expenseRow['total'] ?? 0);

        return [
            'income_total' => $income,
            'expense_total' => $expense,
            'balance' => $income - $expense,
            'transaction_count' => (int)($countRow['total'] ?? 0)
        ];
    }

    public function getGeneralTotals($paymentMethod = null) {
        $where = " WHERE department_id IS NULL";
        $params = [];
        if ($paymentMethod !== null) {
            $where .= " AND payment_method = ?";
            $params[] = (string)$paymentMethod;
        }

        $incomeRow = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM finances
             $where
               AND transaction_type <> 'Expense'",
            $params
        );
        $expenseRow = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM finances
             $where
               AND transaction_type = 'Expense'",
            $params
        );
        $countRow = $this->db->fetch(
            "SELECT COUNT(*) as total
             FROM finances
             $where",
            $params
        );

        $income = (float)($incomeRow['total'] ?? 0);
        $expense = (float)($expenseRow['total'] ?? 0);

        return [
            'income_total' => $income,
            'expense_total' => $expense,
            'balance' => $income - $expense,
            'transaction_count' => (int)($countRow['total'] ?? 0)
        ];
    }

    public function getMonthlyIncome($month = null, $year = null, $departmentId = null, $paymentMethod = null) {
        [$startDate, $endDate] = $this->getMonthDateRange($month, $year);

        $sql = "SELECT SUM(amount) as total
                FROM finances
                WHERE transaction_type <> 'Expense'
                  AND transaction_date >= ?
                  AND transaction_date < ?";
        $params = [$startDate, $endDate];
        if ($departmentId !== null) {
            $sql .= " AND department_id = ?";
            $params[] = (int)$departmentId;
        }
        if ($paymentMethod !== null) {
            $sql .= " AND payment_method = ?";
            $params[] = (string)$paymentMethod;
        }
        $row = $this->db->fetch($sql, $params);
        return (float)($row['total'] ?? 0);
    }

    public function getMonthlyExpenses($month = null, $year = null, $departmentId = null, $paymentMethod = null) {
        [$startDate, $endDate] = $this->getMonthDateRange($month, $year);

        $sql = "SELECT SUM(amount) as total
                FROM finances
                WHERE transaction_type = 'Expense'
                  AND transaction_date >= ?
                  AND transaction_date < ?";
        $params = [$startDate, $endDate];
        if ($departmentId !== null) {
            $sql .= " AND department_id = ?";
            $params[] = (int)$departmentId;
        }
        if ($paymentMethod !== null) {
            $sql .= " AND payment_method = ?";
            $params[] = (string)$paymentMethod;
        }
        $row = $this->db->fetch($sql, $params);
        return (float)($row['total'] ?? 0);
    }

    public function getMonthlyBalance($month = null, $year = null) {
        return $this->getMonthlyIncome($month, $year) - $this->getMonthlyExpenses($month, $year);
    }

    public function getDepartmentTotals($departmentId, $paymentMethod = null) {
        $departmentId = (int)$departmentId;
        if ($departmentId <= 0) {
            return [
                'income_total' => 0.0,
                'expense_total' => 0.0,
                'balance' => 0.0,
                'transaction_count' => 0
            ];
        }

        $where = " WHERE department_id = ?";
        $params = [$departmentId];
        if ($paymentMethod !== null) {
            $where .= " AND payment_method = ?";
            $params[] = (string)$paymentMethod;
        }

        $incomeRow = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM finances
             $where
               AND transaction_type <> 'Expense'",
            $params
        );

        $expenseRow = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM finances
             $where
               AND transaction_type = 'Expense'",
            $params
        );

        $countRow = $this->db->fetch(
            "SELECT COUNT(*) as total
             FROM finances
             $where",
            $params
        );

        $income = (float)($incomeRow['total'] ?? 0);
        $expense = (float)($expenseRow['total'] ?? 0);

        return [
            'income_total' => $income,
            'expense_total' => $expense,
            'balance' => $income - $expense,
            'transaction_count' => (int)($countRow['total'] ?? 0)
        ];
    }

    public function getDepartmentSavingsSummary($month = null, $year = null, $departmentId = null, $paymentMethod = null) {
        [$startDate, $endDate] = $this->getMonthDateRange($month, $year);

        $sql = "SELECT
                    d.id as department_id,
                    d.name as department_name,
                    COALESCE(SUM(CASE WHEN f.transaction_type <> 'Expense' THEN f.amount ELSE 0 END), 0) as income_total,
                    COALESCE(SUM(CASE WHEN f.transaction_type = 'Expense' THEN f.amount ELSE 0 END), 0) as expense_total
                FROM departments d
                LEFT JOIN finances f
                    ON f.department_id = d.id
                   AND f.transaction_date >= ?
                   AND f.transaction_date < ?
                WHERE 1=1";
        $params = [$startDate, $endDate];
        if ($departmentId !== null) {
            $sql .= " AND d.id = ?";
            $params[] = (int)$departmentId;
        }
        if ($paymentMethod !== null) {
            $sql .= " AND f.payment_method = ?";
            $params[] = (string)$paymentMethod;
        }
        $sql .= " GROUP BY d.id, d.name
                  ORDER BY d.name ASC";

        $rows = $this->db->fetchAll($sql, $params);
        return array_map(function ($r) {
            $income = (float)($r['income_total'] ?? 0);
            $expense = (float)($r['expense_total'] ?? 0);
            $r['income_total'] = $income;
            $r['expense_total'] = $expense;
            $r['balance'] = $income - $expense;
            return $r;
        }, $rows ?: []);
    }

    public function getDepartmentAggregateTotals($month = null, $year = null, $departmentId = null, $paymentMethod = null) {
        $rows = $this->getDepartmentSavingsSummary($month, $year, $departmentId, $paymentMethod);
        $income = 0.0;
        $expense = 0.0;
        $count = 0;

        foreach ($rows as $row) {
            $income += (float)($row['income_total'] ?? 0);
            $expense += (float)($row['expense_total'] ?? 0);
            if (((float)($row['income_total'] ?? 0)) > 0 || ((float)($row['expense_total'] ?? 0)) > 0) {
                $count++;
            }
        }

        return [
            'income_total' => $income,
            'expense_total' => $expense,
            'balance' => $income - $expense,
            'department_count' => $count
        ];
    }

    public function getDepartmentAggregateTotalsAllTime($departmentId = null, $paymentMethod = null) {
        $sql = "SELECT
                    d.id as department_id,
                    COALESCE(SUM(CASE WHEN f.transaction_type <> 'Expense' THEN f.amount ELSE 0 END), 0) as income_total,
                    COALESCE(SUM(CASE WHEN f.transaction_type = 'Expense' THEN f.amount ELSE 0 END), 0) as expense_total
                FROM departments d
                LEFT JOIN finances f ON f.department_id = d.id
                WHERE 1=1";
        $params = [];
        if ($departmentId !== null) {
            $sql .= " AND d.id = ?";
            $params[] = (int)$departmentId;
        }
        if ($paymentMethod !== null) {
            $sql .= " AND f.payment_method = ?";
            $params[] = (string)$paymentMethod;
        }
        $sql .= " GROUP BY d.id";

        $rows = $this->db->fetchAll($sql, $params) ?: [];
        $income = 0.0;
        $expense = 0.0;
        $count = 0;
        foreach ($rows as $row) {
            $income += (float)($row['income_total'] ?? 0);
            $expense += (float)($row['expense_total'] ?? 0);
            if (((float)($row['income_total'] ?? 0)) > 0 || ((float)($row['expense_total'] ?? 0)) > 0) {
                $count++;
            }
        }

        return [
            'income_total' => $income,
            'expense_total' => $expense,
            'balance' => $income - $expense,
            'department_count' => $count
        ];
    }

    public function getRecentTransactionsWithMeta($limit = 50) {
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 50;
        if ($limit > 200) $limit = 200;

        $sql = "SELECT
                    f.*,
                    CONCAT(COALESCE(m.first_name,''), ' ', COALESCE(m.last_name,'')) as member_name,
                    d.name as department_name,
                    u.name as recorded_by_name
                FROM finances f
                LEFT JOIN members m ON m.id = f.member_id
                LEFT JOIN departments d ON d.id = f.department_id
                LEFT JOIN users u ON u.id = f.recorded_by
                ORDER BY f.transaction_date DESC, f.id DESC
                LIMIT $limit";
        return $this->db->fetchAll($sql);
    }

    public function getRecentDepartmentTransactionsWithMeta($departmentId, $limit = 50, $paymentMethod = null) {
        $departmentId = (int)$departmentId;
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 50;
        if ($limit > 200) $limit = 200;

        $sql = "SELECT
                    f.*,
                    CONCAT(COALESCE(m.first_name,''), ' ', COALESCE(m.last_name,'')) as member_name,
                    d.name as department_name,
                    u.name as recorded_by_name
                FROM finances f
                LEFT JOIN members m ON m.id = f.member_id
                LEFT JOIN departments d ON d.id = f.department_id
                LEFT JOIN users u ON u.id = f.recorded_by
                WHERE f.department_id = ?";
        $params = [$departmentId];
        if ($paymentMethod !== null) {
            $sql .= " AND f.payment_method = ?";
            $params[] = (string)$paymentMethod;
        }
        $sql .= "
                ORDER BY f.transaction_date DESC, f.id DESC
                LIMIT $limit";
        return $this->db->fetchAll($sql, $params);
    }

    public function getRecorderTotals($userId, $month = null, $year = null, array $allowedTypes = []) {
        $userId = (int)$userId;
        [$monthStart, $monthEnd] = $this->getMonthDateRange($month, $year);
        [$todayStart, $todayEnd] = $this->getDayDateRange();

        if ($userId <= 0) {
            return [
                'today_total' => 0.0,
                'month_total' => 0.0,
                'overall_total' => 0.0,
                'transaction_count' => 0,
                'by_type' => []
            ];
        }

        $typeSql = '';
        $typeParams = [];
        if (!empty($allowedTypes)) {
            $placeholders = implode(',', array_fill(0, count($allowedTypes), '?'));
            $typeSql = " AND transaction_type IN ($placeholders)";
            $typeParams = array_values($allowedTypes);
        }

        $todayRow = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM finances
             WHERE recorded_by = ?
               AND transaction_date >= ?
               AND transaction_date < ?
               $typeSql",
            array_merge([$userId, $todayStart, $todayEnd], $typeParams)
        );

        $monthRow = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM finances
             WHERE recorded_by = ?
               AND transaction_date >= ?
               AND transaction_date < ?
               $typeSql",
            array_merge([$userId, $monthStart, $monthEnd], $typeParams)
        );

        $overallRow = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total, COUNT(*) as count_total
             FROM finances
             WHERE recorded_by = ?
               $typeSql",
            array_merge([$userId], $typeParams)
        );

        $byType = $this->db->fetchAll(
            "SELECT transaction_type, COALESCE(SUM(amount), 0) as total, COUNT(*) as count_total
             FROM finances
             WHERE recorded_by = ?
               $typeSql
             GROUP BY transaction_type
             ORDER BY transaction_type ASC",
            array_merge([$userId], $typeParams)
        );

        return [
            'today_total' => (float)($todayRow['total'] ?? 0),
            'month_total' => (float)($monthRow['total'] ?? 0),
            'overall_total' => (float)($overallRow['total'] ?? 0),
            'transaction_count' => (int)($overallRow['count_total'] ?? 0),
            'by_type' => array_map(function ($row) {
                return [
                    'transaction_type' => (string)($row['transaction_type'] ?? ''),
                    'total' => (float)($row['total'] ?? 0),
                    'count_total' => (int)($row['count_total'] ?? 0)
                ];
            }, $byType ?: [])
        ];
    }

    private function getMonthDateRange($month = null, $year = null) {
        $month = (int)($month ?? date('m'));
        $year = (int)($year ?? date('Y'));

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-d', strtotime($startDate . ' +1 month'));

        return [$startDate, $endDate];
    }

    private function getDayDateRange() {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime($startDate . ' +1 day'));

        return [$startDate, $endDate];
    }

    public function getRecentTransactionsByRecorderWithMeta($userId, $limit = 50, array $allowedTypes = []) {
        $userId = (int)$userId;
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 50;
        if ($limit > 200) $limit = 200;

        $sql = "SELECT
                    f.*,
                    CONCAT(COALESCE(m.first_name,''), ' ', COALESCE(m.last_name,'')) as member_name,
                    d.name as department_name,
                    u.name as recorded_by_name
                FROM finances f
                LEFT JOIN members m ON m.id = f.member_id
                LEFT JOIN departments d ON d.id = f.department_id
                LEFT JOIN users u ON u.id = f.recorded_by
                WHERE f.recorded_by = ?";
        $params = [$userId];

        if (!empty($allowedTypes)) {
            $placeholders = implode(',', array_fill(0, count($allowedTypes), '?'));
            $sql .= " AND f.transaction_type IN ($placeholders)";
            $params = array_merge($params, array_values($allowedTypes));
        }

        $sql .= " ORDER BY f.transaction_date DESC, f.id DESC
                  LIMIT $limit";

        return $this->db->fetchAll($sql, $params);
    }

    public function getRecentExpensesWithMeta($limit = 50) {
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 50;
        if ($limit > 200) $limit = 200;

        $sql = "SELECT
                    f.*,
                    d.name as department_name,
                    u.name as recorded_by_name
                FROM finances f
                LEFT JOIN departments d ON d.id = f.department_id
                LEFT JOIN users u ON u.id = f.recorded_by
                WHERE f.transaction_type = 'Expense'
                ORDER BY f.transaction_date DESC, f.id DESC
                LIMIT $limit";
        return $this->db->fetchAll($sql);
    }

    public function getTransactionWithMeta($id) {
        $sql = "SELECT
                    f.*,
                    CONCAT(COALESCE(m.first_name,''), ' ', COALESCE(m.last_name,'')) as member_name,
                    d.name as department_name,
                    u.name as recorded_by_name
                FROM finances f
                LEFT JOIN members m ON m.id = f.member_id
                LEFT JOIN departments d ON d.id = f.department_id
                LEFT JOIN users u ON u.id = f.recorded_by
                WHERE f.id = ?";
        return $this->db->fetch($sql, [$id]);
    }

    public function getPendingChangeRequests($limit = 50) {
        $limit = max(1, min(200, (int)$limit));
        $sql = "SELECT
                    r.*,
                    f.transaction_number,
                    f.transaction_type,
                    f.amount as current_amount,
                    f.transaction_date as current_transaction_date,
                    f.reference_no as current_reference_no,
                    f.description as current_description,
                    CONCAT(COALESCE(m.first_name,''), ' ', COALESCE(m.last_name,'')) as member_name,
                    d.name as department_name,
                    requester.name as requested_by_name
                FROM finance_change_requests r
                INNER JOIN finances f ON f.id = r.finance_id
                LEFT JOIN members m ON m.id = f.member_id
                LEFT JOIN departments d ON d.id = f.department_id
                LEFT JOIN users requester ON requester.id = r.requested_by
                WHERE r.status = 'pending'
                ORDER BY r.created_at DESC, r.id DESC
                LIMIT $limit";
        return $this->db->fetchAll($sql) ?: [];
    }

    public function getChangeRequestsForRequester($userId, $limit = 50) {
        $userId = (int)$userId;
        $limit = max(1, min(200, (int)$limit));
        if ($userId <= 0) {
            return [];
        }

        $sql = "SELECT
                    r.*,
                    f.transaction_number,
                    f.transaction_type,
                    f.amount as current_amount,
                    f.transaction_date as current_transaction_date,
                    f.reference_no as current_reference_no,
                    f.description as current_description,
                    CONCAT(COALESCE(m.first_name,''), ' ', COALESCE(m.last_name,'')) as member_name,
                    d.name as department_name,
                    approver.name as approved_by_name,
                    rejector.name as rejected_by_name
                FROM finance_change_requests r
                INNER JOIN finances f ON f.id = r.finance_id
                LEFT JOIN members m ON m.id = f.member_id
                LEFT JOIN departments d ON d.id = f.department_id
                LEFT JOIN users approver ON approver.id = r.approved_by
                LEFT JOIN users rejector ON rejector.id = r.rejected_by
                WHERE r.requested_by = ?
                ORDER BY r.created_at DESC, r.id DESC
                LIMIT $limit";
        return $this->db->fetchAll($sql, [$userId]) ?: [];
    }

    public function getApprovedChangeRequestsForRequester($userId) {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return [];
        }

        $rows = $this->db->fetchAll(
            "SELECT *
             FROM finance_change_requests
             WHERE requested_by = ?
               AND status = 'approved'
               AND fulfilled_at IS NULL
               AND COALESCE(edit_count, 0) < 2
             ORDER BY created_at DESC, id DESC",
            [$userId]
        ) ?: [];

        $map = [];
        foreach ($rows as $row) {
            $map[(int)$row['finance_id']] = $row;
        }
        return $map;
    }

    public function getActiveChangeRequestForTransaction($financeId) {
        $financeId = (int)$financeId;
        if ($financeId <= 0) {
            return null;
        }

        return $this->db->fetch(
            "SELECT *
             FROM finance_change_requests
             WHERE finance_id = ?
               AND status IN ('pending', 'approved')
               AND fulfilled_at IS NULL
               AND COALESCE(edit_count, 0) < 2
             ORDER BY id DESC
             LIMIT 1",
            [$financeId]
        );
    }

    public function getApprovedChangeRequestForTransaction($financeId, $requestedBy = null) {
        $financeId = (int)$financeId;
        if ($financeId <= 0) {
            return null;
        }

        $sql = "SELECT *
                FROM finance_change_requests
                WHERE finance_id = ?
                  AND status = 'approved'
                  AND fulfilled_at IS NULL
                  AND COALESCE(edit_count, 0) < 2";
        $params = [$financeId];
        if ($requestedBy !== null) {
            $sql .= " AND requested_by = ?";
            $params[] = (int)$requestedBy;
        }
        $sql .= " ORDER BY id DESC LIMIT 1";

        return $this->db->fetch($sql, $params);
    }

    public function createChangeRequest(array $data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO finance_change_requests ($columns) VALUES ($placeholders)";
        $this->db->query($sql, array_values($data));
        return $this->db->getConnection()->lastInsertId();
    }

    public function updateChangeRequest($id, array $data) {
        $id = (int)$id;
        if ($id <= 0 || empty($data)) {
            return false;
        }

        $fields = [];
        $params = [];
        foreach ($data as $column => $value) {
            $fields[] = $column . ' = ?';
            $params[] = $value;
        }
        $params[] = $id;

        $sql = "UPDATE finance_change_requests
                SET " . implode(', ', $fields) . "
                WHERE id = ?";
        return $this->db->query($sql, $params);
    }

    public function getMemberTransactionSummary($memberId) {
        $memberId = (int)$memberId;
        if ($memberId <= 0) {
            return [
                'member_id' => 0,
                'income_total' => 0.0,
                'expense_total' => 0.0,
                'net_total' => 0.0,
                'by_type' => []
            ];
        }

        $incomeRow = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM finances
             WHERE member_id = ?
               AND department_id IS NULL
               AND transaction_type <> 'Expense'
               AND transaction_type <> 'Offering'",
            [$memberId]
        );
        $expenseRow = $this->db->fetch(
            "SELECT COALESCE(SUM(amount), 0) as total
             FROM finances
             WHERE member_id = ?
               AND department_id IS NULL
               AND transaction_type = 'Expense'",
            [$memberId]
        );

        $byType = $this->db->fetchAll(
            "SELECT transaction_type, COALESCE(SUM(amount), 0) as total
             FROM finances
             WHERE member_id = ?
               AND department_id IS NULL
               AND transaction_type <> 'Offering'
             GROUP BY transaction_type
             ORDER BY transaction_type ASC",
            [$memberId]
        );

        $income = (float)($incomeRow['total'] ?? 0);
        $expense = (float)($expenseRow['total'] ?? 0);

        return [
            'member_id' => $memberId,
            'income_total' => $income,
            'expense_total' => $expense,
            'net_total' => $income - $expense,
            'by_type' => array_map(function ($r) {
                return [
                    'transaction_type' => (string)($r['transaction_type'] ?? ''),
                    'total' => (float)($r['total'] ?? 0)
                ];
            }, $byType ?: [])
        ];
    }

    public function getMemberTransactionsWithMeta($memberId, $limit = 50) {
        $memberId = (int)$memberId;
        $limit = (int)$limit;
        if ($limit <= 0) $limit = 50;
        if ($limit > 200) $limit = 200;

        $sql = "SELECT
                    f.*,
                    CONCAT(COALESCE(m.first_name,''), ' ', COALESCE(m.last_name,'')) as member_name,
                    u.name as recorded_by_name
                FROM finances f
                LEFT JOIN members m ON m.id = f.member_id
                LEFT JOIN users u ON u.id = f.recorded_by
                WHERE f.member_id = ?
                  AND f.department_id IS NULL
                  AND f.transaction_type <> 'Offering'
                ORDER BY f.transaction_date DESC, f.id DESC
                LIMIT $limit";
        return $this->db->fetchAll($sql, [$memberId]);
    }
}
