<?php

require_once __DIR__ . '/../Models/Member.php';
require_once __DIR__ . '/SmsService.php';

class BirthdayService
{
    public static function runDaily(): void
    {
        #region debug-point C:birthday-service-start
        (function() {
            $envPath = ROOT_PATH . '/.dbg/slow-page-load.env';
            $serverUrl = 'http://127.0.0.1:7777/event';
            $sessionId = 'slow-page-load';
            if (file_exists($envPath)) {
                $env = parse_ini_file($envPath);
                if (isset($env['DEBUG_SERVER_URL'])) $serverUrl = $env['DEBUG_SERVER_URL'];
                if (isset($env['DEBUG_SESSION_ID'])) $sessionId = $env['DEBUG_SESSION_ID'];
            }
            $data = json_encode([
                'sessionId' => $sessionId,
                'runId' => 'pre',
                'hypothesisId' => 'C',
                'location' => 'BirthdayService.php:runDaily',
                'msg' => '[DEBUG] BirthdayService runDaily called',
                'data' => [],
                'ts' => microtime(true) * 1000
            ]);
            $opts = ['http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => $data, 'timeout' => 0.5]];
            @file_get_contents($serverUrl, false, stream_context_create($opts));
        })();
        #endregion

        if (PHP_SAPI === 'cli') {
            return;
        }

        SchemaState::once('birthday_sms_dispatch_' . date('Ymd'), function (): void {
            $lockHandle = self::acquireDispatchLock();
            if (!$lockHandle) {
                return;
            }

            try {
                self::dispatchBirthdayWishes();
            } finally {
                self::releaseDispatchLock($lockHandle);
            }
        }, 21600);
    }

    private static function dispatchBirthdayWishes(): void
    {
        try {
            // Check if automatic birthday wishes are enabled in settings
            $autoEnabled = (string)AppConfig::getSetting('auto_birthday_sms', '0');
            if ($autoEnabled !== '1') {
                return;
            }

            $db = Database::getInstance();
            self::ensureBirthdayLogTable($db);

            $memberModel = new Member();
            $members = $memberModel->getTodaysBirthdays();
            if (empty($members)) {
                return;
            }

            $smsService = new SmsService();
            $currentYear = (int)date('Y');
            $provider = (string)AppConfig::getSetting('sms_provider', 'unknown');

            foreach ($members as $member) {
                $memberId = (int)($member['id'] ?? 0);
                $phone = trim((string)($member['phone'] ?? ''));

                if ($memberId <= 0 || $phone === '' || self::hasBirthdayWishForYear($db, $memberId, $currentYear)) {
                    continue;
                }

                $message = self::buildBirthdayMessage($member);
                $result = $smsService->sendBulk([$phone], $message);
                if (($result['status'] ?? 'error') !== 'success') {
                    continue;
                }

                $db->query(
                    "INSERT INTO birthday_sms_logs (member_id, phone, sent_year, message, provider, sent_at)
                     VALUES (?, ?, ?, ?, ?, ?)",
                    [$memberId, $phone, $currentYear, $message, $provider, date('Y-m-d H:i:s')]
                );

                AuditLog::log('Automatic birthday SMS sent', 'birthday_sms_logs', $memberId, null, [
                    'member_id' => $memberId,
                    'phone' => $phone,
                    'sent_year' => $currentYear,
                    'provider' => $provider
                ]);
            }
        } catch (Throwable $e) {
            error_log('BirthdayService Failure: ' . $e->getMessage());
        }
    }

    private static function buildBirthdayMessage(array $member): string
    {
        $customPrefix = trim((string)AppConfig::getSetting('sms_message_prefix', ''));
        if ($customPrefix !== '') {
            $prefix = $customPrefix;
        } else {
            $churchName = trim((string)AppConfig::getSetting('church_name', 'CHURCH'));
            $churchNameClean = strip_tags(html_entity_decode($churchName, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $prefix = (strlen($churchNameClean) > 15) ? substr($churchNameClean, 0, 12) . '...' : $churchNameClean;
        }

        $firstName = trim((string)($member['first_name'] ?? 'Member'));
        $nameClean = strip_tags(html_entity_decode($firstName, ENT_QUOTES | ENT_HTML5, 'UTF-8'));

        $template = trim((string)AppConfig::getSetting('sms_birthday_template', ''));
        if ($template === '') {
            // Default template
            return "{$prefix}: Happy Birthday {$nameClean}! We celebrate you today and pray that God blesses you with joy, favor, and peace in this new year. God bless you.";
        }

        // Parse template
        $search = ['{church_name}', '{name}'];
        $replace = [$prefix, $nameClean];

        return trim(str_replace($search, $replace, $template));
    }

    private static function hasBirthdayWishForYear(Database $db, int $memberId, int $year): bool
    {
        $row = $db->fetch(
            "SELECT id FROM birthday_sms_logs WHERE member_id = ? AND sent_year = ? LIMIT 1",
            [$memberId, $year]
        );

        return !empty($row['id']);
    }

    private static function ensureBirthdayLogTable(Database $db): void
    {
        if ($db->tableExists('birthday_sms_logs')) {
            return;
        }

        if ($db->isPgsql()) {
            $db->rawExec(
                "CREATE TABLE IF NOT EXISTS birthday_sms_logs (
                    id BIGSERIAL PRIMARY KEY,
                    member_id INTEGER NOT NULL REFERENCES members(id) ON DELETE CASCADE,
                    phone VARCHAR(50) NULL,
                    sent_year INTEGER NOT NULL,
                    message TEXT NULL,
                    provider VARCHAR(50) NULL,
                    sent_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
                );
                CREATE UNIQUE INDEX IF NOT EXISTS idx_birthday_sms_logs_member_year
                    ON birthday_sms_logs (member_id, sent_year);
                CREATE INDEX IF NOT EXISTS idx_birthday_sms_logs_sent_year
                    ON birthday_sms_logs (sent_year);"
            );
            return;
        }

        $db->rawExec(
            "CREATE TABLE IF NOT EXISTS birthday_sms_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                member_id INT NOT NULL,
                phone VARCHAR(50) NULL,
                sent_year INT NOT NULL,
                message TEXT NULL,
                provider VARCHAR(50) NULL,
                sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uniq_birthday_sms_logs_member_year (member_id, sent_year),
                KEY idx_birthday_sms_logs_sent_year (sent_year),
                CONSTRAINT fk_birthday_sms_logs_member FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
        );
    }

    private static function acquireDispatchLock()
    {
        $lockPath = rtrim(sys_get_temp_dir(), '\\/') . DIRECTORY_SEPARATOR . 'ag_birthday_sms.lock';
        $handle = @fopen($lockPath, 'c+');
        if ($handle === false) {
            return null;
        }

        if (!@flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            return null;
        }

        return $handle;
    }

    private static function releaseDispatchLock($handle): void
    {
        if (!is_resource($handle)) {
            return;
        }

        @flock($handle, LOCK_UN);
        fclose($handle);
    }
}
