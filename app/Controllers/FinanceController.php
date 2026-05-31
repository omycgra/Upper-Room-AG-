<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Finance.php';
require_once __DIR__ . '/../Models/Member.php';
require_once __DIR__ . '/../Models/Department.php';
require_once __DIR__ . '/../Helpers/FinancePaymentSmsService.php';
require_once __DIR__ . '/../Helpers/FinanceDepartmentHeadSmsService.php';

class FinanceController extends BaseController {
    public function index() {
        $this->ensureFinanceSchema();
        $financeModel = new Finance();
        $deptModel = new Department();

        $isDeptHead = Auth::isDepartmentHead();
        $isStaff = Auth::isStaff();
        $myDeptId = $isDeptHead ? (int)(Session::get('user_department_id') ?? 0) : 0;
        if ($isDeptHead && $myDeptId <= 0) {
            Session::flash('error', 'Department access is not configured for this account.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }

        $month = (int)date('m');
        $year = (int)date('Y');
        $staffAllowedTypes = $this->getStaffAllowedTypes();
        $staffTotals = null;
        $generalAllTime = null;
        $departmentAllTime = null;
        $combinedAllTime = null;
        $pendingChangeRequests = [];
        $pendingDepartmentExpenseRequests = [];
        $myChangeRequests = [];
        $activeChangeRequestMap = [];
        $approvedChangeRequestMap = [];

        if ($isStaff) {
            $cashierTypes = array_values(array_diff($staffAllowedTypes, ['Expense']));
            $staffTotals = $financeModel->getRecorderTotals((int)Session::get('user_id'), $month, $year, $cashierTypes);
            $monthlyIncome = $financeModel->getMonthlyIncome($month, $year, null, null);
            $monthlyExpenses = $financeModel->getMonthlyExpenses($month, $year, null, null);
            $monthlyBalance = $monthlyIncome - $monthlyExpenses;
            $health = $monthlyBalance >= 0 ? 'Good' : 'Needs Review';
            $departmentSavings = $financeModel->getDepartmentSavingsSummary($month, $year, null, null);
            $departmentHeadTotals = null;
            if (Auth::isFinanceHead()) {
                $recentTransactions = $financeModel->getRecentTransactionsWithMeta(50);
            } else {
                $recentTransactions = $financeModel->getRecentTransactionsByRecorderWithMeta((int)Session::get('user_id'), 50, $staffAllowedTypes);
            }
            $myChangeRequests = $financeModel->getChangeRequestsForRequester((int)Session::get('user_id'), 80);
            foreach ($myChangeRequests as $r) {
                $status = strtolower(trim((string)($r['status'] ?? '')));
                $fulfilledAt = $r['fulfilled_at'] ?? null;
                if ($fulfilledAt === null && in_array($status, ['pending', 'approved'], true)) {
                    $activeChangeRequestMap[(int)($r['finance_id'] ?? 0)] = $r;
                }
            }
            $approvedChangeRequestMap = $financeModel->getApprovedChangeRequestsForRequester((int)Session::get('user_id'));

            $generalAllTime = $financeModel->getGeneralTotals();
            $departmentAllTime = $financeModel->getDepartmentAggregateTotalsAllTime();
            $combinedAllTime = [
                'income_total' => (float)($generalAllTime['income_total'] ?? 0) + (float)($departmentAllTime['income_total'] ?? 0),
                'expense_total' => (float)($generalAllTime['expense_total'] ?? 0) + (float)($departmentAllTime['expense_total'] ?? 0),
                'balance' => (float)($generalAllTime['balance'] ?? 0) + (float)($departmentAllTime['balance'] ?? 0)
            ];
            if (Auth::isFinanceHead()) {
                $pendingChangeRequests = $financeModel->getPendingChangeRequests(80);
            }
        } else {
            $monthlyIncome = $financeModel->getMonthlyIncome($month, $year, $isDeptHead ? $myDeptId : null, null);
            $monthlyExpenses = $financeModel->getMonthlyExpenses($month, $year, $isDeptHead ? $myDeptId : null, null);
            $monthlyBalance = $monthlyIncome - $monthlyExpenses;
            $health = $monthlyBalance >= 0 ? 'Good' : 'Needs Review';
            $departmentSavings = $financeModel->getDepartmentSavingsSummary($month, $year, $isDeptHead ? $myDeptId : null, null);
            $departmentHeadTotals = $isDeptHead ? $financeModel->getDepartmentTotals($myDeptId, null) : null;
            $recentTransactions = $isDeptHead ? $financeModel->getRecentDepartmentTransactionsWithMeta($myDeptId, 50, null) : $financeModel->getRecentTransactionsWithMeta(50);
            if (!$isDeptHead) {
                $generalAllTime = $financeModel->getGeneralTotals();
                $departmentAllTime = $financeModel->getDepartmentAggregateTotalsAllTime();
                $combinedAllTime = [
                    'income_total' => (float)($generalAllTime['income_total'] ?? 0) + (float)($departmentAllTime['income_total'] ?? 0),
                    'expense_total' => (float)($generalAllTime['expense_total'] ?? 0) + (float)($departmentAllTime['expense_total'] ?? 0),
                    'balance' => (float)($generalAllTime['balance'] ?? 0) + (float)($departmentAllTime['balance'] ?? 0)
                ];
                $pendingChangeRequests = $financeModel->getPendingChangeRequests(80);
            }
        }

        $db = Database::getInstance();
        if (!$isDeptHead) {
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
                         LIMIT 80"
                    ) ?: [];
                }
            } catch (Throwable $e) {
                $pendingDepartmentExpenseRequests = [];
            }
        }
        $expFrom = $this->sanitizeDateParam($_GET['exp_from'] ?? null);
        $expTo = $this->sanitizeDateParam($_GET['exp_to'] ?? null);
        if ($expFrom !== null && $expTo === null) $expTo = $expFrom;
        if ($expTo !== null && $expFrom === null) $expFrom = $expTo;

        $recentExpenses = [];
        if (!$isDeptHead) {
            try {
                if ($expFrom !== null && $expTo !== null) {
                    $recentExpenses = $financeModel->getExpensesWithMetaByDateRange($expFrom, $expTo, 200);
                } else {
                    $recentExpenses = $financeModel->getRecentExpensesWithMeta(60);
                }
            } catch (Throwable $e) {
                $recentExpenses = [];
            }
        }
        $bank = [
            'bank_name' => $this->getSetting($db, 'finance_bank_name', ''),
            'account_name' => $this->getSetting($db, 'finance_account_name', ''),
            'account_number' => $this->getSetting($db, 'finance_account_number', ''),
            'branch' => $this->getSetting($db, 'finance_bank_branch', ''),
            'currency' => $this->getSetting($db, 'finance_currency', 'GHS')
        ];
        $departmentBank = null;
        if ($isDeptHead) {
            $departmentBank = $deptModel->getDepartmentBankDetails($myDeptId);
            if ($departmentBank) {
                $bank['bank_name'] = (string)($departmentBank['bank_name'] ?? '');
                $bank['account_name'] = (string)($departmentBank['account_name'] ?? '');
                $bank['account_number'] = (string)($departmentBank['account_number'] ?? '');
                $bank['branch'] = (string)($departmentBank['bank_branch'] ?? '');
            }
        }

        $membersForSelect = [];
        if (!$isDeptHead && !$isStaff) {
            $membersForSelect = $db->fetchAll("SELECT id, first_name, last_name FROM members ORDER BY first_name ASC, last_name ASC");
        }

        View::render('finance.index', [
            'title' => 'Financial Management',
            'overall_balance' => $isStaff ? (float)($staffTotals['overall_total'] ?? 0) : ($isDeptHead ? $monthlyBalance : $financeModel->getBalance()),
            'month' => $month,
            'year' => $year,
            'month_label' => date('F Y'),
            'health' => $health,
            'monthly_income' => $monthlyIncome,
            'monthly_expenses' => $monthlyExpenses,
            'monthly_balance' => $monthlyBalance,
            'department_head_totals' => $departmentHeadTotals,
            'department_savings' => $departmentSavings,
            'general_all_time' => $generalAllTime,
            'department_all_time' => $departmentAllTime,
            'combined_all_time' => $combinedAllTime,
            'pending_change_requests' => $pendingChangeRequests,
            'pending_department_expense_requests' => $pendingDepartmentExpenseRequests,
            'my_change_requests' => $myChangeRequests,
            'active_change_request_map' => $activeChangeRequestMap,
            'approved_change_request_map' => $approvedChangeRequestMap,
            'bank' => $bank,
            'department_bank' => $departmentBank,
            'recent_transactions' => $recentTransactions,
            'recent_expenses' => $recentExpenses,
            'exp_from' => $expFrom,
            'exp_to' => $expTo,
            'isDeptHead' => $isDeptHead,
            'isStaff' => $isStaff,
            'myDeptId' => $myDeptId,
            'membersForSelect' => $membersForSelect,
            'staff_totals' => $staffTotals,
            'staff_allowed_types' => $staffAllowedTypes,
            'receipt_data' => Session::flash('receipt_data')
        ]);
    }

    public function transactions() {
        $this->ensureFinanceSchema();
        $financeModel = new Finance();

        $isDeptHead = Auth::isDepartmentHead();
        $isStaff = Auth::isStaff();
        $myDeptId = $isDeptHead ? (int)(Session::get('user_department_id') ?? 0) : 0;
        if ($isDeptHead && $myDeptId <= 0) {
            Session::flash('error', 'Department access is not configured for this account.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }

        $txFrom = $this->sanitizeDateParam($_GET['tx_from'] ?? null);
        $txTo = $this->sanitizeDateParam($_GET['tx_to'] ?? null);
        if ($txFrom !== null && $txTo === null) $txTo = $txFrom;
        if ($txTo !== null && $txFrom === null) $txFrom = $txTo;

        $staffAllowedTypes = $this->getStaffAllowedTypes();
        $recentTransactions = [];
        $activeChangeRequestMap = [];
        $approvedChangeRequestMap = [];

        if ($isStaff) {
            if (Auth::isFinanceHead()) {
                if ($txFrom !== null && $txTo !== null) {
                    $recentTransactions = $financeModel->getTransactionsWithMetaByDateRange($txFrom, $txTo, 200);
                } else {
                    $recentTransactions = $financeModel->getRecentTransactionsWithMeta(200);
                }
            } else {
                if ($txFrom !== null && $txTo !== null) {
                    $recentTransactions = $financeModel->getTransactionsWithMetaByDateRange($txFrom, $txTo, 200, null, (int)Session::get('user_id'), $staffAllowedTypes);
                } else {
                    $recentTransactions = $financeModel->getRecentTransactionsByRecorderWithMeta((int)Session::get('user_id'), 200, $staffAllowedTypes);
                }
            }

            $myChangeRequests = $financeModel->getChangeRequestsForRequester((int)Session::get('user_id'), 200);
            foreach ($myChangeRequests as $r) {
                $status = strtolower(trim((string)($r['status'] ?? '')));
                $fulfilledAt = $r['fulfilled_at'] ?? null;
                if ($fulfilledAt === null && in_array($status, ['pending', 'approved'], true)) {
                    $activeChangeRequestMap[(int)($r['finance_id'] ?? 0)] = $r;
                }
            }
            $approvedChangeRequestMap = $financeModel->getApprovedChangeRequestsForRequester((int)Session::get('user_id'));
        } else {
            if ($isDeptHead) {
                if ($txFrom !== null && $txTo !== null) {
                    $recentTransactions = $financeModel->getTransactionsWithMetaByDateRange($txFrom, $txTo, 200, $myDeptId);
                } else {
                    $recentTransactions = $financeModel->getRecentDepartmentTransactionsWithMeta($myDeptId, 200, null);
                }
            } else {
                if ($txFrom !== null && $txTo !== null) {
                    $recentTransactions = $financeModel->getTransactionsWithMetaByDateRange($txFrom, $txTo, 200);
                } else {
                    $recentTransactions = $financeModel->getRecentTransactionsWithMeta(200);
                }
            }
        }

        $db = Database::getInstance();
        $bank = [
            'currency' => $this->getSetting($db, 'finance_currency', 'GHS')
        ];

        View::render('finance.transactions', [
            'title' => 'Transactions',
            'bank' => $bank,
            'recent_transactions' => $recentTransactions,
            'tx_from' => $txFrom,
            'tx_to' => $txTo,
            'isDeptHead' => $isDeptHead,
            'isStaff' => $isStaff,
            'active_change_request_map' => $activeChangeRequestMap,
            'approved_change_request_map' => $approvedChangeRequestMap,
            'receipt_data' => Session::flash('receipt_data'),
        ]);
    }

    public function departmentSavings() {
        $this->ensureFinanceSchema();
        $financeModel = new Finance();

        $isDeptHead = Auth::isDepartmentHead();
        $isStaff = Auth::isStaff();
        $myDeptId = $isDeptHead ? (int)(Session::get('user_department_id') ?? 0) : 0;
        if ($isDeptHead && $myDeptId <= 0) {
            Session::flash('error', 'Department access is not configured for this account.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }

        $scope = strtolower(trim((string)($_GET['scope'] ?? 'month')));
        if (!in_array($scope, ['month', 'all'], true)) $scope = 'month';

        $dateParam = $this->sanitizeDateParam($_GET['date'] ?? null);

        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));
        if ($month < 1 || $month > 12) $month = (int)date('m');
        if ($year < 2000 || $year > ((int)date('Y') + 1)) $year = (int)date('Y');

        if ($dateParam !== null) {
            $month = (int)date('m', strtotime($dateParam));
            $year = (int)date('Y', strtotime($dateParam));
        }

        if ($scope === 'all') {
            $departmentSavings = $financeModel->getDepartmentSavingsSummaryAllTime($isDeptHead ? $myDeptId : null, null);
        } else {
            $departmentSavings = $financeModel->getDepartmentSavingsSummary($month, $year, $isDeptHead ? $myDeptId : null, null);
        }

        $db = Database::getInstance();
        $bank = [
            'currency' => $this->getSetting($db, 'finance_currency', 'GHS')
        ];

        View::render('finance.department_savings', [
            'title' => 'Departmental Savings',
            'bank' => $bank,
            'month' => $month,
            'year' => $year,
            'month_label' => date('F Y', strtotime(sprintf('%04d-%02d-01', $year, $month))),
            'department_savings' => $departmentSavings,
            'scope' => $scope,
            'date' => $dateParam,
            'isDeptHead' => $isDeptHead,
            'isStaff' => $isStaff,
            'myDeptId' => $myDeptId,
        ]);
    }

    public function add() {
        $this->ensureFinanceSchema();
        if (Auth::isAdmin()) {
            Session::flash('error', 'Admins can view transactions but cannot add them.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/transactions");
            exit;
        }
        $isDeptHead = Auth::isDepartmentHead();
        $isStaff = Auth::isStaff();
        $myDeptId = $isDeptHead ? (int)(Session::get('user_department_id') ?? 0) : 0;
        if ($isDeptHead && $myDeptId <= 0) {
            Session::flash('error', 'Department access is not configured for this account.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $memberModel = new Member();
        $deptModel = new Department();
        $allowedTypes = $isStaff ? $this->getStaffAllowedTypes() : [];
        $defaultType = trim((string)($_GET['type'] ?? 'Offering'));
        if ($isStaff && !in_array($defaultType, $allowedTypes, true)) {
            $defaultType = 'Offering';
        }
        View::render('finance.add', [
            'title' => 'Record Transaction',
            'members' => $isDeptHead ? [] : $memberModel->all('first_name ASC'),
            'departments' => $isDeptHead ? $deptModel->where('id', $myDeptId) : $deptModel->all('name ASC'),
            'isDeptHead' => $isDeptHead,
            'isStaff' => $isStaff,
            'myDeptId' => $myDeptId,
            'staffAllowedTypes' => $allowedTypes,
            'defaultType' => $defaultType
        ]);
    }

    public function store() {
        $this->ensureFinanceSchema();
        $financeModel = new Finance();
        if (Auth::isAdmin()) {
            Session::flash('error', 'Admins can view transactions but cannot add them.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/transactions");
            exit;
        }

        $isDeptHead = Auth::isDepartmentHead();
        $isStaff = Auth::isStaff();
        $myDeptId = $isDeptHead ? (int)(Session::get('user_department_id') ?? 0) : 0;
        if ($isDeptHead && $myDeptId <= 0) {
            Session::flash('error', 'Department access is not configured for this account.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }

        $db = Database::getInstance();
        $txNo = $this->generateTransactionNumber($db);

        $memberId = !empty($_POST['member_id']) ? (int)$_POST['member_id'] : null;
        $departmentId = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        $transactionType = trim((string)($_POST['transaction_type'] ?? 'Offering'));
        $staffAllowedTypes = $this->getStaffAllowedTypes();
        $staffMemberTypes = ['Tithe', 'Welfare'];

        if ($isStaff && !in_array($transactionType, $staffAllowedTypes, true)) {
            Session::flash('error', 'Staff can record only general offering, tithe, department offering, welfare, and church expenses.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/finance/add");
            exit;
        }

        if (!$isDeptHead && in_array($transactionType, ['Tithe', 'Welfare'], true) && !$memberId) {
            Session::flash('error', $transactionType . ' must be attached to a member (so we can send the payment SMS).');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/finance/add?type=" . urlencode($transactionType));
            exit;
        }
        
        if (!$isDeptHead && in_array($transactionType, ['Tithe', 'Welfare'], true) && $memberId) {
            $row = $db->fetch("SELECT phone FROM members WHERE id = ? LIMIT 1", [$memberId]);
            $phone = trim((string)($row['phone'] ?? ''));
            $hasAnyPhone = $phone !== '' && (bool)preg_match('/(\+233|233|0)\d{9}|\b\d{9}\b/', $phone);
            if (!$hasAnyPhone) {
                Session::flash('error', 'This member has no valid phone number. Add a correct phone number first so the payment SMS can be sent.');
                $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                header("Location: $base/finance/add?type=" . urlencode($transactionType));
                exit;
            }
        }

        if ($transactionType === 'Expense') {
            $memberId = null;
            $departmentId = null;
        } elseif ($transactionType === 'Offering') {
            $memberId = null;
            $departmentId = null;
        } elseif (in_array($transactionType, ['Sunday School', 'Annual Harvest', 'Mini Harvest', 'Project Offering', 'Donation', 'Pledge Fulfillment', 'Seed', 'Others'], true)) {
            $memberId = null;
            $departmentId = null;
        } elseif ($departmentId) {
            $memberId = null;
        } elseif ($memberId) {
            $departmentId = null;
        }
        
        $data = [
            'transaction_number' => $txNo,
            'member_id' => $memberId,
            'department_id' => $departmentId,
            'transaction_type' => $transactionType,
            'offering_subtype' => null,
            'amount' => $_POST['amount'],
            'payment_method' => $_POST['payment_method'],
            'transaction_date' => $_POST['transaction_date'],
            'description' => $_POST['description'],
            'reference_no' => $_POST['reference_no'],
            'recorded_by' => Session::get('user_id')
        ];

        if ($transactionType === 'Offering') {
            $sub = trim((string)($_POST['offering_subtype'] ?? ''));
            $allowed = ['Main Offering', 'Thanksgiving'];
            if ($sub === '' || !in_array($sub, $allowed, true)) {
                Session::flash('error', 'Select the offering type (Main Offering or Thanksgiving).');
                $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                header("Location: $base/finance/add?type=Offering");
                exit;
            }
            $data['offering_subtype'] = $sub;
        }

        $paymentMethod = trim((string)($_POST['payment_method'] ?? 'Cash'));
        if ($paymentMethod === 'Mobile Money') {
            $paymentMethod = 'MoMo';
        }

        if ($isDeptHead) {
            $data['member_id'] = null;
            $data['department_id'] = $myDeptId;
            $data['payment_method'] = $paymentMethod !== '' ? $paymentMethod : 'Cash';
        } elseif ($isStaff) {
            $data['payment_method'] = $paymentMethod !== '' ? $paymentMethod : 'Cash';
            if (in_array($transactionType, $staffMemberTypes, true)) {
                if (!$memberId) {
                    Session::flash('error', $transactionType . ' must be attached to a member.');
                    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                    header("Location: $base/finance/add?type=" . urlencode($transactionType));
                    exit;
                }
                $data['member_id'] = $memberId;
                $data['department_id'] = null;
            } elseif ($transactionType === 'Departmental Savings') {
                if (!$departmentId) {
                    Session::flash('error', 'Select a department for department offering.');
                    $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                    header("Location: $base/finance/add?type=Departmental%20Savings");
                    exit;
                }
                $data['member_id'] = null;
                $data['department_id'] = $departmentId;
            } else {
                $data['member_id'] = null;
                $data['department_id'] = null;
            }
        } else {
            $data['payment_method'] = $paymentMethod !== '' ? $paymentMethod : 'Cash';
        }
        
        $financeId = $financeModel->create($data);
        
        if ($financeId) {
            AuditLog::log("Recorded " . $data['transaction_type'] . " of " . $data['amount'], "finances", $financeId);
            $successMessage = 'Transaction recorded successfully';
            $smsResult = null;
            $txTypeLower = strtolower(trim((string)($data['transaction_type'] ?? '')));
            $shouldSendSms = !empty($_POST['send_sms']);
            
            if (in_array($txTypeLower, ['tithe', 'welfare', 'offering'], true)) {
                if ($shouldSendSms) {
                    $smsResult = FinancePaymentSmsService::sendForTransaction((int)$financeId);
                    if (($smsResult['status'] ?? '') === 'sent') {
                        $successMessage = (string)($smsResult['message'] ?? $successMessage);
                    } elseif (($smsResult['status'] ?? '') === 'duplicate') {
                        $successMessage .= '. ' . trim((string)($smsResult['message'] ?? 'SMS was already sent for this transaction.'));
                    } elseif (($smsResult['status'] ?? '') === 'error' || ($smsResult['status'] ?? '') === 'skipped') {
                        $successMessage = 'Transaction recorded, but SMS was not sent. ' . trim((string)($smsResult['message'] ?? ''));
                    }
                } else {
                    $successMessage = 'Transaction recorded successfully (SMS skipped).';
                }
            }

            $deptSmsResult = null;
            if ((int)($data['department_id'] ?? 0) > 0 && !in_array($txTypeLower, ['tithe', 'welfare'], true)) {
                $deptSmsResult = FinanceDepartmentHeadSmsService::sendForTransaction((int)$financeId);
            }
            if ($deptSmsResult && ($deptSmsResult['status'] ?? '') === 'sent') {
                $successMessage .= ' Department heads notified.';
            } elseif ($deptSmsResult && (($deptSmsResult['status'] ?? '') === 'error')) {
                $successMessage .= ' ' . trim((string)($deptSmsResult['message'] ?? ''));
            }

            if ($smsResult && (($smsResult['status'] ?? '') === 'error' || ($smsResult['status'] ?? '') === 'skipped')) {
                Session::flash('error', $successMessage);
            } else {
                Session::flash('success', $successMessage);
            }
            if ($isStaff) {
                $receiptData = $financeModel->getTransactionWithMeta($financeId);
                if ($receiptData && (int)($receiptData['member_id'] ?? 0) > 0) {
                    Session::flash('receipt_data', $receiptData);
                }
            }
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/transactions");
        } else {
            Session::flash('error', 'Failed to record transaction');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/finance/add");
        }
        exit;
    }

    public function memberSummary() {
        $this->ensureFinanceSchema();
        $financeModel = new Finance();

        $memberId = (int)($_GET['member_id'] ?? 0);
        if ((Session::get('user_role') === 'dept_head')) {
            $myDeptId = (int)(Session::get('user_department_id') ?? 0);
            if ($myDeptId > 0) {
                $db = Database::getInstance();
                $row = $db->fetch("SELECT department_id FROM members WHERE id = ?", [$memberId]);
                if ((int)($row['department_id'] ?? 0) !== $myDeptId) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'member_id' => 0,
                        'income_total' => 0.0,
                        'expense_total' => 0.0,
                        'net_total' => 0.0,
                        'by_type' => []
                    ]);
                    exit;
                }
            }
        }
        $summary = $financeModel->getMemberTransactionSummary($memberId);

        header('Content-Type: application/json');
        echo json_encode($summary);
        exit;
    }

    public function memberTransactions() {
        $this->ensureFinanceSchema();
        $financeModel = new Finance();

        $memberId = (int)($_GET['member_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 50);

        if ((Session::get('user_role') === 'dept_head')) {
            $myDeptId = (int)(Session::get('user_department_id') ?? 0);
            if ($myDeptId > 0) {
                $db = Database::getInstance();
                $row = $db->fetch("SELECT department_id FROM members WHERE id = ?", [$memberId]);
                if ((int)($row['department_id'] ?? 0) !== $myDeptId) {
                    header('Content-Type: application/json');
                    echo json_encode(['transactions' => []]);
                    exit;
                }
            }
        }

        $tx = $financeModel->getMemberTransactionsWithMeta($memberId, $limit);
        header('Content-Type: application/json');
        echo json_encode(['transactions' => $tx]);
        exit;
    }

    public function updateTransaction() {
        $this->ensureFinanceSchema();
        $financeModel = new Finance();

        $isStaff = Auth::isStaff();
        $isDeptHead = Auth::isDepartmentHead();
        $myDeptId = $isDeptHead ? (int)(Session::get('user_department_id') ?? 0) : 0;

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            Session::flash('error', 'Transaction ID missing.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/finance");
            exit;
        }

        $tx = $financeModel->find($id);
        if (!$tx) {
            Session::flash('error', 'Transaction not found.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/finance");
            exit;
        }

        if ($isDeptHead) {
            if ($myDeptId <= 0 || (int)($tx['department_id'] ?? 0) !== $myDeptId) {
                Session::flash('error', 'Unauthorized access.');
                $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                header("Location: $base/finance");
                exit;
            }
        } elseif ($isStaff) {
            $approved = $financeModel->getApprovedChangeRequestForTransaction($id, (int)Session::get('user_id'));
            if (!$approved) {
                Session::flash('error', 'This transaction cannot be edited until an approved change request exists.');
                $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                header("Location: $base/finance");
                exit;
            }
        } else {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }

        $amount = (float)($_POST['amount'] ?? 0);
        $date = $_POST['transaction_date'] ?? null;
        $desc = $_POST['description'] ?? null;
        $ref = $_POST['reference_no'] ?? null;

        $financeModel->update($id, [
            'amount' => $amount,
            'transaction_date' => $date,
            'description' => $desc,
            'reference_no' => $ref,
            'recorded_by' => Session::get('user_id')
        ]);

        if ($isStaff) {
            $approved = $financeModel->getApprovedChangeRequestForTransaction($id, (int)Session::get('user_id'));
            if ($approved) {
                $nextCount = (int)($approved['edit_count'] ?? 0) + 1;
                $update = ['edit_count' => $nextCount];
                if ($nextCount >= 2) {
                    $update['fulfilled_at'] = date('Y-m-d H:i:s');
                }
                $financeModel->updateChangeRequest((int)$approved['id'], $update);
                AuditLog::log('Updated transaction under approved change request', 'finance_change_requests', (int)$approved['id'], null, [
                    'edit_count' => $nextCount
                ]);
            }
        }

        Session::flash('success', 'Transaction updated successfully.');
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/finance");
        exit;
    }

    public function requestChange() {
        $this->ensureFinanceSchema();
        if (!Auth::isStaff()) {
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $financeModel = new Finance();
        $financeId = (int)($_POST['finance_id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));
        if ($financeId <= 0 || $reason === '') {
            Session::flash('error', 'Select a transaction and enter a reason for the change request.');
            header('Location: ' . BASE_URL . '/finance');
            exit;
        }

        $tx = $financeModel->find($financeId);
        if (!$tx) {
            Session::flash('error', 'Transaction not found.');
            header('Location: ' . BASE_URL . '/finance');
            exit;
        }

        $userId = (int)Session::get('user_id');
        if ((int)($tx['recorded_by'] ?? 0) !== $userId) {
            Session::flash('error', 'You can request changes only for transactions you recorded.');
            header('Location: ' . BASE_URL . '/finance');
            exit;
        }

        $existing = $financeModel->getActiveChangeRequestForTransaction($financeId);
        if ($existing && (int)($existing['requested_by'] ?? 0) === $userId) {
            Session::flash('error', 'A change request already exists for this transaction.');
            header('Location: ' . BASE_URL . '/finance');
            exit;
        }

        $requestId = $financeModel->createChangeRequest([
            'finance_id' => $financeId,
            'requested_by' => $userId,
            'reason' => $reason,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        AuditLog::log('Submitted finance change request', 'finance_change_requests', (int)$requestId);
        Session::flash('success', 'Change request sent to the Head of Finance for approval.');
        header('Location: ' . BASE_URL . '/finance');
        exit;
    }

    public function approveChangeRequest() {
        $this->ensureFinanceSchema();
        if (!Auth::isFinanceHead()) {
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        try {
            $financeModel = new Finance();
            $requestId = (int)($_POST['request_id'] ?? 0);
            if ($requestId <= 0) {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Change request ID missing.'], 400);
                }
                Session::flash('error', 'Change request ID missing.');
                header('Location: ' . BASE_URL . '/finance');
                exit;
            }

            $db = Database::getInstance();
            $request = $db->fetch("SELECT * FROM finance_change_requests WHERE id = ? LIMIT 1", [$requestId]);
            if (!$request) {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Change request not found.'], 404);
                }
                Session::flash('error', 'Change request not found.');
                header('Location: ' . BASE_URL . '/finance');
                exit;
            }

            if (strtolower((string)($request['status'] ?? '')) !== 'pending' || ($request['fulfilled_at'] ?? null) !== null) {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'This change request is no longer pending.'], 400);
                }
                Session::flash('error', 'This change request is no longer pending.');
                header('Location: ' . BASE_URL . '/finance');
                exit;
            }

            $financeModel->updateChangeRequest($requestId, [
                'status' => 'approved',
                'approved_by' => (int)Session::get('user_id'),
                'approved_at' => date('Y-m-d H:i:s')
            ]);

            AuditLog::log('Approved finance change request', 'finance_change_requests', $requestId);
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => true, 'message' => 'Change request approved.']);
            }
            Session::flash('success', 'Change request approved. The finance staff can now edit the transaction.');
            header('Location: ' . BASE_URL . '/finance');
            exit;
        } catch (Throwable $e) {
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => false, 'message' => 'Error approving change request.'], 500);
            }
            Session::flash('error', 'Error approving change request: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/finance');
            exit;
        }
    }

    public function rejectChangeRequest() {
        $this->ensureFinanceSchema();
        if (!Auth::isFinanceHead()) {
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        try {
            $financeModel = new Finance();
            $requestId = (int)($_POST['request_id'] ?? 0);
            if ($requestId <= 0) {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Change request ID missing.'], 400);
                }
                Session::flash('error', 'Change request ID missing.');
                header('Location: ' . BASE_URL . '/finance');
                exit;
            }

            $db = Database::getInstance();
            $request = $db->fetch("SELECT * FROM finance_change_requests WHERE id = ? LIMIT 1", [$requestId]);
            if (!$request) {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Change request not found.'], 404);
                }
                Session::flash('error', 'Change request not found.');
                header('Location: ' . BASE_URL . '/finance');
                exit;
            }

            if (strtolower((string)($request['status'] ?? '')) !== 'pending' || ($request['fulfilled_at'] ?? null) !== null) {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'This change request is no longer pending.'], 400);
                }
                Session::flash('error', 'This change request is no longer pending.');
                header('Location: ' . BASE_URL . '/finance');
                exit;
            }

            $financeModel->updateChangeRequest($requestId, [
                'status' => 'rejected',
                'rejected_by' => (int)Session::get('user_id'),
                'rejected_at' => date('Y-m-d H:i:s')
            ]);

            AuditLog::log('Rejected finance change request', 'finance_change_requests', $requestId);
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => true, 'message' => 'Change request rejected.']);
            }
            Session::flash('success', 'Change request rejected.');
            header('Location: ' . BASE_URL . '/finance');
            exit;
        } catch (Throwable $e) {
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => false, 'message' => 'Error rejecting change request.'], 500);
            }
            Session::flash('error', 'Error rejecting change request: ' . $e->getMessage());
            header('Location: ' . BASE_URL . '/finance');
            exit;
        }
    }

    public function pendingApprovals() {
        $this->ensureFinanceSchema();
        if (!Auth::isFinanceHead()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $financeModel = new Finance();
        $items = [];
        $latestKey = '';
        try {
            $pendingChanges = $financeModel->getPendingChangeRequests(40) ?: [];
            foreach ($pendingChanges as $r) {
                $createdAt = (string)($r['created_at'] ?? '');
                $items[] = [
                    'kind' => 'change_request',
                    'id' => (int)($r['id'] ?? 0),
                    'created_at' => $createdAt,
                    'requested_by_name' => (string)($r['requested_by_name'] ?? ''),
                    'transaction_type' => (string)($r['transaction_type'] ?? ''),
                    'transaction_number' => (string)($r['transaction_number'] ?? ''),
                    'amount' => (float)($r['current_amount'] ?? 0),
                    'reason' => (string)($r['reason'] ?? '')
                ];
                if ($latestKey === '' && $createdAt !== '') $latestKey = $createdAt . '_cr_' . (int)($r['id'] ?? 0);
            }
        } catch (Throwable $e) {
        }

        $db = Database::getInstance();
        try {
            if ($db->tableExists('department_expense_requests')) {
                $pendingDept = $db->fetchAll(
                    "SELECT r.*, d.name as department_name, requester.name as requested_by_name
                     FROM department_expense_requests r
                     INNER JOIN departments d ON d.id = r.department_id
                     INNER JOIN users requester ON requester.id = r.requested_by
                     WHERE LOWER(COALESCE(r.status, 'pending')) = 'pending'
                     ORDER BY r.created_at DESC, r.id DESC
                     LIMIT 40"
                ) ?: [];
                foreach ($pendingDept as $r) {
                    $createdAt = (string)($r['created_at'] ?? '');
                    $items[] = [
                        'kind' => 'dept_expense_request',
                        'id' => (int)($r['id'] ?? 0),
                        'created_at' => $createdAt,
                        'requested_by_name' => (string)($r['requested_by_name'] ?? ''),
                        'department_name' => (string)($r['department_name'] ?? ''),
                        'amount' => (float)($r['amount'] ?? 0),
                        'purpose' => (string)($r['purpose'] ?? '')
                    ];
                    if ($latestKey === '' && $createdAt !== '') $latestKey = $createdAt . '_dr_' . (int)($r['id'] ?? 0);
                }
            }
        } catch (Throwable $e) {
        }

        usort($items, function ($a, $b) {
            $ta = strtotime((string)($a['created_at'] ?? '')) ?: 0;
            $tb = strtotime((string)($b['created_at'] ?? '')) ?: 0;
            if ($ta === $tb) return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
            return $tb <=> $ta;
        });

        if ($latestKey === '' && !empty($items)) {
            $top = $items[0];
            $latestKey = (string)($top['created_at'] ?? '') . '_' . (string)($top['kind'] ?? '') . '_' . (int)($top['id'] ?? 0);
        }

        $this->jsonResponse([
            'success' => true,
            'total_pending' => count($items),
            'latest_key' => $latestKey,
            'items' => array_slice($items, 0, 10)
        ]);
    }

    public function myRequestUpdates() {
        $this->ensureFinanceSchema();
        if (!Auth::isStaff() && !Auth::isDepartmentHead()) {
            $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }

        $db = Database::getInstance();
        $userId = (int)Session::get('user_id');
        $items = [];

        if (Auth::isStaff() && $userId > 0) {
            try {
                $rows = $db->fetchAll(
                    "SELECT r.*,
                            f.transaction_number,
                            f.transaction_type,
                            f.amount as current_amount,
                            approver.name as approved_by_name,
                            rejector.name as rejected_by_name
                     FROM finance_change_requests r
                     INNER JOIN finances f ON f.id = r.finance_id
                     LEFT JOIN users approver ON approver.id = r.approved_by
                     LEFT JOIN users rejector ON rejector.id = r.rejected_by
                     WHERE r.requested_by = ?
                       AND r.status IN ('approved', 'rejected')
                     ORDER BY COALESCE(r.approved_at, r.rejected_at, r.created_at) DESC, r.id DESC
                     LIMIT 25",
                    [$userId]
                ) ?: [];

                foreach ($rows as $r) {
                    $status = strtolower(trim((string)($r['status'] ?? '')));
                    $decidedAt = $r['approved_at'] ?? ($r['rejected_at'] ?? ($r['created_at'] ?? null));
                    $editCount = (int)($r['edit_count'] ?? 0);
                    $items[] = [
                        'kind' => 'change_request',
                        'id' => (int)($r['id'] ?? 0),
                        'status' => $status,
                        'decided_at' => (string)($decidedAt ?? ''),
                        'finance_id' => (int)($r['finance_id'] ?? 0),
                        'transaction_type' => (string)($r['transaction_type'] ?? ''),
                        'transaction_number' => (string)($r['transaction_number'] ?? ''),
                        'amount' => (float)($r['current_amount'] ?? 0),
                        'reason' => (string)($r['reason'] ?? ''),
                        'approved_by_name' => (string)($r['approved_by_name'] ?? ''),
                        'rejected_by_name' => (string)($r['rejected_by_name'] ?? ''),
                        'remaining_edits' => max(0, 2 - $editCount),
                        'edit_count' => $editCount
                    ];
                }
            } catch (Throwable $e) {
            }
        }

        if (Auth::isDepartmentHead() && $userId > 0) {
            try {
                if ($db->tableExists('department_expense_requests')) {
                    $rows = $db->fetchAll(
                        "SELECT r.*,
                                d.name as department_name,
                                approver.name as approved_by_name,
                                rejector.name as rejected_by_name
                         FROM department_expense_requests r
                         INNER JOIN departments d ON d.id = r.department_id
                         LEFT JOIN users approver ON approver.id = r.approved_by
                         LEFT JOIN users rejector ON rejector.id = r.rejected_by
                         WHERE r.requested_by = ?
                           AND LOWER(COALESCE(r.status, '')) IN ('approved', 'rejected')
                         ORDER BY COALESCE(r.approved_at, r.rejected_at, r.created_at) DESC, r.id DESC
                         LIMIT 25",
                        [$userId]
                    ) ?: [];

                    foreach ($rows as $r) {
                        $status = strtolower(trim((string)($r['status'] ?? '')));
                        $decidedAt = $r['approved_at'] ?? ($r['rejected_at'] ?? ($r['created_at'] ?? null));
                        $items[] = [
                            'kind' => 'dept_expense_request',
                            'id' => (int)($r['id'] ?? 0),
                            'status' => $status,
                            'decided_at' => (string)($decidedAt ?? ''),
                            'department_name' => (string)($r['department_name'] ?? ''),
                            'amount' => (float)($r['amount'] ?? 0),
                            'purpose' => (string)($r['purpose'] ?? ''),
                            'approved_by_name' => (string)($r['approved_by_name'] ?? ''),
                            'rejected_by_name' => (string)($r['rejected_by_name'] ?? '')
                        ];
                    }
                }
            } catch (Throwable $e) {
            }
        }

        usort($items, function ($a, $b) {
            $ta = strtotime((string)($a['decided_at'] ?? '')) ?: 0;
            $tb = strtotime((string)($b['decided_at'] ?? '')) ?: 0;
            if ($ta === $tb) return ((int)($b['id'] ?? 0)) <=> ((int)($a['id'] ?? 0));
            return $tb <=> $ta;
        });

        $this->jsonResponse([
            'success' => true,
            'items' => array_slice($items, 0, 10)
        ]);
    }

    public function requestDepartmentExpense() {
        $this->ensureFinanceSchema();
        if (!Auth::isDepartmentHead()) {
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $myDeptId = (int)(Session::get('user_department_id') ?? 0);
        if ($myDeptId <= 0) {
            Session::flash('error', 'Department access is not configured for this account.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $amount = (float)($_POST['amount'] ?? 0);
        $purpose = trim((string)($_POST['purpose'] ?? ''));
        if ($amount <= 0 || $purpose === '') {
            Session::flash('error', 'Enter a valid amount and purpose for the expense request.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $db = Database::getInstance();
        try {
            $userId = (int)Session::get('user_id');
            if ($db->isPgsql()) {
                $row = $db->fetch(
                    "INSERT INTO department_expense_requests (department_id, requested_by, amount, purpose, status, created_at)
                     VALUES (?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP)
                     RETURNING id",
                    [$myDeptId, $userId, $amount, $purpose]
                );
                $requestId = (int)($row['id'] ?? 0);
            } else {
                $db->query(
                    "INSERT INTO department_expense_requests (department_id, requested_by, amount, purpose, status, created_at)
                     VALUES (?, ?, ?, ?, 'pending', NOW())",
                    [$myDeptId, $userId, $amount, $purpose]
                );
                $requestId = (int)$db->getConnection()->lastInsertId();
            }

            AuditLog::log('Submitted department expense request', 'department_expense_requests', $requestId, null, [
                'department_id' => $myDeptId,
                'amount' => $amount
            ]);
            Session::flash('success', 'Expense request submitted to the finance department for approval.');

            $cacheKey = 'dashboard_cache_dept_head_' . $userId;
            Session::remove($cacheKey);
        } catch (Throwable $e) {
            Session::flash('error', 'Error submitting expense request: ' . $e->getMessage());
        }

        header('Location: ' . BASE_URL . '/dashboard');
        exit;
    }

    public function approveDepartmentExpenseRequest() {
        $this->ensureFinanceSchema();
        $isFinanceHead = Auth::isFinanceHead();
        $isPastor = Auth::isPastor();
        if (!$isFinanceHead && !$isPastor) {
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        if ($requestId <= 0) {
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => false, 'message' => 'Expense request ID missing.'], 400);
            }
            Session::flash('error', 'Expense request ID missing.');
            header('Location: ' . BASE_URL . '/finance');
            exit;
        }

        $db = Database::getInstance();
        try {
            $request = $db->fetch(
                "SELECT r.*, d.name as department_name, u.name, u.phone
                 FROM department_expense_requests r
                 INNER JOIN departments d ON d.id = r.department_id
                 LEFT JOIN users u ON u.id = r.requested_by
                 WHERE r.id = ?
                 LIMIT 1",
                [$requestId]
            );
            if (!$request) {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Expense request not found.'], 404);
                }
                Session::flash('error', 'Expense request not found.');
                header('Location: ' . BASE_URL . '/finance');
                exit;
            }

            if (strtolower((string)($request['status'] ?? '')) !== 'pending') {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'This expense request is no longer pending.'], 400);
                }
                Session::flash('error', 'This expense request is no longer pending.');
                header('Location: ' . BASE_URL . '/finance');
                exit;
            }

            $now = date('Y-m-d H:i:s');
            $actorId = (int)Session::get('user_id');
            $actorName = trim((string)Session::get('user_name', ''));
            $actorName = $actorName !== '' ? $actorName : ($isPastor ? 'Pastor' : 'Finance Head');

            $financeApprovedBy = (int)($request['approved_by'] ?? 0);
            $pastorApprovedBy = (int)($request['pastor_approved_by'] ?? 0);

            if ($isFinanceHead && $financeApprovedBy > 0) {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'This request was already approved by the finance head.'], 400);
                }
                Session::flash('error', 'This request was already approved by the finance head.');
                header('Location: ' . BASE_URL . '/finance');
                exit;
            }
            if ($isPastor && $pastorApprovedBy > 0) {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'This request was already approved by the pastor.'], 400);
                }
                Session::flash('error', 'This request was already approved by the pastor.');
                header('Location: ' . BASE_URL . '/pastor');
                exit;
            }

            $amount = (float)($request['amount'] ?? 0);
            if ($amount <= 0) {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Invalid request amount.'], 400);
                }
                Session::flash('error', 'Invalid request amount.');
                header('Location: ' . BASE_URL . '/finance');
                exit;
            }

            if ($isFinanceHead) {
                $db->query(
                    "UPDATE department_expense_requests
                     SET approved_by = ?,
                         approved_at = ?
                     WHERE id = ?",
                    [$actorId, $now, $requestId]
                );
            } else {
                $db->query(
                    "UPDATE department_expense_requests
                     SET pastor_approved_by = ?,
                         pastor_approved_at = ?
                     WHERE id = ?",
                    [$actorId, $now, $requestId]
                );
            }

            $request = $db->fetch(
                "SELECT r.*, d.name as department_name, u.name, u.phone
                 FROM department_expense_requests r
                 INNER JOIN departments d ON d.id = r.department_id
                 LEFT JOIN users u ON u.id = r.requested_by
                 WHERE r.id = ?
                 LIMIT 1",
                [$requestId]
            );

            $financeApprovedBy = (int)($request['approved_by'] ?? 0);
            $pastorApprovedBy = (int)($request['pastor_approved_by'] ?? 0);
            $financeId = (int)($request['finance_id'] ?? 0);
            $isFullyApproved = ($financeApprovedBy > 0 && $pastorApprovedBy > 0);

            if ($isFullyApproved && strtolower((string)($request['status'] ?? '')) === 'pending') {
                if ($financeId <= 0) {
                    $txNo = $this->generateTransactionNumber($db);
                    $desc = 'DEPARTMENT EXPENSE REQUEST: ' . trim((string)($request['purpose'] ?? ''));
                    $ref = 'DEP-REQ-' . $requestId;
                    $today = date('Y-m-d');
                    $recordedBy = $financeApprovedBy;

                    if ($db->isPgsql()) {
                        $row = $db->fetch(
                            "INSERT INTO finances (transaction_number, member_id, department_id, transaction_type, amount, payment_method, transaction_date, description, reference_no, recorded_by)
                             VALUES (?, NULL, ?, 'Expense', ?, 'Cash', ?, ?, ?, ?)
                             RETURNING id",
                            [$txNo, (int)$request['department_id'], $amount, $today, $desc, $ref, $recordedBy]
                        );
                        $financeId = (int)($row['id'] ?? 0);
                    } else {
                        $db->query(
                            "INSERT INTO finances (transaction_number, member_id, department_id, transaction_type, amount, payment_method, transaction_date, description, reference_no, recorded_by)
                             VALUES (?, NULL, ?, 'Expense', ?, 'Cash', ?, ?, ?, ?)",
                            [$txNo, (int)$request['department_id'], $amount, $today, $desc, $ref, $recordedBy]
                        );
                        $financeId = (int)$db->getConnection()->lastInsertId();
                    }
                }

                $db->query(
                    "UPDATE department_expense_requests
                     SET status = 'approved',
                         finance_id = ?
                     WHERE id = ?",
                    [$financeId ?: null, $requestId]
                );
            }

            AuditLog::log('Approved department expense request', 'department_expense_requests', $requestId, null, [
                'department_id' => (int)$request['department_id'],
                'amount' => $amount,
                'finance_id' => $financeId
            ]);

            $reqPhone = trim((string)($request['phone'] ?? ''));
            if ($reqPhone !== '') {
                $currency = strtoupper(trim((string)AppConfig::getSetting('finance_currency', 'GHS')));
                if (!preg_match('/^[A-Z]{2,5}$/', $currency)) $currency = 'GHS';
                $toName = trim((string)($request['name'] ?? ''));
                $toName = $toName !== '' ? $toName : 'Department Head';
                $deptName = trim((string)($request['department_name'] ?? 'Department'));
                $purpose = trim((string)($request['purpose'] ?? ''));
                $msg = 'Dear ' . $toName . ', your expense request for ' . $deptName . ' of ' . $currency . ' ' . number_format($amount, 2) . ' has been APPROVED by ' . $actorName . '.';
                if (!$isFullyApproved) {
                    $msg .= $isFinanceHead ? ' Waiting for Pastor approval.' : ' Waiting for Head of Finance approval.';
                } else {
                    $msg .= ' Final status: APPROVED.';
                }
                if ($purpose !== '') {
                    $msg .= ' Purpose: ' . $purpose . '.';
                }
                $msg .= ' Ref: DEP-REQ-' . $requestId . '.';
                (new SmsService())->sendBulk([$reqPhone], $msg);
            }

            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => true, 'message' => $isFullyApproved ? 'Expense request fully approved.' : 'Expense request approved. Waiting for the other approval.']);
            }
            Session::flash('success', $isFullyApproved ? 'Expense request fully approved and recorded.' : 'Approval saved. Waiting for the other approval.');
        } catch (Throwable $e) {
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => false, 'message' => 'Error approving expense request.'], 500);
            }
            Session::flash('error', 'Error approving expense request: ' . $e->getMessage());
        }

        header('Location: ' . ($isPastor ? (BASE_URL . '/pastor') : (BASE_URL . '/finance')));
        exit;
    }

    public function rejectDepartmentExpenseRequest() {
        $this->ensureFinanceSchema();
        $isFinanceHead = Auth::isFinanceHead();
        $isPastor = Auth::isPastor();
        if (!$isFinanceHead && !$isPastor) {
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => false, 'message' => 'Unauthorized access.'], 403);
            }
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $requestId = (int)($_POST['request_id'] ?? 0);
        if ($requestId <= 0) {
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => false, 'message' => 'Expense request ID missing.'], 400);
            }
            Session::flash('error', 'Expense request ID missing.');
            header('Location: ' . BASE_URL . '/finance');
            exit;
        }

        $db = Database::getInstance();
        try {
            $request = $db->fetch(
                "SELECT r.*, d.name as department_name, u.name, u.phone
                 FROM department_expense_requests r
                 INNER JOIN departments d ON d.id = r.department_id
                 LEFT JOIN users u ON u.id = r.requested_by
                 WHERE r.id = ?
                 LIMIT 1",
                [$requestId]
            );
            if (!$request) {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Expense request not found.'], 404);
                }
                Session::flash('error', 'Expense request not found.');
                header('Location: ' . BASE_URL . '/finance');
                exit;
            }

            if (strtolower((string)($request['status'] ?? '')) !== 'pending') {
                if ($this->wantsJsonResponse()) {
                    $this->jsonResponse(['success' => false, 'message' => 'This expense request is no longer pending.'], 400);
                }
                Session::flash('error', 'This expense request is no longer pending.');
                header('Location: ' . ($isPastor ? (BASE_URL . '/pastor') : (BASE_URL . '/finance')));
                exit;
            }

            $db->query(
                "UPDATE department_expense_requests
                 SET status = 'rejected',
                     rejected_by = ?,
                     rejected_at = ?
                 WHERE id = ?",
                [(int)Session::get('user_id'), date('Y-m-d H:i:s'), $requestId]
            );

            AuditLog::log('Rejected department expense request', 'department_expense_requests', $requestId);

            $reqPhone = trim((string)($request['phone'] ?? ''));
            if ($reqPhone !== '') {
                $amount = (float)($request['amount'] ?? 0);
                $currency = strtoupper(trim((string)AppConfig::getSetting('finance_currency', 'GHS')));
                if (!preg_match('/^[A-Z]{2,5}$/', $currency)) $currency = 'GHS';
                $toName = trim((string)($request['name'] ?? ''));
                $toName = $toName !== '' ? $toName : 'Department Head';
                $deptName = trim((string)($request['department_name'] ?? 'Department'));
                $purpose = trim((string)($request['purpose'] ?? ''));
                $rejectedByName = trim((string)Session::get('user_name', $isPastor ? 'Pastor' : 'Finance Head'));
                $msg = 'Dear ' . $toName . ', your expense request for ' . $deptName . ' of ' . $currency . ' ' . number_format($amount, 2) . ' has been REJECTED by ' . $rejectedByName . '.';
                if ($purpose !== '') {
                    $msg .= ' Purpose: ' . $purpose . '.';
                }
                $msg .= ' Ref: DEP-REQ-' . $requestId . '.';
                (new SmsService())->sendBulk([$reqPhone], $msg);
            }
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => true, 'message' => 'Expense request rejected.']);
            }
            Session::flash('success', 'Expense request rejected.');
        } catch (Throwable $e) {
            if ($this->wantsJsonResponse()) {
                $this->jsonResponse(['success' => false, 'message' => 'Error rejecting expense request.'], 500);
            }
            Session::flash('error', 'Error rejecting expense request: ' . $e->getMessage());
        }

        header('Location: ' . ($isPastor ? (BASE_URL . '/pastor') : (BASE_URL . '/finance')));
        exit;
    }

    private function wantsJsonResponse() {
        $accept = strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? ''));
        $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
        return strpos($accept, 'application/json') !== false || $requestedWith === 'xmlhttprequest';
    }

    private function jsonResponse(array $payload, int $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($payload);
        exit;
    }

    public function updateBankDetails() {
        $this->ensureFinanceSchema();
        $db = Database::getInstance();
        $isDeptHead = (Session::get('user_role') === 'dept_head');
        if (Auth::isStaff()) {
            $this->isAdmin();
        }
        if (!$isDeptHead) {
            $this->isAdmin();
        }
        $bankName = trim($_POST['finance_bank_name'] ?? '');
        $accountName = trim($_POST['finance_account_name'] ?? '');
        $accountNumber = trim($_POST['finance_account_number'] ?? '');
        $branch = trim($_POST['finance_bank_branch'] ?? '');
        $currency = strtoupper(trim($_POST['finance_currency'] ?? 'GHS'));
        if (!preg_match('/^[A-Z]{2,5}$/', $currency)) {
            $currency = 'GHS';
        }

        try {
            if ($isDeptHead) {
                $myDeptId = (int)(Session::get('user_department_id') ?? 0);
                if ($myDeptId <= 0) {
                    throw new Exception('Department access is not configured for this account.');
                }

                $db->query(
                    "UPDATE departments
                     SET bank_name = ?, account_name = ?, account_number = ?, bank_branch = ?
                     WHERE id = ?",
                    [$bankName, $accountName, $accountNumber, $branch, $myDeptId]
                );
                AuditLog::log("Updated department bank details", "departments", $myDeptId);
            } else {
                $this->upsertSetting($db, 'finance_bank_name', $bankName);
                $this->upsertSetting($db, 'finance_account_name', $accountName);
                $this->upsertSetting($db, 'finance_account_number', $accountNumber);
                $this->upsertSetting($db, 'finance_bank_branch', $branch);
                $this->upsertSetting($db, 'finance_currency', $currency);
                AuditLog::log("Updated finance bank details", "settings");
            }

            Session::flash('success', 'Bank details updated successfully');
        } catch (Exception $e) {
            Session::flash('error', 'Error updating bank details: ' . $e->getMessage());
        }

        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/finance");
        exit;
    }

    private function ensureFinanceSchema() {
        $db = Database::getInstance();
        SchemaState::once('finance_schema_v5', function () use ($db) {
            $columns = [
                'department_id' => "ALTER TABLE finances ADD COLUMN department_id INT NULL",
                'reference_no' => "ALTER TABLE finances ADD COLUMN reference_no VARCHAR(100) NULL",
                'recorded_by' => "ALTER TABLE finances ADD COLUMN recorded_by INT NULL",
                'transaction_number' => "ALTER TABLE finances ADD COLUMN transaction_number VARCHAR(50) NULL",
                'offering_subtype' => "ALTER TABLE finances ADD COLUMN offering_subtype VARCHAR(60) NULL",
            ];

            foreach ($columns as $columnName => $alterSql) {
                if (!$db->columnExists('finances', $columnName)) {
                    $db->query($alterSql);
                }
            }

            if ($db->isMysql() && $db->getColumnDataType('finances', 'transaction_type') === 'enum') {
                $db->query("ALTER TABLE finances MODIFY COLUMN transaction_type VARCHAR(100) NULL");
            }

            if ($db->isMysql() && $db->getColumnDataType('finances', 'payment_method') === 'enum') {
                $db->query("ALTER TABLE finances MODIFY COLUMN payment_method VARCHAR(50) NULL");
            }

            $db->query(
                "UPDATE finances
                 SET transaction_type = CASE
                        WHEN transaction_type IS NOT NULL AND transaction_type <> '' THEN transaction_type
                        WHEN category IS NOT NULL AND category <> '' THEN category
                        WHEN department_id IS NOT NULL THEN 'Departmental Savings'
                        ELSE 'Offering'
                     END
                 WHERE transaction_type IS NULL OR transaction_type = ''"
            );

            $db->query(
                "UPDATE finances
                 SET payment_method = CASE
                        WHEN payment_method IS NOT NULL AND payment_method <> '' THEN payment_method
                        WHEN LOWER(COALESCE(subcategory, '')) IN ('cash', 'momo', 'mobile money', 'bank transfer', 'bank_transfer', 'check') THEN
                            CASE LOWER(COALESCE(subcategory, ''))
                                WHEN 'mobile money' THEN 'MoMo'
                                WHEN 'momo' THEN 'MoMo'
                                WHEN 'bank_transfer' THEN 'Bank Transfer'
                                WHEN 'bank transfer' THEN 'Bank Transfer'
                                WHEN 'check' THEN 'Check'
                                ELSE 'Cash'
                            END
                        ELSE 'Cash'
                     END
                 WHERE payment_method IS NULL OR payment_method = ''"
            );

            $db->query(
                "UPDATE finances
                 SET payment_method = CASE LOWER(COALESCE(payment_method, ''))
                        WHEN 'cash' THEN 'Cash'
                        WHEN 'momo' THEN 'MoMo'
                        WHEN 'mobile money' THEN 'MoMo'
                        WHEN 'bank_transfer' THEN 'Bank Transfer'
                        WHEN 'bank transfer' THEN 'Bank Transfer'
                        WHEN 'check' THEN 'Check'
                        ELSE payment_method
                     END"
            );

            $departmentColumns = [
                'bank_name' => "ALTER TABLE departments ADD COLUMN bank_name VARCHAR(120) NULL",
                'account_name' => "ALTER TABLE departments ADD COLUMN account_name VARCHAR(120) NULL",
                'account_number' => "ALTER TABLE departments ADD COLUMN account_number VARCHAR(80) NULL",
                'bank_branch' => "ALTER TABLE departments ADD COLUMN bank_branch VARCHAR(120) NULL"
            ];

            foreach ($departmentColumns as $columnName => $alterSql) {
                if (!$db->columnExists('departments', $columnName)) {
                    $db->query($alterSql);
                }
            }

            if (!$db->tableExists('finance_change_requests')) {
                if ($db->isPgsql()) {
                    $db->rawExec(
                        "CREATE TABLE IF NOT EXISTS finance_change_requests (
                            id BIGSERIAL PRIMARY KEY,
                            finance_id INTEGER NOT NULL REFERENCES finances(id) ON DELETE CASCADE,
                            requested_by INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                            reason TEXT NOT NULL,
                            status VARCHAR(20) NOT NULL DEFAULT 'pending',
                            created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            approved_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
                            approved_at TIMESTAMPTZ NULL,
                            rejected_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
                            rejected_at TIMESTAMPTZ NULL,
                            edit_count INTEGER NOT NULL DEFAULT 0,
                            fulfilled_at TIMESTAMPTZ NULL
                        );
                        CREATE INDEX IF NOT EXISTS idx_finance_change_requests_finance_id ON finance_change_requests (finance_id);
                        CREATE INDEX IF NOT EXISTS idx_finance_change_requests_requested_by ON finance_change_requests (requested_by);
                        CREATE INDEX IF NOT EXISTS idx_finance_change_requests_status ON finance_change_requests (status);"
                    );
                } else {
                    $db->rawExec(
                        "CREATE TABLE IF NOT EXISTS finance_change_requests (
                            id INT AUTO_INCREMENT PRIMARY KEY,
                            finance_id INT NOT NULL,
                            requested_by INT NOT NULL,
                            reason TEXT NOT NULL,
                            status VARCHAR(20) NOT NULL DEFAULT 'pending',
                            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                            approved_by INT NULL,
                            approved_at DATETIME NULL,
                            rejected_by INT NULL,
                            rejected_at DATETIME NULL,
                            edit_count INT NOT NULL DEFAULT 0,
                            fulfilled_at DATETIME NULL,
                            KEY idx_finance_change_requests_finance_id (finance_id),
                            KEY idx_finance_change_requests_requested_by (requested_by),
                            KEY idx_finance_change_requests_status (status),
                            CONSTRAINT fk_finance_change_requests_finance FOREIGN KEY (finance_id) REFERENCES finances(id) ON DELETE CASCADE,
                            CONSTRAINT fk_finance_change_requests_requested_by FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
                            CONSTRAINT fk_finance_change_requests_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
                            CONSTRAINT fk_finance_change_requests_rejected_by FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                    );
                }
            }

            $changeRequestColumns = [
                'approved_by' => $db->isPgsql()
                    ? "ALTER TABLE finance_change_requests ADD COLUMN approved_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL"
                    : "ALTER TABLE finance_change_requests ADD COLUMN approved_by INT NULL",
                'approved_at' => $db->isPgsql()
                    ? "ALTER TABLE finance_change_requests ADD COLUMN approved_at TIMESTAMPTZ NULL"
                    : "ALTER TABLE finance_change_requests ADD COLUMN approved_at DATETIME NULL",
                'rejected_by' => $db->isPgsql()
                    ? "ALTER TABLE finance_change_requests ADD COLUMN rejected_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL"
                    : "ALTER TABLE finance_change_requests ADD COLUMN rejected_by INT NULL",
                'rejected_at' => $db->isPgsql()
                    ? "ALTER TABLE finance_change_requests ADD COLUMN rejected_at TIMESTAMPTZ NULL"
                    : "ALTER TABLE finance_change_requests ADD COLUMN rejected_at DATETIME NULL",
                'edit_count' => $db->isPgsql()
                    ? "ALTER TABLE finance_change_requests ADD COLUMN edit_count INTEGER NOT NULL DEFAULT 0"
                    : "ALTER TABLE finance_change_requests ADD COLUMN edit_count INT NOT NULL DEFAULT 0",
                'fulfilled_at' => $db->isPgsql()
                    ? "ALTER TABLE finance_change_requests ADD COLUMN fulfilled_at TIMESTAMPTZ NULL"
                    : "ALTER TABLE finance_change_requests ADD COLUMN fulfilled_at DATETIME NULL"
            ];

            foreach ($changeRequestColumns as $columnName => $alterSql) {
                if ($db->tableExists('finance_change_requests') && !$db->columnExists('finance_change_requests', $columnName)) {
                    $db->query($alterSql);
                }
            }

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
                            pastor_approved_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL,
                            pastor_approved_at TIMESTAMPTZ NULL,
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
                            pastor_approved_by INT NULL,
                            pastor_approved_at DATETIME NULL,
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
                            CONSTRAINT fk_dep_expense_requests_pastor_approved_by FOREIGN KEY (pastor_approved_by) REFERENCES users(id) ON DELETE SET NULL,
                            CONSTRAINT fk_dep_expense_requests_approved_by FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL,
                            CONSTRAINT fk_dep_expense_requests_rejected_by FOREIGN KEY (rejected_by) REFERENCES users(id) ON DELETE SET NULL,
                            CONSTRAINT fk_dep_expense_requests_finance FOREIGN KEY (finance_id) REFERENCES finances(id) ON DELETE SET NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                    );
                }
            }

            $deptExpenseColumns = [
                'pastor_approved_by' => $db->isPgsql()
                    ? "ALTER TABLE department_expense_requests ADD COLUMN pastor_approved_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL"
                    : "ALTER TABLE department_expense_requests ADD COLUMN pastor_approved_by INT NULL",
                'pastor_approved_at' => $db->isPgsql()
                    ? "ALTER TABLE department_expense_requests ADD COLUMN pastor_approved_at TIMESTAMPTZ NULL"
                    : "ALTER TABLE department_expense_requests ADD COLUMN pastor_approved_at DATETIME NULL",
                'approved_by' => $db->isPgsql()
                    ? "ALTER TABLE department_expense_requests ADD COLUMN approved_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL"
                    : "ALTER TABLE department_expense_requests ADD COLUMN approved_by INT NULL",
                'approved_at' => $db->isPgsql()
                    ? "ALTER TABLE department_expense_requests ADD COLUMN approved_at TIMESTAMPTZ NULL"
                    : "ALTER TABLE department_expense_requests ADD COLUMN approved_at DATETIME NULL",
                'rejected_by' => $db->isPgsql()
                    ? "ALTER TABLE department_expense_requests ADD COLUMN rejected_by INTEGER NULL REFERENCES users(id) ON DELETE SET NULL"
                    : "ALTER TABLE department_expense_requests ADD COLUMN rejected_by INT NULL",
                'rejected_at' => $db->isPgsql()
                    ? "ALTER TABLE department_expense_requests ADD COLUMN rejected_at TIMESTAMPTZ NULL"
                    : "ALTER TABLE department_expense_requests ADD COLUMN rejected_at DATETIME NULL",
                'finance_id' => $db->isPgsql()
                    ? "ALTER TABLE department_expense_requests ADD COLUMN finance_id INTEGER NULL REFERENCES finances(id) ON DELETE SET NULL"
                    : "ALTER TABLE department_expense_requests ADD COLUMN finance_id INT NULL"
            ];

            foreach ($deptExpenseColumns as $columnName => $alterSql) {
                if ($db->tableExists('department_expense_requests') && !$db->columnExists('department_expense_requests', $columnName)) {
                    $db->query($alterSql);
                }
            }
        });
    }

    private function getStaffAllowedTypes() {
        $types = ['Offering', 'Tithe', 'Departmental Savings', 'Welfare', 'Expense'];
        if (Auth::isFinance()) {
            $types[] = 'Sunday School';
            $types[] = 'Annual Harvest';
            $types[] = 'Mini Harvest';
        }
        return $types;
    }

    private function sanitizeDateParam($value): ?string {
        $v = trim((string)($value ?? ''));
        if ($v === '') return null;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) return null;
        return $v;
    }

    public function downloadTransactions() {
        $this->ensureFinanceSchema();
        if (!Auth::isStaff() && !Auth::isDepartmentHead() && !Auth::isAuditor() && !Auth::isAdmin() && !Auth::isPastor()) {
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $mode = strtolower(trim((string)($_GET['mode'] ?? 'all')));
        if (!in_array($mode, ['all', 'date'], true)) $mode = 'all';

        $from = $this->sanitizeDateParam($_GET['from'] ?? ($_GET['tx_from'] ?? null));
        $to = $this->sanitizeDateParam($_GET['to'] ?? ($_GET['tx_to'] ?? null));
        if ($from !== null && $to === null) $to = $from;
        if ($to !== null && $from === null) $from = $to;

        $departmentId = null;
        $recordedBy = null;
        $allowedTypes = [];

        if (Auth::isDepartmentHead()) {
            $departmentId = (int)(Session::get('user_department_id') ?? 0);
            if ($departmentId <= 0) {
                Session::flash('error', 'Department access is not configured for this account.');
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            }
        }

        if (Auth::isStaff() && !Auth::isFinanceHead()) {
            $recordedBy = (int)(Session::get('user_id') ?? 0);
            $allowedTypes = $this->getStaffAllowedTypes();
        }

        $financeModel = new Finance();
        $rows = [];
        if ($mode === 'date' && $from !== null && $to !== null) {
            $rows = $financeModel->getTransactionsWithMetaByDateRange($from, $to, 5000, $departmentId, $recordedBy, $allowedTypes);
        } else {
            $rows = $financeModel->getTransactionsWithMetaByDateRange(null, null, 5000, $departmentId, $recordedBy, $allowedTypes);
            $from = null;
            $to = null;
        }

        $label = $from !== null && $to !== null ? ($from . '_to_' . $to) : 'all';
        $filename = 'transactions_' . $label . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if (!$out) exit;

        fputcsv($out, [
            'date',
            'transaction number',
            'transaction type',
            'amount',
            'payment method',
            'member',
            'department',
            'reference',
            'description',
            'recorded by'
        ]);

        foreach ($rows as $tx) {
            fputcsv($out, [
                (string)($tx['transaction_date'] ?? ''),
                (string)($tx['transaction_number'] ?? ''),
                (string)($tx['transaction_type'] ?? ''),
                (string)($tx['amount'] ?? ''),
                (string)($tx['payment_method'] ?? ''),
                (string)($tx['member_name'] ?? ''),
                (string)($tx['department_name'] ?? ''),
                (string)($tx['reference_no'] ?? ''),
                (string)($tx['description'] ?? ''),
                (string)($tx['recorded_by_name'] ?? '')
            ]);
        }
        fclose($out);
        exit;
    }

    public function downloadExpenses() {
        $this->ensureFinanceSchema();
        if (!Auth::isStaff() && !Auth::isAuditor() && !Auth::isAdmin()) {
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }
        if (Auth::isDepartmentHead()) {
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/finance');
            exit;
        }

        $mode = strtolower(trim((string)($_GET['mode'] ?? 'all')));
        if (!in_array($mode, ['all', 'date'], true)) $mode = 'all';

        $from = $this->sanitizeDateParam($_GET['from'] ?? ($_GET['exp_from'] ?? null));
        $to = $this->sanitizeDateParam($_GET['to'] ?? ($_GET['exp_to'] ?? null));
        if ($from !== null && $to === null) $to = $from;
        if ($to !== null && $from === null) $from = $to;

        $financeModel = new Finance();
        $rows = [];
        if ($mode === 'date' && $from !== null && $to !== null) {
            $rows = $financeModel->getExpensesWithMetaByDateRange($from, $to, 5000);
        } else {
            $rows = $financeModel->getExpensesWithMetaByDateRange(null, null, 5000);
            $from = null;
            $to = null;
        }

        $label = $from !== null && $to !== null ? ($from . '_to_' . $to) : 'all';
        $filename = 'expenses_' . $label . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if (!$out) exit;

        fputcsv($out, [
            'date',
            'transaction number',
            'amount',
            'department',
            'reference',
            'description',
            'recorded by'
        ]);

        foreach ($rows as $tx) {
            fputcsv($out, [
                (string)($tx['transaction_date'] ?? ''),
                (string)($tx['transaction_number'] ?? ''),
                (string)($tx['amount'] ?? ''),
                (string)(($tx['department_name'] ?? '') !== '' ? $tx['department_name'] : 'Church'),
                (string)($tx['reference_no'] ?? ''),
                (string)($tx['description'] ?? ''),
                (string)($tx['recorded_by_name'] ?? '')
            ]);
        }
        fclose($out);
        exit;
    }

    public function downloadDepartmentSavings() {
        $this->ensureFinanceSchema();
        if (!Auth::isStaff() && !Auth::isDepartmentHead() && !Auth::isAuditor() && !Auth::isAdmin() && !Auth::isPastor()) {
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $mode = strtolower(trim((string)($_GET['mode'] ?? 'month')));
        if (!in_array($mode, ['month', 'all'], true)) $mode = 'month';

        $dateParam = $this->sanitizeDateParam($_GET['date'] ?? null);
        $month = (int)($_GET['month'] ?? date('m'));
        $year = (int)($_GET['year'] ?? date('Y'));
        if ($month < 1 || $month > 12) $month = (int)date('m');
        if ($year < 2000 || $year > ((int)date('Y') + 1)) $year = (int)date('Y');
        if ($dateParam !== null) {
            $month = (int)date('m', strtotime($dateParam));
            $year = (int)date('Y', strtotime($dateParam));
        }

        $departmentId = null;
        if (Auth::isDepartmentHead()) {
            $departmentId = (int)(Session::get('user_department_id') ?? 0);
            if ($departmentId <= 0) {
                Session::flash('error', 'Department access is not configured for this account.');
                header('Location: ' . BASE_URL . '/dashboard');
                exit;
            }
        }

        $financeModel = new Finance();
        if ($mode === 'all') {
            $rows = $financeModel->getDepartmentSavingsSummaryAllTime($departmentId, null);
            $label = 'all';
        } else {
            $rows = $financeModel->getDepartmentSavingsSummary($month, $year, $departmentId, null);
            $label = sprintf('%04d-%02d', $year, $month);
        }

        $filename = 'department_savings_' . $label . '_' . date('Ymd_His') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'w');
        if (!$out) exit;

        fputcsv($out, [
            'department',
            'income',
            'expenses',
            'balance'
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                (string)($r['department_name'] ?? ''),
                (string)($r['income_total'] ?? ''),
                (string)($r['expense_total'] ?? ''),
                (string)($r['balance'] ?? '')
            ]);
        }
        fclose($out);
        exit;
    }

    private function generateTransactionNumber($db) {
        for ($i = 0; $i < 5; $i++) {
            $candidate = 'TX' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
            try {
                $row = $db->fetch("SELECT id FROM finances WHERE transaction_number = ? LIMIT 1", [$candidate]);
                if (!$row) return $candidate;
            } catch (Exception $e) {
                return $candidate;
            }
        }
        return 'TX' . date('YmdHis') . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));
    }

    private function getSetting($db, $key, $default = '') {
        return AppConfig::getSetting($key, $default);
    }

    private function upsertSetting($db, $key, $value) {
        $exists = $db->fetch("SELECT id FROM settings WHERE key_name = ?", [$key]);
        if ($exists) {
            $db->query("UPDATE settings SET value = ? WHERE key_name = ?", [$value, $key]);
        } else {
            $db->query("INSERT INTO settings (key_name, value) VALUES (?, ?)", [$key, $value]);
        }
        AppConfig::reset();
    }

}
