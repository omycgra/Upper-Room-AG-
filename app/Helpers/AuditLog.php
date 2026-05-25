<?php

class AuditLog {
    public static function log($action, $entityType = null, $entityId = null, $oldValues = null, $newValues = null) {
        $db = Database::getInstance();
        $userId = Session::get('user_id');
        
        $sql = "INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues) : null,
            $newValues ? json_encode($newValues) : null,
            $_SERVER['REMOTE_ADDR'] ?? null,
            $_SERVER['HTTP_USER_AGENT'] ?? null
        ];
        
        try {
            $db->query($sql, $params);
        } catch (Exception $e) {
            // Silently fail or log to error log to avoid crashing the main operation
            error_log("Audit Log Failure: " . $e->getMessage());
        }
    }
}
