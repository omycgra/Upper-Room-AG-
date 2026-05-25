<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../Models/Attendance.php';
require_once __DIR__ . '/../Models/Member.php';

class AttendanceController extends BaseController {
    public function index() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $attendanceModel = new Attendance();
        
        View::render('attendance.index', [
            'title' => 'Attendance Management',
            'attendance_rate' => $attendanceModel->getAttendanceRate(),
            'recent_records' => $attendanceModel->all('service_date DESC LIMIT 50')
        ]);
    }

    public function mark() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $memberModel = new Member();
        View::render('attendance.mark', [
            'title' => 'Mark Attendance',
            'members' => $memberModel->all('first_name ASC')
        ]);
    }

    public function store() {
        if (Session::get('user_role') === 'dept_head') {
            Session::flash('error', 'Unauthorized access.');
            $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
            header("Location: $base/dashboard");
            exit;
        }
        $attendanceModel = new Attendance();
        
        $memberIds = $_POST['member_ids'] ?? [];
        $serviceDate = $_POST['service_date'];
        $serviceType = $_POST['service_type'];
        
        foreach ($memberIds as $memberId) {
            $attendanceModel->create([
                'member_id' => $memberId,
                'service_date' => $serviceDate,
                'service_type' => $serviceType,
                'status' => 'Present'
            ]);
        }
        
        AuditLog::log("Marked attendance for $serviceType on $serviceDate", "attendance");
        Session::flash('success', 'Attendance marked successfully');
        $base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
        header("Location: $base/attendance");
        exit;
    }
}
