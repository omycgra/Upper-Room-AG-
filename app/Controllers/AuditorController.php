<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Finance.php';

class AuditorController extends BaseController {
    public function index() {
        if (!Auth::isAuditor()) {
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $financeModel = new Finance();
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

        $recentTransactions = $financeModel->getRecentTransactionsWithMeta(200);
        $recentExpenses = $financeModel->getRecentExpensesWithMeta(120);
        $departmentSavings = $financeModel->getDepartmentSavingsSummary($month, $year, null, null);

        $db = Database::getInstance();
        $currency = strtoupper(trim((string)(AppConfig::getSetting('finance_currency', 'GHS'))));
        if (!preg_match('/^[A-Z]{2,5}$/', $currency)) $currency = 'GHS';

        View::render('auditor.index', [
            'title' => 'Auditor Dashboard',
            'currency' => $currency,
            'month_label' => date('F Y'),
            'monthly_income' => $monthlyIncome,
            'monthly_expenses' => $monthlyExpenses,
            'monthly_balance' => $monthlyBalance,
            'general_all_time' => $generalAllTime,
            'department_all_time' => $departmentAllTime,
            'combined_all_time' => $combinedAllTime,
            'recent_transactions' => $recentTransactions,
            'recent_expenses' => $recentExpenses,
            'department_savings' => $departmentSavings
        ]);
    }
}
