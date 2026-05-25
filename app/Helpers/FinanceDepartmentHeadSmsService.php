<?php

require_once __DIR__ . '/SmsService.php';

class FinanceDepartmentHeadSmsService
{
    public static function sendForTransaction(int $financeId): array
    {
        if ($financeId <= 0) {
            return ['status' => 'skipped', 'message' => 'Department head SMS skipped because the transaction ID is invalid.'];
        }

        try {
            $db = Database::getInstance();
            self::ensureSmsLogTable($db);

            $transaction = $db->fetch(
                "SELECT
                    f.id,
                    f.transaction_number,
                    f.transaction_type,
                    f.amount,
                    f.transaction_date,
                    f.department_id,
                    d.name as department_name
                 FROM finances f
                 LEFT JOIN departments d ON d.id = f.department_id
                 WHERE f.id = ?
                 LIMIT 1",
                [$financeId]
            );

            if (!$transaction) {
                return ['status' => 'skipped', 'message' => 'Department head SMS skipped because the transaction was not found.'];
            }

            $departmentId = (int)($transaction['department_id'] ?? 0);
            if ($departmentId <= 0) {
                return ['status' => 'skipped', 'message' => 'Department head SMS skipped because this transaction is not linked to a department.'];
            }

            $heads = $db->fetchAll(
                "SELECT id, name, phone
                 FROM users
                 WHERE department_id = ?
                   AND LOWER(COALESCE(role, '')) IN ('dept_head', 'department_head', 'department head', 'dept head', 'departmenthead')
                 ORDER BY name ASC, id ASC",
                [$departmentId]
            ) ?: [];

            if (empty($heads)) {
                return ['status' => 'skipped', 'message' => 'Department head SMS skipped because no department head phone number was found.'];
            }

            $sent = 0;
            $skipped = 0;
            $failed = 0;
            $provider = (string)AppConfig::getSetting('sms_provider', 'unknown');
            $sms = new SmsService();

            foreach ($heads as $head) {
                $userId = (int)($head['id'] ?? 0);
                $phone = trim((string)($head['phone'] ?? ''));
                if ($userId <= 0 || $phone === '') {
                    $skipped++;
                    continue;
                }

                $existing = $db->fetch(
                    "SELECT id
                     FROM finance_department_sms_logs
                     WHERE finance_id = ?
                       AND user_id = ?
                     LIMIT 1",
                    [$financeId, $userId]
                );
                if ($existing) {
                    $skipped++;
                    continue;
                }

                $message = self::buildMessage($transaction, $head);
                $result = $sms->sendBulk([$phone], $message);
                if (($result['status'] ?? 'error') !== 'success') {
                    $failed++;
                    continue;
                }

                $db->query(
                    "INSERT INTO finance_department_sms_logs (finance_id, department_id, user_id, phone, provider, message, sent_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$financeId, $departmentId, $userId, $phone, $provider, $message, date('Y-m-d H:i:s')]
                );
                $sent++;
            }

            if ($sent > 0) {
                AuditLog::log('Automatic department head SMS sent', 'finance_department_sms_logs', $financeId, null, [
                    'finance_id' => $financeId,
                    'department_id' => $departmentId,
                    'sent' => $sent,
                    'failed' => $failed,
                    'skipped' => $skipped,
                    'provider' => $provider
                ]);
                return ['status' => 'sent', 'message' => 'Department head SMS sent for this department transaction.'];
            }

            if ($failed > 0) {
                return ['status' => 'error', 'message' => 'Department transaction saved, but department head SMS failed to send.'];
            }

            return ['status' => 'skipped', 'message' => 'Department head SMS skipped because no eligible department head recipient was available.'];
        } catch (Throwable $e) {
            error_log('FinanceDepartmentHeadSmsService Failure: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Department transaction saved, but department head SMS failed due to a system error.'];
        }
    }

    private static function buildMessage(array $transaction, array $head): string
    {
        $churchName = trim((string)AppConfig::getSetting('church_name', 'YOUR CHURCH'));
        $currency = strtoupper(trim((string)AppConfig::getSetting('finance_currency', 'GHS')));
        $amount = number_format((float)($transaction['amount'] ?? 0), 2);
        $type = trim((string)($transaction['transaction_type'] ?? 'Transaction'));
        $department = trim((string)($transaction['department_name'] ?? 'Department'));
        $date = trim((string)($transaction['transaction_date'] ?? ''));
        $transactionNumber = trim((string)($transaction['transaction_number'] ?? ''));
        $name = trim((string)($head['name'] ?? 'Department Head'));

        $parts = [
            'Hello ' . $name . ',',
            $type . ' of ' . $currency . ' ' . $amount . ' has been recorded for ' . $department . '.'
        ];

        if ($date !== '') {
            $parts[] = 'Date: ' . $date . '.';
        }

        if ($transactionNumber !== '') {
            $parts[] = 'Transaction No: ' . $transactionNumber . '.';
        }

        $parts[] = $churchName . '.';

        return implode(' ', $parts);
    }

    private static function ensureSmsLogTable(Database $db): void
    {
        if ($db->tableExists('finance_department_sms_logs')) {
            return;
        }

        if ($db->isPgsql()) {
            $db->rawExec(
                "CREATE TABLE IF NOT EXISTS finance_department_sms_logs (
                    id BIGSERIAL PRIMARY KEY,
                    finance_id INTEGER NOT NULL REFERENCES finances(id) ON DELETE CASCADE,
                    department_id INTEGER NOT NULL REFERENCES departments(id) ON DELETE CASCADE,
                    user_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
                    phone VARCHAR(50) NULL,
                    provider VARCHAR(50) NULL,
                    message TEXT NULL,
                    sent_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
                );
                CREATE UNIQUE INDEX IF NOT EXISTS idx_finance_department_sms_logs_unique
                    ON finance_department_sms_logs (finance_id, user_id);
                CREATE INDEX IF NOT EXISTS idx_finance_department_sms_logs_department_id
                    ON finance_department_sms_logs (department_id);"
            );
            return;
        }

        $db->rawExec(
            "CREATE TABLE IF NOT EXISTS finance_department_sms_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                finance_id INT NOT NULL,
                department_id INT NOT NULL,
                user_id INT NOT NULL,
                phone VARCHAR(50) NULL,
                provider VARCHAR(50) NULL,
                message TEXT NULL,
                sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_finance_department_sms_logs_finance_user (finance_id, user_id),
                KEY idx_finance_department_sms_logs_department_id (department_id),
                CONSTRAINT fk_finance_department_sms_logs_finance FOREIGN KEY (finance_id) REFERENCES finances(id) ON DELETE CASCADE,
                CONSTRAINT fk_finance_department_sms_logs_department FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
                CONSTRAINT fk_finance_department_sms_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}
