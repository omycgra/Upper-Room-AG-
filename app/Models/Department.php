<?php
require_once __DIR__ . '/BaseModel.php';

class Department extends BaseModel {
    protected $table = 'departments';

    public function getDepartmentBankDetails($departmentId) {
        $departmentId = (int)$departmentId;
        if ($departmentId <= 0) {
            return null;
        }

        return $this->db->fetch(
            "SELECT id, name, bank_name, account_name, account_number, bank_branch
             FROM departments
             WHERE id = ?",
            [$departmentId]
        );
    }
}
