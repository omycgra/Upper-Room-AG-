<?php

require_once __DIR__ . '/SmsService.php';

class FinancePaymentSmsService
{
    private const ELIGIBLE_TYPES = ['tithe', 'welfare', 'offering'];

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

            $transactionTypeRaw = trim((string)($transaction['transaction_type'] ?? ''));
            $transactionType = strtolower($transactionTypeRaw);
            if (!in_array($transactionType, self::ELIGIBLE_TYPES, true)) {
                return ['status' => 'skipped', 'message' => 'SMS skipped because this transaction type is not configured for automatic member alerts.'];
            }

            $memberId = (int)($transaction['member_id'] ?? 0);
            $phone = trim((string)($transaction['phone'] ?? ''));
            if ($memberId <= 0 || $phone === '') {
                return ['status' => 'skipped', 'message' => 'Transaction recorded, but SMS was not sent because the member phone number is missing.'];
            }

            $phones = self::extractPhoneNumbers($phone);
            if (empty($phones)) {
                return ['status' => 'skipped', 'message' => 'Transaction recorded, but SMS was not sent because the member phone number is invalid.'];
            }

            $existingLog = $db->fetch(
                "SELECT id FROM finance_payment_sms_logs WHERE finance_id = ? LIMIT 1",
                [$financeId]
            );
            if ($existingLog) {
                return ['status' => 'duplicate', 'message' => 'SMS was already sent for this transaction.'];
            }

            $message = self::buildMessage($transaction);
            $result = (new SmsService())->sendBulk($phones, $message);
            $sendStatus = (string)($result['status'] ?? 'error');
            if (!in_array($sendStatus, ['success', 'warning'], true)) {
                $debugRun = trim((string)($result['debug_run_id'] ?? ''));
                if ($debugRun !== '') {
                    $result['message'] = trim((string)($result['message'] ?? '')) . ' (Ref: ' . $debugRun . ')';
                }
                return [
                    'status' => 'error',
                    'message' => 'Transaction recorded, but SMS failed to send. ' . trim((string)($result['message'] ?? ''))
                ];
            }

            $debugRun = trim((string)($result['debug_run_id'] ?? ''));
            $provider = (string)AppConfig::getSetting('sms_provider', 'unknown');
            $db->query(
                "INSERT INTO finance_payment_sms_logs (finance_id, member_id, phone, transaction_type, amount, provider, message, sent_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $financeId,
                    $memberId,
                    (string)($phones[0] ?? $phone),
                    $transactionTypeRaw,
                    (float)($transaction['amount'] ?? 0),
                    $provider,
                    $message,
                    date('Y-m-d H:i:s')
                ]
            );

            AuditLog::log('Automatic finance payment SMS sent', 'finance_payment_sms_logs', $financeId, null, [
                'finance_id' => $financeId,
                'member_id' => $memberId,
                'transaction_type' => $transactionTypeRaw,
                'amount' => (float)($transaction['amount'] ?? 0),
                'provider' => $provider,
                'debug_run_id' => $debugRun,
                'phones_count' => count($phones)
            ]);

            $msg = 'Transaction recorded successfully and SMS sent to the member.';
            if ($debugRun !== '') {
                $msg .= ' (Ref: ' . $debugRun . ')';
            }
            return ['status' => 'sent', 'message' => $msg];
        } catch (Throwable $e) {
            error_log('FinancePaymentSmsService Failure: ' . $e->getMessage());
            return ['status' => 'error', 'message' => 'Transaction recorded, but SMS failed to send due to a system error.'];
        }
    }

    private static function extractPhoneNumbers(string $raw): array
    {
        $raw = trim($raw);
        if ($raw === '') return [];

        // First, try to find numbers in the raw string by removing common separators but keeping a sense of groups
        $clean = preg_replace('/[^\d+]/', '', $raw);
        
        $found = [];
        // Match Ghana formats: +233..., 233..., 0... (10 digits)
        if (preg_match_all('/(\+233|233|0)\d{9}/', $clean, $m)) {
            foreach (($m[0] ?? []) as $v) {
                $v = trim((string)$v);
                if ($v !== '') $found[] = $v;
            }
        }

        // If nothing found, try simpler 9-digit match (without leading 0)
        if (empty($found)) {
            if (preg_match_all('/\b\d{9}\b/', $clean, $m)) {
                foreach (($m[0] ?? []) as $v) {
                    $found[] = '233' . $v;
                }
            }
        }

        // Final fallback: original logic for split strings
        if (empty($found)) {
            $parts = preg_split('/[,\s\/;|]+/', $raw) ?: [];
            foreach ($parts as $p) {
                $p = preg_replace('/[^\d+]/', '', $p);
                if ($p !== '') $found[] = $p;
            }
        }

        $found = array_values(array_unique(array_filter(array_map('trim', $found))));
        return array_slice($found, 0, 3);
    }

    private static function buildMessage(array $transaction): string
    {
        $customPrefix = trim((string)AppConfig::getSetting('sms_message_prefix', ''));
        if ($customPrefix !== '') {
            $prefix = $customPrefix;
        } else {
            $churchName = trim((string)AppConfig::getSetting('church_name', 'CHURCH'));
            $churchNameClean = strip_tags(html_entity_decode($churchName, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $prefix = (strlen($churchNameClean) > 15) ? substr($churchNameClean, 0, 12) . '...' : $churchNameClean;
        }
        
        $currency = strtoupper(trim((string)AppConfig::getSetting('finance_currency', 'GHS')));
        $amount = number_format((float)($transaction['amount'] ?? 0), 2);
        
        $firstName = trim((string)($transaction['first_name'] ?? ''));
        $lastName = trim((string)($transaction['last_name'] ?? ''));
        $name = $firstName !== '' ? $firstName : ($lastName !== '' ? $lastName : 'Member');
        $nameClean = strip_tags(html_entity_decode($name, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        
        $typeRaw = trim((string)($transaction['transaction_type'] ?? 'contribution'));
        $type = ucfirst(strtolower($typeRaw));
        
        $template = trim((string)AppConfig::getSetting('sms_payment_template', ''));
        if ($template === '') {
            // Default template
            return "{$prefix}: Hi {$nameClean}, thank you for your {$type} contribution of {$currency} {$amount}. We appreciate your support. God bless you.";
        }

        // Parse template
        $search = ['{church_name}', '{name}', '{type}', '{currency}', '{amount}'];
        $replace = [$prefix, $nameClean, $type, $currency, $amount];
        
        return trim(str_replace($search, $replace, $template));
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
