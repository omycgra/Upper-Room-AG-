<?php
require_once __DIR__ . '/BaseModel.php';

class Visitor extends BaseModel {
    protected $table = 'visitors';

    public function getPendingFollowups() {
        return $this->where('follow_up_status', 'Pending');
    }

    public function getAllWithAssignee() {
        return $this->db->fetchAll(
            "SELECT v.*,
                    COALESCE(NULLIF(u.name, ''), NULLIF(u.username, ''), u.email) AS assigned_to_name,
                    d.name AS assigned_department_name
             FROM visitors v
             LEFT JOIN users u ON v.assigned_to = u.id
             LEFT JOIN departments d ON u.department_id = d.id
             ORDER BY v.visit_date DESC, v.id DESC"
        );
    }

    public function getVisitationAssignments($limit = null) {
        $sql = "SELECT v.*,
                       COALESCE(NULLIF(u.name, ''), NULLIF(u.username, ''), u.email) AS assigned_to_name,
                       d.name AS assigned_department_name
                FROM visitors v
                INNER JOIN users u ON v.assigned_to = u.id
                INNER JOIN departments d ON u.department_id = d.id
                WHERE LOWER(COALESCE(u.role, '')) = 'visitation_team'
                  AND LOWER(COALESCE(d.name, '')) LIKE '%visitation%'
                ORDER BY
                    CASE WHEN v.follow_up_date IS NULL THEN 1 ELSE 0 END ASC,
                    v.follow_up_date ASC,
                    v.visit_date DESC,
                    v.id DESC";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        return $this->db->fetchAll($sql);
    }
}
