<?php

class BaseController {
    public function __construct() {
        if (!Auth::check()) {
            header('Location: login');
            exit;
        }

        $currentRoute = $this->resolveCurrentRoute();

        if (Auth::isAuditor()) {
            $allowed = [
                'auditor',
                'reports',
                'reports/download',
                'logout'
            ];
            $this->guardRoute($currentRoute, $allowed);
        }

        if (Auth::isDepartmentHead()) {
            $allowed = [
                'dashboard',
                'logout',
                'members',
                'members/viewAjax',
                'members/store',
                'finance',
                'finance/memberSummary',
                'finance/memberTransactions',
                'finance/requestDepartmentExpense',
                'finance/myRequestUpdates',
                'finance/updateBankDetails',
                'chat/threads',
                'chat/messages',
                'chat/send'
            ];
            $this->guardRoute($currentRoute, $allowed);
        }

        if (Auth::isStaff()) {
            $allowed = [
                'dashboard',
                'logout',
                'finance',
                'finance/add',
                'finance/store',
                'finance/requestChange',
                'finance/updateTransaction',
                'chat/threads',
                'chat/messages',
                'chat/send'
            ];
            $allowed[] = 'finance/myRequestUpdates';
            if (Auth::isFinanceHead()) {
                $allowed[] = 'finance/approveChangeRequest';
                $allowed[] = 'finance/rejectChangeRequest';
                $allowed[] = 'finance/approveDepartmentExpenseRequest';
                $allowed[] = 'finance/rejectDepartmentExpenseRequest';
                $allowed[] = 'finance/pendingApprovals';
                $allowed[] = 'reports';
                $allowed[] = 'reports/download';
            }
            $this->guardRoute($currentRoute, $allowed);
        }

        if (Auth::isVisitationTeam()) {
            $allowed = [
                'dashboard',
                'logout',
                'visitors/export',
                'chat/threads',
                'chat/messages',
                'chat/send'
            ];
            $this->guardRoute($currentRoute, $allowed);
        }

        if (Auth::isPastor()) {
            $allowed = [
                'pastor',
                'logout',
                'reports',
                'reports/download',
                'chat/threads',
                'chat/messages',
                'chat/send'
            ];
            $this->guardRoute($currentRoute, $allowed);
        }
    }

    protected function isAdmin() {
        if (!Auth::isAdmin()) {
            Session::flash('error', 'Unauthorized access. Admins only.');
            header('Location: dashboard');
            exit;
        }
    }

    protected function renderPlaceholder($title) {
        $data = [
            'title' => $title,
            'content' => '<div class="bg-white p-8 rounded-xl shadow-sm text-center">
                <i class="fas fa-tools text-6xl text-gray-300 mb-4"></i>
                <h2 class="text-2xl font-bold text-gray-800">' . $title . ' Module</h2>
                <p class="text-gray-500 mt-2">This module is currently under development.</p>
                <a href="dashboard" class="mt-6 inline-block bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition-colors">Back to Dashboard</a>
            </div>'
        ];
        
        // We bypass the View::render slightly here to show a quick placeholder
        // or we can create a placeholder view file.
        // Let's create a placeholder view file instead for consistency.
        View::render('placeholder', $data);
    }

    private function resolveCurrentRoute() {
        $currentRoute = trim($_SERVER['REQUEST_URI'], '/');
        $scriptName = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($scriptName && strpos($currentRoute, $scriptName) === 0) {
            $currentRoute = trim(substr($currentRoute, strlen($scriptName)), '/');
        }
        if (($pos = strpos($currentRoute, '?')) !== false) {
            $currentRoute = substr($currentRoute, 0, $pos);
        }

        return $currentRoute === '' ? $this->resolveHomeRoute() : $currentRoute;
    }

    private function resolveHomeRoute(): string {
        if (Auth::isAuditor()) return 'auditor';
        if (Auth::isPastor()) return 'pastor';
        return 'dashboard';
    }

    private function guardRoute($currentRoute, array $allowed) {
        if (!in_array($currentRoute, $allowed, true)) {
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . $this->resolveHomeRoute());
            exit;
        }
    }
}
