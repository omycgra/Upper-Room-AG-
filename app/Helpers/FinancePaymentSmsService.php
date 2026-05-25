<?php

require_once __DIR__ . '/SmsService.php';

class FinancePaymentSmsService
{
    private const ELIGIBLE_TYPES = ['Tithe', 'Welfare'];

    public static function sendForTransaction(int $financeId): array
    {
        if ($financeId <= 0) {
            return ['status' => 'skipped', 'message' => 'SMS skipped because the transaction ID is invalid.'];
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
                    f.payment_method,
                    f.member_id,
                    f.recorded_by,
                    m.first_name,
                    m.last_name,
                    m.phone,
                    u.name as received_by_name
                 FROM finances f
                 LEFT JOIN members m ON m.id = f.member_id
                 LEFT JOIN users u ON u.id = f.recorded_by
                 WHERE f.id = ?
                 LIMIT 1",
                [$financeId]
            );

            if (!$transaction) {
                return ['status' => 'skipped', 'message' => 'SMS skipped because the transaction was not found.'];
            }

            $transactionType = trim((string)($transaction['transaction_type'] ?? ''));
            if (!in_array($transactionType, self::ELIGIBLE_TYPES, true)) {
                return ['status' => 'skipped', 'message' => 'SMS skipped because this transaction type is not configured for automatic member alerts.'];
            }

            $memberId = (int)($transaction['member_id'] ?? 0);
            $phone = trim((string)($transaction['phone'] ?? ''));
            if ($memberId <= 0 || $phone === '') {
                return ['status' => 'skipped', 'message' => 'Transaction recorded, but SMS was not sent because the member phone number is missing.'];
            }

            $existingLog = $db->fetch(
                "SELECT id FROM finance_payment_sms_logs WHERE finance_id = ? LIMIT 1",
                [$financeId]
            );
            if ($existingLog) {
                return ['status' => 'duplicate', 'message' => 'SMS was already sent for this transaction.'];
            }

            $message = self::buildMessage($transaction);
            $result = (new SmsService())->sendBulk([$phone], $message);
            if (($result['status'] ?? 'error') !== 'success') {
                return [
                    'status' => 'error',
                    'message' => 'Transaction recorded, but SMS failed to send. ' . trim((string)($result['message'] ?? ''))
                ];
            }

            $provider = (string)AppConfig::getSetting('sms_provider', 'unknown');
            $db->query(
                "INSERT INTO finance_payment_sms_logs (finance_id, member_id, phone, transaction_type, amount, provider, message, sent_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $financeId,
                    $memberId,
                    $phone,
                    $transactionType,
                    (float)($transaction['amount'] ?? 0),
                    $provider,
                    $message,
                    date('Y-m-d H:i:s')
                ]
            );

            AuditLog::log('Automatic finance payment SMS sent', 'finance_payment_sms_logs', $financeId, null, [
                'finance_id' => $financeId,
                'member_id' => $memberId,
                'transaction_type' => $transactionType,
                'amount' => (float)($transaction['amount'] ?? 0),
                'provider' => $provider
            ]);

            return ['status' => 'sent', 'message' => 'Transaction recorded successfully and SMS sent to the member.'];
        } catch (Throwable $e) {
            error_log('FinancePaymentSmsService Failure: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Transaction recorded, but SMS failed to send due to a system error.'];
        }
    }

    private static function buildMessage(array $transaction): string
    {
        $churchName = trim((string)AppConfig::getSetting('church_name', 'YOUR CHURCH'));
        $currency = strtoupper(trim((string)AppConfig::getSetting('finance_currency', 'GHS')));
        $amount = number_format((float)($transaction['amount'] ?? 0), 2);
        $name = trim((string)(($transaction['first_name'] ?? '') . ' ' . ($transaction['last_name'] ?? '')));
        if ($name === '') $name = 'MEMBER';
        $type = strtoupper(trim((string)($transaction['transaction_type'] ?? 'PAYMENT')));
        $transactionNumber = trim((string)($transaction['transaction_number'] ?? ''));
        $date = trim((string)($transaction['transaction_date'] ?? ''));
        $paymentMethod = trim((string)($transaction['payment_method'] ?? ''));
        $receivedBy = trim((string)($transaction['received_by_name'] ?? ''));

        $parts = [
            'Dear ' . $name . ',',
            'we have received your ' . $type . ' payment of ' . $currency . ' ' . $amount . '.'
        ];

        if ($receivedBy !== '') {
            $label = (strtolower($paymentMethod) === 'cash') ? 'Cash received by' : 'Received by';
            $parts[] = $label . ': ' . $receivedBy . '.';
        }

        if ($date !== '') {
            $parts[] = 'Date: ' . $date . '.';
        }

        if ($transactionNumber !== '') {
            $parts[] = 'Transaction No: ' . $transactionNumber . '.';
        }

        $parts[] = 'Thank you for your faithfulness.';
        $parts[] = $churchName . '.';

        return implode(' ', $parts);
    }

    private static function ensureSmsLogTable(Database $db): void
    {
        if ($db->tableExists('finance_payment_sms_logs')) {
            return;
        }

        if ($db->isPgsql()) {
            $db->rawExec(
                "CREATE TABLE IF NOT EXISTS finance_payment_sms_logs (
                    id BIGSERIAL PRIMARY KEY,
                    finance_id INTEGER NOT NULL REFERENCES finances(id) ON DELETE CASCADE,
                    member_id INTEGER NOT NULL REFERENCES members(id) ON DELETE CASCADE,
                    phone VARCHAR(50) NULL,
                    transaction_type VARCHAR(50) NULL,
                    amount NUMERIC(12,2) NOT NULL DEFAULT 0,
                    provider VARCHAR(50) NULL,
                    message TEXT NULL,
                    sent_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
                );
                CREATE UNIQUE INDEX IF NOT EXISTS idx_finance_payment_sms_logs_finance_id
                    ON finance_payment_sms_logs (finance_id);
                CREATE INDEX IF NOT EXISTS idx_finance_payment_sms_logs_member_id
                    ON finance_payment_sms_logs (member_id);"
            );
            return;
        }

        $db->rawExec(
            "CREATE TABLE IF NOT EXISTS finance_payment_sms_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                finance_id INT NOT NULL,
                member_id INT NOT NULL,
                phone VARCHAR(50) NULL,
                transaction_type VARCHAR(50) NULL,
                amount DECIMAL(12,2) NOT NULL DEFAULT 0,
                provider VARCHAR(50) NULL,
                message TEXT NULL,
                sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_finance_payment_sms_logs_finance_id (finance_id),
                KEY idx_finance_payment_sms_logs_member_id (member_id),
                CONSTRAINT fk_finance_payment_sms_logs_finance FOREIGN KEY (finance_id) REFERENCES finances(id) ON DELETE CASCADE,
                CONSTRAINT fk_finance_payment_sms_logs_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }
}
