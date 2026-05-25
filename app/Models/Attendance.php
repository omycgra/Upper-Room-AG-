<?php
require_once __DIR__ . '/BaseModel.php';

class Attendance extends BaseModel {
    protected $table = 'attendance';

    public function getServiceAttendance($date, $type) {
        $sql = "SELECT a.*, m.first_name, m.last_name 
                FROM attendance a
                JOIN members m ON a.member_id = m.id
                WHERE a.service_date = ? AND a.service_type = ?";
        return $this->db->fetchAll($sql, [$date, $type]);
    }

    public function getAttendanceRate($days = 30) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime('-' . (int)$days . ' days'));
        $sql = "SELECT
                COALESCE(
                    (SELECT COUNT(*) FROM attendance WHERE status = 'Present' AND service_date >= ?) /
                    NULLIF((SELECT COUNT(*) FROM attendance WHERE service_date >= ?), 0) * 100,
                    0
                ) as rate";
        $result = $this->db->fetch($sql, [$cutoffDate, $cutoffDate]);
        return ($result && $result['rate'] !== null) ? round($result['rate'], 2) . '%' : '0%';
    }
}
