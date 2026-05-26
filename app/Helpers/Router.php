<?php

class Router {
    protected $routes = [];

    public function __construct() {
        // Auth routes
        $this->add('login', 'AuthController@showLogin');
        $this->add('logout', 'AuthController@logout');
        $this->add('forgot-password', 'AuthController@forgotPassword');
        $this->add('reset-password', 'AuthController@resetPassword');

        // Root route opens login first; authenticated users are redirected onward
        $this->add('', 'AuthController@showLogin');
        $this->add('dashboard', 'DashboardController@index');
        $this->add('dashboard/birthdaysThisMonth', 'DashboardController@birthdaysThisMonth');
        $this->add('pastor', 'PastorController@index');
        
        // Module routes
        $this->add('members', 'MemberController@index');
        $this->add('members/add', 'MemberController@add');
        $this->add('members/store', 'MemberController@store');
        $this->add('members/update', 'MemberController@update');
        $this->add('members/delete', 'MemberController@delete');
        $this->add('members/viewAjax', 'MemberController@viewAjax');
        $this->add('members/template', 'MemberController@downloadTemplate');
        $this->add('members/import', 'MemberController@importExcel');
        $this->add('members/export', 'MemberController@exportAll');

        $this->add('visitors', 'VisitorController@index');
        $this->add('visitors/add', 'VisitorController@add');
        $this->add('visitors/store', 'VisitorController@store');
        $this->add('visitors/export', 'VisitorController@exportAssigned');
        $this->add('visitors/approve', 'VisitorController@approve');
        $this->add('visitors/details', 'VisitorController@details');
        $this->add('visitors/assign', 'VisitorController@assign');
        $this->add('attendance', 'AttendanceController@index');
        $this->add('attendance/mark', 'AttendanceController@mark');
        $this->add('attendance/store', 'AttendanceController@store');
        $this->add('attendance/syncBioTime', 'AttendanceController@syncBioTime');
        $this->add('attendance/quick', 'AttendanceController@quick');
        $this->add('attendance/quickMark', 'AttendanceController@quickMark');
        $this->add('attendance/pushOnline', 'AttendanceController@pushOnline');
        $this->add('attendance/download', 'AttendanceController@download');
        $this->add('api/attendance/import', 'AuthController@importAttendance');
        $this->add('finance', 'FinanceController@index');
        $this->add('finance/add', 'FinanceController@add');
        $this->add('finance/store', 'FinanceController@store');
        $this->add('finance/updateBankDetails', 'FinanceController@updateBankDetails');
        $this->add('finance/memberSummary', 'FinanceController@memberSummary');
        $this->add('finance/memberTransactions', 'FinanceController@memberTransactions');
        $this->add('finance/updateTransaction', 'FinanceController@updateTransaction');
        $this->add('finance/requestChange', 'FinanceController@requestChange');
        $this->add('finance/approveChangeRequest', 'FinanceController@approveChangeRequest');
        $this->add('finance/rejectChangeRequest', 'FinanceController@rejectChangeRequest');
        $this->add('finance/pendingApprovals', 'FinanceController@pendingApprovals');
        $this->add('finance/myRequestUpdates', 'FinanceController@myRequestUpdates');
        $this->add('finance/requestDepartmentExpense', 'FinanceController@requestDepartmentExpense');
        $this->add('finance/approveDepartmentExpenseRequest', 'FinanceController@approveDepartmentExpenseRequest');
        $this->add('finance/rejectDepartmentExpenseRequest', 'FinanceController@rejectDepartmentExpenseRequest');
        $this->add('transactions', 'FinanceController@transactions');
        $this->add('department-savings', 'FinanceController@departmentSavings');
        $this->add('sms', 'SmsController@index');
        $this->add('sms/balance', 'SmsController@balance');
        $this->add('sms/send', 'SmsController@send');
        $this->add('debug/sms-logs', 'DebugController@smsLogs');
        $this->add('debug/sms-logs/clear', 'DebugController@clearSmsLogs');
        $this->add('equipment', 'EquipmentController@index');
        $this->add('reports', 'ReportController@index');
        $this->add('reports/download', 'ReportController@download');
        $this->add('chat/threads', 'ChatController@threads');
        $this->add('chat/messages', 'ChatController@messages');
        $this->add('chat/send', 'ChatController@send');
        $this->add('chat/delete', 'ChatController@deleteMessage');
        $this->add('chat/clear', 'ChatController@clearThread');
        $this->add('settings', 'SettingController@index');
        $this->add('settings/updateBranding', 'SettingController@updateBranding');
        $this->add('settings/updateChurchName', 'SettingController@updateChurchName');
        $this->add('settings/addUser', 'SettingController@addUser');
        $this->add('settings/updateTheme', 'SettingController@updateTheme');
        $this->add('settings/updateProfile', 'SettingController@updateProfile');
        $this->add('settings/updateSmsConfig', 'SettingController@updateSmsConfig');
        $this->add('settings/updateAttendanceConfig', 'SettingController@updateAttendanceConfig');
        $this->add('settings/updateDatabaseConnection', 'SettingController@updateDatabaseConnection');
        $this->add('settings/user/add', 'SettingController@addUser');
        $this->add('settings/user/delete', 'SettingController@deleteUser');
        $this->add('settings/user/resetPassword', 'SettingController@resetUserPassword');
        $this->add('settings/user/updateUsername', 'SettingController@updateUserUsername');
        $this->add('settings/user/updateRole', 'SettingController@updateUserRole');
        $this->add('settings/user/updatePhoto', 'SettingController@updateUserPhoto');
        $this->add('settings/user/approvePasswordReset', 'SettingController@approvePasswordReset');
        $this->add('settings/user/rejectPasswordReset', 'SettingController@rejectPasswordReset');
        $this->add('cluster', 'ClusterController@index');
        $this->add('cluster/store', 'ClusterController@store');
        $this->add('cluster/update', 'ClusterController@update');
        $this->add('cluster/delete', 'ClusterController@delete');
        $this->add('cluster/viewAjax', 'ClusterController@viewAjax');
        $this->add('departments', 'DepartmentController@index');
        $this->add('departments/store', 'DepartmentController@store');
        $this->add('departments/update', 'DepartmentController@update');
        $this->add('departments/delete', 'DepartmentController@delete');
        $this->add('auditor', 'AuditorController@index');
    }

    public function add($route, $controller) {
        $this->routes[$route] = $controller;
    }

    public function dispatch() {
        $uri = trim($_SERVER['REQUEST_URI'], '/');
        
        // Handle sub-directory if necessary (e.g., /AG/dashboard)
        $scriptName = trim(dirname($_SERVER['SCRIPT_NAME']), '/');
        if ($scriptName && strpos($uri, $scriptName) === 0) {
            $uri = trim(substr($uri, strlen($scriptName)), '/');
        }

        // Remove query strings
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        // Handle POST login separately or via the same controller action
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $uri === 'login') {
            $this->callAction('AuthController@login');
            return;
        }

        if (array_key_exists($uri, $this->routes)) {
            // Basic Authentication Guard
            if (!in_array($uri, ['login', 'forgot-password', 'reset-password', 'api/attendance/import', 'attendance/quick', 'attendance/quickMark'], true) && !Auth::check()) {
                $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
                header("Location: $base/login");
                exit;
            }
            $this->callAction($this->routes[$uri]);
        } else {
            // Default to 404 or redirect to login/dashboard
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            if (!Auth::check()) {
                header("Location: $base/login");
            } else {
                header("Location: $base/dashboard");
            }
            exit;
        }
    }

    protected function callAction($controllerAction) {
        list($controller, $action) = explode('@', $controllerAction);
        
        $controllerPath = __DIR__ . '/../Controllers/' . $controller . '.php';
        
        if (file_exists($controllerPath)) {
            require_once $controllerPath;
            $controllerInstance = new $controller();
            $controllerInstance->$action();
        } else {
            die("Controller $controller not found at $controllerPath");
        }
    }
}
