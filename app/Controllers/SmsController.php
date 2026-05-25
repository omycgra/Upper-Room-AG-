<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Member.php';
require_once __DIR__ . '/../Models/Cluster.php';
require_once __DIR__ . '/../Helpers/SmsService.php';

class SmsController extends BaseController {
    public function index() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $memberModel = new Member();
        $clusterModel = new Cluster();
        View::render('sms.index', [
            'title' => 'Bulk SMS Communication',
            'members' => $memberModel->all('first_name ASC'),
            'clusters' => $clusterModel->all('name ASC')
        ]);
    }

    public function balance() {
        if (Session::get('user_role') === 'dept_head') {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => false,
                'error' => 'Unauthorized access.'
            ]);
            exit;
        }

        $smsService = new SmsService();
        $result = $smsService->getBalance();

        header('Content-Type: application/json');
        echo json_encode($result);
        exit;
    }

    public function send() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $smsService = new SmsService();
        $db = Database::getInstance();
        $message = trim($_POST['message'] ?? '');
        $sendAll = ($_POST['send_all'] ?? '') === '1';
        $groupId = !empty($_POST['group_id']) ? (int)$_POST['group_id'] : null;
        $recipients = [];

        if ($sendAll) {
            $rows = $db->fetchAll("SELECT phone FROM members WHERE phone IS NOT NULL AND phone <> ''");
            $recipients = array_values(array_filter(array_map(fn($r) => trim($r['phone'] ?? ''), $rows)));
        } elseif ($groupId) {
            $rows = $db->fetchAll("SELECT phone FROM members WHERE cluster_id = ? AND phone IS NOT NULL AND phone <> ''", [$groupId]);
            $recipients = array_values(array_filter(array_map(fn($r) => trim($r['phone'] ?? ''), $rows)));
        } else {
            $recipients = $_POST['recipients'] ?? [];
            $recipients = array_values(array_filter(array_map('trim', (array)$recipients)));
        }
        
        if ($message === '') {
            Session::flash('error', 'Message content cannot be empty');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/sms");
            exit;
        }

        if (empty($recipients)) {
            Session::flash('error', 'Please select at least one recipient');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/sms");
            exit;
        }
        
        $result = $smsService->sendBulk($recipients, $message);
        
        if (($result['status'] ?? '') === 'success') {
            Session::flash('success', $result['message']);
        } elseif (($result['status'] ?? '') === 'warning') {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['message']);
        }
        
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/sms");
        exit;
    }
}
