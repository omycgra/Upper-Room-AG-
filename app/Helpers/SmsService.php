<?php

class SmsService {
    private $provider;
    private $apiKey;
    private $senderId;
    private $naloPrefix;
    private $baseUrl;
    private $infobipBaseUrl;
    private $twilioAccountSid;
    private $twilioAuthToken;
    private $twilioFrom;
    private $debugSessionId = 'sms-not-delivered';

    public function __construct() {
        $db = Database::getInstance();

        $this->provider = strtolower(trim($this->getSetting($db, 'sms_provider', 'nalo')));
        if (!in_array($this->provider, ['nalo', 'mnotify', 'twilio', 'infobip'], true)) {
            $this->provider = 'nalo';
        }
        $this->upsertSetting($db, 'sms_provider', $this->provider);

        $this->apiKey = $this->getSetting($db, 'sms_api_key', '');

        $churchName = $this->getSetting($db, 'church_name', 'CHURCH');
        $senderFromDb = $this->getSetting($db, 'sms_sender_id', '');
        $sender = $senderFromDb !== '' ? $senderFromDb : $churchName;
        $this->senderId = $this->normalizeSenderId($sender);
        if ($senderFromDb === '') {
            $this->upsertSetting($db, 'sms_sender_id', $this->senderId);
        }

        $this->naloPrefix = $this->getSetting($db, 'sms_nalo_prefix', 'Resl_Nalo');
        $this->baseUrl = $this->getSetting($db, 'sms_base_url', 'https://sms.nalosolutions.com/smsbackend/clientapi/{prefix}/send-message/');
        $this->upsertSetting($db, 'sms_nalo_prefix', $this->naloPrefix);
        $this->upsertSetting($db, 'sms_base_url', $this->baseUrl);
        $this->infobipBaseUrl = $this->normalizeInfobipBaseUrl($this->getSetting($db, 'sms_infobip_base_url', 'https://api.infobip.com'));
        $this->upsertSetting($db, 'sms_infobip_base_url', $this->infobipBaseUrl);

        $this->twilioAccountSid = $this->getSetting($db, 'sms_twilio_account_sid', '');
        $this->twilioAuthToken = $this->getSetting($db, 'sms_twilio_auth_token', '');
        $this->twilioFrom = $this->getSetting($db, 'sms_twilio_from', '');
    }

    public function sendBulk($recipients, $message) {
        $debugRunId = 'pre-' . bin2hex(random_bytes(6));
        $message = trim((string)$message);
        if ($message === '') {
            return ['status' => 'error', 'message' => 'Message cannot be empty', 'debug_run_id' => $debugRunId];
        }

        $inputRecipients = (array)$recipients;
        $normalizedPairs = [];
        $normalizedList = array_map(function ($phone) use (&$normalizedPairs) {
            $raw = trim((string)$phone);
            $normalized = '';
            if ($this->provider === 'twilio' || $this->provider === 'infobip') {
                $normalized = $this->normalizePhoneE164($raw);
            } else {
                $normalized = $this->normalizePhone($raw);
            }
            $normalizedPairs[] = ['raw' => $raw, 'normalized' => $normalized];
            return $normalized;
        }, $inputRecipients);
        $recipients = array_values(array_unique(array_filter($normalizedList)));
        if (count($recipients) === 0) {
            return ['status' => 'error', 'message' => 'No valid recipients', 'debug_run_id' => $debugRunId];
        }

        // #region debug-point sms-1
        $this->dbgEvent('H2', $debugRunId, [
            'provider' => $this->provider,
            'sender_id' => $this->senderId,
            'recipient_count_raw' => count($inputRecipients),
            'recipient_count_normalized' => count($recipients),
            'recipient_samples' => array_slice($normalizedPairs, 0, 5),
            'message' => $message,
            'message_len' => mb_strlen($message),
        ]);
        // #endregion debug-point sms-1

        $apiKeys = in_array($this->provider, ['nalo', 'mnotify'], true) ? $this->getCandidateApiKeys($this->apiKey) : [''];
        $activeApiKey = $apiKeys[0] ?? '';
        $lastError = null;
        $results = [
            'sent' => 0,
            'failed' => 0,
            'failures' => []
        ];

        if ($this->provider === 'mnotify') {
            $batchResults = $this->sendBulkViaMnotify($recipients, $message, $apiKeys);
            // #region debug-point sms-2
            $this->dbgEvent('H1', $debugRunId, [
                'provider' => 'mnotify',
                'batch_results' => [
                    'sent' => (int)($batchResults['sent'] ?? 0),
                    'failed' => (int)($batchResults['failed'] ?? 0),
                    'last_error' => (string)($batchResults['last_error'] ?? ''),
                    'failures_sample' => array_slice((array)($batchResults['failures'] ?? []), 0, 5)
                ],
            ]);
            // #endregion debug-point sms-2
            AuditLog::log("Sent Bulk SMS", "sms_logs", null, null, [
                'recipient_count' => count($recipients),
                'sent' => (int)($batchResults['sent'] ?? 0),
                'failed' => (int)($batchResults['failed'] ?? 0)
            ]);

            if (($batchResults['sent'] ?? 0) > 0 && ($batchResults['failed'] ?? 0) === 0) {
                return [
                    'status' => 'success',
                    'message' => 'SMS sent successfully to ' . (int)$batchResults['sent'] . ' recipients',
                    'debug_run_id' => $debugRunId
                ];
            }

            if (($batchResults['sent'] ?? 0) === 0) {
                $detail = !empty($batchResults['last_error']) ? (' (' . $batchResults['last_error'] . ')') : '';
                return [
                    'status' => 'error',
                    'message' => 'SMS failed to send. Please verify your SMS API configuration.' . $detail,
                    'debug_run_id' => $debugRunId
                ];
            }

            return [
                'status' => 'warning',
                'message' => 'SMS sent to ' . (int)$batchResults['sent'] . ' recipients. Failed: ' . (int)$batchResults['failed'],
                'debug_run_id' => $debugRunId
            ];
        }

        foreach ($recipients as $phone) {
            if ($this->provider === 'twilio') {
                $resp = $this->sendViaTwilio($phone, $message);
            } elseif ($this->provider === 'infobip') {
                $resp = $this->sendViaInfobip($phone, $message);
            } else {
                $resp = $this->sendViaNalo($activeApiKey, $this->senderId, $phone, $message);
            }

            // #region debug-point sms-3
            $this->dbgEvent('H1', $debugRunId, [
                'provider' => $this->provider,
                'to' => $phone,
                'ok' => (bool)($resp['ok'] ?? false),
                'error' => (string)($resp['error'] ?? ''),
                'raw' => isset($resp['raw']) ? $this->dbgTrunc((string)$resp['raw'], 600) : '',
                'http' => isset($resp['http']) ? (int)$resp['http'] : null
            ]);
            // #endregion debug-point sms-3

            if (in_array($this->provider, ['nalo', 'mnotify'], true) && ($resp['ok'] ?? false) !== true && count($apiKeys) > 1 && $results['sent'] === 0 && $results['failed'] === 0) {
                foreach ($apiKeys as $candidate) {
                    if ($candidate === $activeApiKey) continue;
                    $try = $this->provider === 'mnotify'
                        ? $this->sendViaMnotify($candidate, $this->senderId, $phone, $message)
                        : $this->sendViaNalo($candidate, $this->senderId, $phone, $message);
                    if (($try['ok'] ?? false) === true) {
                        $activeApiKey = $candidate;
                        $resp = $try;
                        break;
                    }
                    $lastError = $try['error'] ?? $lastError;
                }
            }

            if (($resp['ok'] ?? false) === true) {
                $results['sent']++;
            } else {
                $results['failed']++;
                $lastError = $resp['error'] ?? $lastError;
                $results['failures'][] = [
                    'to' => $phone,
                    'error' => $resp['error'] ?? 'Failed'
                ];
            }
        }

        AuditLog::log("Sent Bulk SMS", "sms_logs", null, null, [
            'recipient_count' => count($recipients),
            'sent' => $results['sent'],
            'failed' => $results['failed']
        ]);

        if ($results['sent'] > 0 && $results['failed'] === 0) {
            return [
                'status' => 'success',
                'message' => 'SMS sent successfully to ' . $results['sent'] . ' recipients',
                'debug_run_id' => $debugRunId
            ];
        }

        if ($results['sent'] === 0) {
            $detail = $lastError ? (' (' . $lastError . ')') : '';
            return [
                'status' => 'error',
                'message' => 'SMS failed to send. Please verify your SMS API configuration.' . $detail,
                'debug_run_id' => $debugRunId
            ];
        }

        return [
            'status' => 'warning',
            'message' => 'SMS sent to ' . $results['sent'] . ' recipients. Failed: ' . $results['failed'],
            'debug_run_id' => $debugRunId
        ];
    }

    public function sendToMember($memberId, $message) {
        $memberModel = new Member();
        $member = $memberModel->find($memberId);
        
        if ($member && $member['phone']) {
            return $this->sendBulk([$member['phone']], $message);
        }
        
        return ['status' => 'error', 'message' => 'Member not found or has no phone number'];
    }

    public function getBalance() {
        if ($this->provider === 'infobip') {
            return $this->getInfobipBalance();
        }

        if ($this->provider === 'mnotify') {
            $apiKeys = $this->getCandidateApiKeys($this->apiKey);
            $lastError = 'MNotify API key not configured';

            foreach ($apiKeys as $candidate) {
                $candidate = trim((string)$candidate);
                if ($candidate === '') {
                    continue;
                }

                $response = $this->getMnotifyBalance($candidate);
                if (($response['ok'] ?? false) === true) {
                    return $response;
                }

                $lastError = $response['error'] ?? $lastError;
            }

            return [
                'ok' => false,
                'provider' => 'mnotify',
                'error' => $lastError
            ];
        }

        if ($this->provider === 'twilio') {
            return $this->getTwilioBalance();
        }

        return [
            'ok' => false,
            'provider' => $this->provider,
            'error' => 'Live balance is not available for the current SMS provider.'
        ];
    }

    private function getSetting($db, $key, $default = '') {
        try {
            $row = $db->fetch("SELECT value FROM settings WHERE key_name = ?", [$key]);
            if ($row && array_key_exists('value', $row) && $row['value'] !== null) {
                return (string)$row['value'];
            }
        } catch (Exception $e) {
        }
        return $default;
    }

    private function upsertSetting($db, $key, $value) {
        try {
            $exists = $db->fetch("SELECT id FROM settings WHERE key_name = ?", [$key]);
            if ($exists) {
                $db->query("UPDATE settings SET value = ? WHERE key_name = ?", [$value, $key]);
            } else {
                $db->query("INSERT INTO settings (key_name, value) VALUES (?, ?)", [$key, $value]);
            }
        } catch (Exception $e) {
        }
    }

    private function normalizeSenderId($sender) {
        $sender = trim((string)$sender);
        if ($sender === '') return 'CHURCH';
        $sender = preg_replace('/\s+/', ' ', $sender);
        $sender = str_replace(' ', '', $sender);
        $sender = preg_replace('/[^A-Za-z0-9]/', '', $sender);
        if ($sender === '') return 'CHURCH';
        return substr($sender, 0, 11);
    }

    private function normalizePhone($phone) {
        $phone = $this->extractFirstPhoneCandidate($phone);
        if ($phone === '') return '';
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '+')) {
            $phone = ltrim($phone, '+');
        }
        if (preg_match('/^0\d{9}$/', $phone)) {
            return '233' . substr($phone, 1);
        }
        if (preg_match('/^2330\d{9}$/', $phone)) {
            return '233' . substr($phone, 4);
        }
        if (preg_match('/^233\d{9}$/', $phone)) {
            return $phone;
        }
        if (preg_match('/^\d{9}$/', $phone)) {
            return '233' . $phone;
        }
        return '';
    }

    private function normalizePhoneE164($phone) {
        $phone = $this->extractFirstPhoneCandidate($phone);
        if ($phone === '') return '';
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '+')) {
            return $phone;
        }
        if (preg_match('/^0\d{9}$/', $phone)) {
            return '+233' . substr($phone, 1);
        }
        if (preg_match('/^2330\d{9}$/', $phone)) {
            return '+233' . substr($phone, 4);
        }
        if (str_starts_with($phone, '233')) {
            return '+' . $phone;
        }
        if (preg_match('/^\d{8,15}$/', $phone)) {
            return '+' . $phone;
        }
        return '';
    }

    private function extractFirstPhoneCandidate($phone): string {
        $raw = trim((string)$phone);
        if ($raw === '') return '';

        // Remove all non-numeric characters except +
        $clean = preg_replace('/[^\d+]/', '', $raw);

        // Try to match standard formats in the cleaned string
        if (preg_match('/(\+233|233|0)\d{9}/', $clean, $m)) {
            return (string)($m[0] ?? '');
        }
        
        // If it's just 9 digits, it's likely a Ghana number without the leading 0
        if (preg_match('/\b\d{9}\b/', $clean, $m)) {
            return '233' . (string)($m[0] ?? '');
        }

        return $clean;
    }

    private function getCandidateApiKeys($key) {
        $key = trim((string)$key);
        if ($key === '') return [''];

        $keys = [$key];

        $decoded = base64_decode($key, true);
        if ($decoded === false) return $keys;

        $decoded = trim($decoded);
        if ($decoded === '') return $keys;
        if (preg_match('/[\\x00-\\x1F\\x7F]/', $decoded)) return $keys;

        if ($decoded !== $key) {
            $keys[] = $decoded;
        }
        return $keys;
    }

    private function normalizeInfobipBaseUrl($baseUrl) {
        $baseUrl = trim((string)$baseUrl);
        if ($baseUrl === '') {
            return 'https://api.infobip.com';
        }
        if (!preg_match('#^https?://#i', $baseUrl)) {
            $baseUrl = 'https://' . $baseUrl;
        }
        return rtrim($baseUrl, '/');
    }

    private function formatBalanceResult($provider, $amount, $currency = '', $raw = null) {
        $currency = strtoupper(trim((string)$currency));
        $numericAmount = is_numeric($amount) ? (float)$amount : null;
        $displayAmount = $numericAmount !== null
            ? number_format($numericAmount, 2)
            : trim((string)$amount);

        return [
            'ok' => true,
            'provider' => $provider,
            'amount' => $numericAmount !== null ? $numericAmount : $amount,
            'currency' => $currency,
            'display' => trim(($currency !== '' ? $currency . ' ' : '') . $displayAmount),
            'raw' => $raw
        ];
    }

    private function getTwilioBalance() {
        $sid = trim((string)$this->twilioAccountSid);
        $token = trim((string)$this->twilioAuthToken);

        if ($sid === '' || $token === '') {
            return ['ok' => false, 'provider' => 'twilio', 'error' => 'Twilio credentials not configured'];
        }
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'provider' => 'twilio', 'error' => 'cURL not available on server'];
        }

        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Balance.json';

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $token);

            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false) {
                return ['ok' => false, 'provider' => 'twilio', 'error' => $err ?: 'Twilio request failed'];
            }

            $decoded = json_decode((string)$body, true);
            if ($httpCode >= 200 && $httpCode < 300 && is_array($decoded) && isset($decoded['balance'])) {
                return $this->formatBalanceResult('twilio', $decoded['balance'], $decoded['currency'] ?? '', $decoded);
            }

            $msg = is_array($decoded) ? ($decoded['message'] ?? $decoded['error_message'] ?? null) : null;
            $msg = $msg ?: ('Twilio balance request failed (HTTP ' . $httpCode . ')');
            return ['ok' => false, 'provider' => 'twilio', 'error' => $msg];
        } catch (Exception $e) {
            return ['ok' => false, 'provider' => 'twilio', 'error' => 'Twilio balance request failed'];
        }
    }

    private function getInfobipBalance() {
        $apiKey = trim((string)$this->apiKey);
        $baseUrl = $this->normalizeInfobipBaseUrl($this->infobipBaseUrl);

        if ($apiKey === '') {
            return ['ok' => false, 'provider' => 'infobip', 'error' => 'Infobip API key not configured'];
        }
        if ($baseUrl === '') {
            return ['ok' => false, 'provider' => 'infobip', 'error' => 'Infobip base URL not configured'];
        }
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'provider' => 'infobip', 'error' => 'cURL not available on server'];
        }

        $url = $baseUrl . '/account/1/balance';

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: App ' . $apiKey,
                'Accept: application/json'
            ]);

            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false) {
                return ['ok' => false, 'provider' => 'infobip', 'error' => $err ?: 'Infobip request failed'];
            }

            $decoded = json_decode((string)$body, true);
            $balanceData = is_array($decoded) ? ($decoded['balance'] ?? null) : null;
            if ($httpCode >= 200 && $httpCode < 300 && is_array($decoded)) {
                $balanceAmount = null;
                $currency = '';

                if (is_array($balanceData) && array_key_exists('balance', $balanceData)) {
                    $balanceAmount = $balanceData['balance'];
                    $currency = (string)($balanceData['currencyCode'] ?? $balanceData['currency'] ?? '');
                } elseif (array_key_exists('balance', $decoded) && (is_numeric($decoded['balance']) || is_string($decoded['balance']))) {
                    $balanceAmount = $decoded['balance'];
                    $currency = (string)($decoded['currency'] ?? $decoded['currencyCode'] ?? '');
                }

                if ($balanceAmount !== null) {
                    return $this->formatBalanceResult('infobip', $balanceAmount, $currency, $decoded);
                }
            }

            $msg = null;
            if (is_array($decoded)) {
                if (!empty($decoded['requestError']['serviceException']['text'])) {
                    $msg = $decoded['requestError']['serviceException']['text'];
                } elseif (!empty($decoded['requestError']['policyException']['text'])) {
                    $msg = $decoded['requestError']['policyException']['text'];
                } elseif (!empty($decoded['message'])) {
                    $msg = $decoded['message'];
                }
            }
            $msg = $msg ?: ('Infobip balance request failed (HTTP ' . $httpCode . ')');
            return ['ok' => false, 'provider' => 'infobip', 'error' => $msg];
        } catch (Exception $e) {
            return ['ok' => false, 'provider' => 'infobip', 'error' => 'Infobip balance request failed'];
        }
    }

    private function getMnotifyBalance($apiKey) {
        $apiKey = trim((string)$apiKey);
        if ($apiKey === '') {
            return ['ok' => false, 'provider' => 'mnotify', 'error' => 'MNotify API key not configured'];
        }
        if (!function_exists('curl_init')) {
            return ['ok' => false, 'provider' => 'mnotify', 'error' => 'cURL not available on server'];
        }

        $modern = $this->getMnotifyModernBalance($apiKey);
        if (($modern['ok'] ?? false) === true) {
            return $modern;
        }

        if (!$this->shouldFallbackToLegacyMnotify($modern)) {
            return $modern;
        }

        $legacy = $this->getMnotifyLegacyBalance($apiKey);
        if (($legacy['ok'] ?? false) === true) {
            return $legacy;
        }

        return $modern['error'] ?? '' ? $modern : $legacy;
    }

    private function getMnotifyModernBalance($apiKey) {
        $url = 'https://api.mnotify.com/api/balance/sms?key=' . rawurlencode($apiKey);

        try {
            $attempt = function (bool $verifyPeer) use ($url, $apiKey) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 25);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer ? true : false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: ' . $apiKey,
                    'Accept: application/json',
                ]);
                $body = curl_exec($ch);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err = curl_error($ch);
                curl_close($ch);
                return [$body, $httpCode, $err];
            };

            [$body, $httpCode, $err] = $attempt(true);
            if ($body === false && $err && stripos($err, 'ssl') !== false) {
                [$body, $httpCode, $err] = $attempt(false);
            }

            if ($body === false) {
                return ['ok' => false, 'provider' => 'mnotify', 'error' => $err ?: 'MNotify balance request failed', 'http' => $httpCode];
            }

            $decoded = json_decode((string)$body, true);
            if ($httpCode >= 200 && $httpCode < 300 && is_array($decoded)) {
                $status = strtolower((string)($decoded['status'] ?? ''));
                $amount = $decoded['data']['balance'] ?? $decoded['balance'] ?? null;
                $currency = $decoded['data']['currency'] ?? $decoded['currency'] ?? '';
                if (($status === 'success' || array_key_exists('data', $decoded) || array_key_exists('balance', $decoded)) && $amount !== null) {
                    return $this->formatBalanceResult('mnotify', $amount, $currency, $decoded);
                }
            }

            $messageText = is_array($decoded) ? ($decoded['message'] ?? $decoded['error'] ?? null) : null;
            $messageText = trim((string)($messageText ?: ('MNotify balance request failed (HTTP ' . $httpCode . ')')));
            return ['ok' => false, 'provider' => 'mnotify', 'error' => $messageText, 'http' => $httpCode];
        } catch (Exception $e) {
            return ['ok' => false, 'provider' => 'mnotify', 'error' => 'MNotify balance request failed'];
        }
    }

    private function getMnotifyLegacyBalance($apiKey) {
        $url = 'https://apps.mnotify.net/smsapi/balance?key=' . rawurlencode($apiKey);

        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);

            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false) {
                return ['ok' => false, 'provider' => 'mnotify', 'error' => $err ?: 'MNotify balance request failed', 'http' => $httpCode];
            }

            $body = trim((string)$body);
            if ($httpCode >= 200 && $httpCode < 300 && is_numeric($body)) {
                return $this->formatBalanceResult('mnotify', $body, 'GHS', $body);
            }

            return ['ok' => false, 'provider' => 'mnotify', 'error' => $body !== '' ? $body : 'MNotify balance request failed', 'http' => $httpCode];
        } catch (Exception $e) {
            return ['ok' => false, 'provider' => 'mnotify', 'error' => 'MNotify balance request failed'];
        }
    }

    private function sendViaNalo($apiKey, $senderId, $to, $message) {
        $url = str_replace('{prefix}', rawurlencode($this->naloPrefix), $this->baseUrl);
        $payload = [
            'type' => '0',
            'dlr' => '1',
            'source' => $senderId,
            'destination' => $to,
            'message' => $message,
            'key' => $apiKey
        ];

        $query = http_build_query($payload);
        $fullUrl = rtrim($url, '/') . '/?' . $query;

        try {
            $body = null;
            $httpCode = null;

            if (function_exists('curl_init')) {
                $attempt = function (bool $verifyPeer) use ($fullUrl, &$httpCode) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $fullUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 25);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer ? true : false);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                    $body = curl_exec($ch);
                    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    $err = curl_error($ch);
                    curl_close($ch);
                    return ['body' => $body, 'err' => $err, 'http' => $httpCode];
                };

                $first = $attempt(true);
                $body = $first['body'];
                $httpCode = $first['http'];
                $err = $first['err'];

                if ($body === false && $err) {
                    $low = strtolower($err);
                    if (str_contains($low, 'ssl') || str_contains($low, 'certificate')) {
                        $retry = $attempt(false);
                        $body = $retry['body'];
                        $httpCode = $retry['http'];
                        $err = $retry['err'];
                    }
                }

                if ($body === false || $httpCode >= 400) {
                    $msg = $err ?: ('Gateway error' . ($httpCode ? " (HTTP $httpCode)" : ''));
                    return ['ok' => false, 'error' => $msg];
                }
            } else {
                $ctx = stream_context_create([
                    'http' => [
                        'method' => 'GET',
                        'timeout' => 25
                    ]
                ]);
                $body = @file_get_contents($fullUrl, false, $ctx);
                if ($body === false) {
                    return ['ok' => false, 'error' => 'Gateway error'];
                }
            }

            $body = trim((string)$body);
            if ($body === '') {
                return ['ok' => false, 'error' => 'Empty gateway response'];
            }

            if (preg_match('/^\\d+\\|/', $body)) {
                $parts = explode('|', $body);
                $code = (int)($parts[0] ?? 0);
                if ($code === 1701) {
                    return ['ok' => true, 'raw' => $body];
                }
                return ['ok' => false, 'error' => 'Gateway code: ' . $code];
            }

            $lower = strtolower($body);
            if (str_contains($lower, 'success') || str_contains($lower, 'queued') || str_contains($lower, 'sent')) {
                return ['ok' => true, 'raw' => $body];
            }

            return ['ok' => false, 'error' => 'Gateway response: ' . substr($body, 0, 120)];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'Gateway error'];
        }
    }

    private function sendViaTwilio($to, $message) {
        $sid = trim((string)$this->twilioAccountSid);
        $token = trim((string)$this->twilioAuthToken);
        $from = trim((string)$this->twilioFrom);

        if ($sid === '' || $token === '') {
            return ['ok' => false, 'error' => 'Twilio credentials not configured'];
        }
        if ($from === '') {
            return ['ok' => false, 'error' => 'Twilio From number not configured'];
        }

        $url = 'https://api.twilio.com/2010-04-01/Accounts/' . rawurlencode($sid) . '/Messages.json';

        $post = http_build_query([
            'To' => $to,
            'From' => $from,
            'Body' => $message
        ]);

        try {
            if (!function_exists('curl_init')) {
                return ['ok' => false, 'error' => 'cURL not available on server'];
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, $sid . ':' . $token);

            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false) {
                return ['ok' => false, 'error' => $err ?: 'Twilio request failed'];
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                return ['ok' => true, 'raw' => $body];
            }

            $decoded = json_decode((string)$body, true);
            $msg = null;
            if (is_array($decoded)) {
                $msg = $decoded['message'] ?? $decoded['error_message'] ?? null;
            }
            $msg = $msg ?: ('Twilio error (HTTP ' . $httpCode . ')');
            return ['ok' => false, 'error' => $msg];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'Twilio error'];
        }
    }

    private function sendViaInfobip($to, $message) {
        $apiKey = trim((string)$this->apiKey);
        $baseUrl = $this->normalizeInfobipBaseUrl($this->infobipBaseUrl);
        $sender = trim((string)$this->senderId);

        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'Infobip API key not configured'];
        }
        if ($baseUrl === '') {
            return ['ok' => false, 'error' => 'Infobip base URL not configured'];
        }
        if ($sender === '') {
            $sender = 'ServiceSMS';
        }

        $url = $baseUrl . '/sms/3/messages';
        $payload = [
            'messages' => [
                [
                    'sender' => $sender,
                    'destinations' => [
                        ['to' => $to]
                    ],
                    'content' => [
                        'text' => $message
                    ]
                ]
            ]
        ];

        try {
            if (!function_exists('curl_init')) {
                return ['ok' => false, 'error' => 'cURL not available on server'];
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: App ' . $apiKey,
                'Content-Type: application/json',
                'Accept: application/json'
            ]);

            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false) {
                return ['ok' => false, 'error' => $err ?: 'Infobip request failed'];
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                return ['ok' => true, 'raw' => $body];
            }

            $decoded = json_decode((string)$body, true);
            $msg = null;
            if (is_array($decoded)) {
                if (!empty($decoded['requestError']['serviceException']['text'])) {
                    $msg = $decoded['requestError']['serviceException']['text'];
                } elseif (!empty($decoded['requestError']['policyException']['text'])) {
                    $msg = $decoded['requestError']['policyException']['text'];
                } elseif (!empty($decoded['message'])) {
                    $msg = $decoded['message'];
                }
            }
            $msg = $msg ?: ('Infobip error (HTTP ' . $httpCode . ')');
            return ['ok' => false, 'error' => $msg];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'Infobip error'];
        }
    }

    private function sendViaMnotify($apiKey, $senderId, $to, $message) {
        $apiKey = trim((string)$apiKey);
        $senderId = trim((string)$senderId);

        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'MNotify API key not configured'];
        }

        if ($senderId === '') {
            return ['ok' => false, 'error' => 'MNotify sender ID not configured'];
        }

        $modern = $this->sendViaMnotifyModern($apiKey, $senderId, $to, $message);
        if (($modern['ok'] ?? false) === true) {
            return $modern;
        }

        if (!$this->shouldFallbackToLegacyMnotify($modern)) {
            return $modern;
        }

        $legacy = $this->sendViaMnotifyLegacy($apiKey, $senderId, $to, $message);
        if (($legacy['ok'] ?? false) === true) {
            return $legacy;
        }

        return $modern['error'] ?? '' ? $modern : $legacy;
    }

    private function sendBulkViaMnotify(array $recipients, string $message, array $apiKeys): array
    {
        if (empty($recipients)) {
            return ['sent' => 0, 'failed' => 0, 'failures' => [], 'last_error' => null];
        }

        $apiKeys = array_values(array_filter(array_map('trim', $apiKeys)));
        $activeApiKey = $apiKeys[0] ?? '';

        if ($activeApiKey === '') {
            return ['sent' => 0, 'failed' => count($recipients), 'failures' => [], 'last_error' => 'MNotify API key not configured'];
        }

        $senderId = trim((string)$this->senderId);
        if ($senderId === '') {
            return ['sent' => 0, 'failed' => count($recipients), 'failures' => [], 'last_error' => 'MNotify sender ID not configured'];
        }

        $batchSize = 200;
        $sent = 0;
        $failed = 0;
        $failures = [];
        $lastError = null;

        foreach (array_chunk($recipients, $batchSize) as $batch) {
            $resp = $this->sendViaMnotifyBatch($activeApiKey, $senderId, $batch, $message);

            if (($resp['ok'] ?? false) !== true && count($apiKeys) > 1 && $sent === 0 && $failed === 0) {
                foreach ($apiKeys as $candidate) {
                    if ($candidate === $activeApiKey) continue;
                    $try = $this->sendViaMnotifyBatch($candidate, $senderId, $batch, $message);
                    if (($try['ok'] ?? false) === true) {
                        $activeApiKey = $candidate;
                        $resp = $try;
                        break;
                    }
                    $lastError = $try['error'] ?? $lastError;
                }
            }

            if (($resp['ok'] ?? false) === true) {
                $sent += count($batch);
            } else {
                $failed += count($batch);
                $lastError = $resp['error'] ?? $lastError;
                $sample = array_slice($batch, 0, max(1, min(10, count($batch))));
                foreach ($sample as $phone) {
                    $failures[] = [
                        'to' => $phone,
                        'error' => $resp['error'] ?? 'Failed'
                    ];
                }
            }
        }

        return [
            'sent' => $sent,
            'failed' => $failed,
            'failures' => $failures,
            'last_error' => $lastError
        ];
    }

    private function sendViaMnotifyBatch(string $apiKey, string $senderId, array $recipients, string $message): array
    {
        $apiKey = trim((string)$apiKey);
        $senderId = trim((string)$senderId);
        $recipients = array_values(array_filter(array_map('trim', $recipients)));

        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'MNotify API key not configured'];
        }
        if ($senderId === '') {
            return ['ok' => false, 'error' => 'MNotify sender ID not configured'];
        }
        if (empty($recipients)) {
            return ['ok' => false, 'error' => 'No valid recipients'];
        }

        $modern = $this->sendViaMnotifyModernBatch($apiKey, $senderId, $recipients, $message);
        if (($modern['ok'] ?? false) === true) {
            return $modern;
        }

        if (!$this->shouldFallbackToLegacyMnotify($modern)) {
            return $modern;
        }

        $legacy = $this->sendViaMnotifyLegacyBatch($apiKey, $senderId, $recipients, $message);
        if (($legacy['ok'] ?? false) === true) {
            return $legacy;
        }

        return $modern['error'] ?? '' ? $modern : $legacy;
    }

    private function sendViaMnotifyModernBatch(string $apiKey, string $senderId, array $recipients, string $message): array
    {
        $url = 'https://api.mnotify.com/api/sms/quick?key=' . rawurlencode($apiKey);
        $payload = [
            'recipient' => array_values($recipients),
            'sender' => $senderId,
            'message' => $message,
            'is_schedule' => false,
            'schedule_date' => '',
        ];

        try {
            if (!function_exists('curl_init')) {
                return ['ok' => false, 'error' => 'cURL not available on server'];
            }

            $attempt = function (bool $verifyPeer) use ($url, $payload, $apiKey) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 40);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer ? true : false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ]);
                $body = curl_exec($ch);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err = curl_error($ch);
                curl_close($ch);
                return [$body, $httpCode, $err];
            };

            [$body, $httpCode, $err] = $attempt(true);
            if ($body === false && $err && stripos($err, 'ssl') !== false) {
                [$body, $httpCode, $err] = $attempt(false);
            }

            if ($body === false) {
                // #region debug-point sms-4
                $this->dbgEvent('H4', 'pre-transport', [
                    'provider' => 'mnotify',
                    'endpoint' => $this->redactUrlSecrets($url),
                    'http' => (int)$httpCode,
                    'curl_error' => (string)$err
                ]);
                // #endregion debug-point sms-4
                return ['ok' => false, 'error' => $err ?: 'MNotify request failed', 'http' => $httpCode];
            }

            $body = trim((string)$body);
            if ($body === '') {
                return ['ok' => false, 'error' => 'Empty gateway response', 'http' => $httpCode];
            }

            $decoded = json_decode($body, true);
            if ($httpCode >= 200 && $httpCode < 300 && is_array($decoded)) {
                $status = strtolower((string)($decoded['status'] ?? ''));
                $code = (string)($decoded['code'] ?? '');
                if ($status === 'success' || in_array($code, ['1000', '200', '201'], true)) {
                    // #region debug-point sms-5
                    $this->dbgEvent('H1', 'pre-provider', [
                        'provider' => 'mnotify',
                        'endpoint' => $this->redactUrlSecrets($url),
                        'http' => (int)$httpCode,
                        'status' => $status,
                        'code' => $code,
                        'raw' => $this->dbgTrunc($body, 800)
                    ]);
                    // #endregion debug-point sms-5
                    return ['ok' => true, 'raw' => $body];
                }
            }

            $messageText = null;
            if (is_array($decoded)) {
                $messageText = $decoded['message'] ?? $decoded['error'] ?? null;
            }
            $messageText = trim((string)($messageText ?: ('MNotify error' . ($httpCode ? ' (HTTP ' . $httpCode . ')' : ''))));

            // #region debug-point sms-6
            $this->dbgEvent('H1', 'pre-provider', [
                'provider' => 'mnotify',
                'endpoint' => $this->redactUrlSecrets($url),
                'http' => (int)$httpCode,
                'error' => $messageText,
                'raw' => $this->dbgTrunc($body, 800)
            ]);
            // #endregion debug-point sms-6

            return ['ok' => false, 'error' => $messageText, 'http' => $httpCode, 'raw' => $body];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'MNotify error'];
        }
    }

    private function sendViaMnotifyLegacyBatch(string $apiKey, string $senderId, array $recipients, string $message): array
    {
        $to = implode(',', array_values($recipients));

        $url = 'https://apps.mnotify.net/smsapi?' . http_build_query([
            'key' => $apiKey,
            'to' => $to,
            'msg' => $message,
            'sender_id' => $senderId,
        ]);

        try {
            if (!function_exists('curl_init')) {
                return ['ok' => false, 'error' => 'cURL not available on server'];
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 40);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false) {
                return ['ok' => false, 'error' => $err ?: 'MNotify request failed'];
            }

            if ($httpCode >= 400) {
                return ['ok' => false, 'error' => 'MNotify error (HTTP ' . $httpCode . ')'];
            }

            $body = trim((string)$body);
            if ($body === '') {
                return ['ok' => false, 'error' => 'Empty gateway response'];
            }

            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $status = (string)($decoded['status'] ?? $decoded['code'] ?? '');
                if (in_array($status, ['1000', 'success', 'ok'], true)) {
                    return ['ok' => true, 'raw' => $body];
                }

                $messageText = $decoded['message'] ?? $decoded['msg'] ?? null;
                if ($messageText) {
                    return ['ok' => false, 'error' => (string)$messageText];
                }
            }

            if ($body === '1000' || str_contains(strtolower($body), 'success')) {
                return ['ok' => true, 'raw' => $body];
            }

            $knownErrors = [
                '1002' => 'SMS sending failed',
                '1003' => 'Insufficient SMS credit balance',
                '1004' => 'Invalid API key',
                '1005' => 'Invalid recipient phone number',
                '1006' => 'Invalid sender ID',
                '1007' => 'Message scheduled for later delivery',
                '1008' => 'Empty message',
            ];

            if (isset($knownErrors[$body])) {
                return ['ok' => false, 'error' => $knownErrors[$body]];
            }

            return ['ok' => false, 'error' => 'Gateway response: ' . substr($body, 0, 120)];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'MNotify error'];
        }
    }

    private function sendViaMnotifyModern($apiKey, $senderId, $to, $message) {
        $url = 'https://api.mnotify.com/api/sms/quick?key=' . rawurlencode($apiKey);
        $payload = [
            'recipient' => [$to],
            'sender' => $senderId,
            'message' => $message,
            'is_schedule' => false,
            'schedule_date' => '',
        ];

        try {
            if (!function_exists('curl_init')) {
                return ['ok' => false, 'error' => 'cURL not available on server'];
            }

            $attempt = function (bool $verifyPeer) use ($url, $payload, $apiKey) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_TIMEOUT, 25);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifyPeer ? true : false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Authorization: ' . $apiKey,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ]);
                $body = curl_exec($ch);
                $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $err = curl_error($ch);
                curl_close($ch);
                return [$body, $httpCode, $err];
            };

            [$body, $httpCode, $err] = $attempt(true);
            if ($body === false && $err && stripos($err, 'ssl') !== false) {
                [$body, $httpCode, $err] = $attempt(false);
            }

            if ($body === false) {
                return ['ok' => false, 'error' => $err ?: 'MNotify request failed', 'http' => $httpCode];
            }

            $body = trim((string)$body);
            if ($body === '') {
                return ['ok' => false, 'error' => 'Empty gateway response', 'http' => $httpCode];
            }

            $decoded = json_decode($body, true);
            if ($httpCode >= 200 && $httpCode < 300 && is_array($decoded)) {
                $status = strtolower((string)($decoded['status'] ?? ''));
                $code = (string)($decoded['code'] ?? '');
                if ($status === 'success' || in_array($code, ['1000', '200', '201'], true)) {
                    return ['ok' => true, 'raw' => $body];
                }
            }

            $messageText = null;
            if (is_array($decoded)) {
                $messageText = $decoded['message'] ?? $decoded['error'] ?? null;
            }
            $messageText = trim((string)($messageText ?: ('MNotify error' . ($httpCode ? ' (HTTP ' . $httpCode . ')' : ''))));

            return ['ok' => false, 'error' => $messageText, 'http' => $httpCode, 'raw' => $body];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'MNotify error'];
        }
    }

    private function sendViaMnotifyLegacy($apiKey, $senderId, $to, $message) {

        $url = 'https://apps.mnotify.net/smsapi?' . http_build_query([
            'key' => $apiKey,
            'to' => $to,
            'msg' => $message,
            'sender_id' => $senderId,
        ]);

        try {
            if (!function_exists('curl_init')) {
                return ['ok' => false, 'error' => 'cURL not available on server'];
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_TIMEOUT, 25);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

            $body = curl_exec($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($body === false) {
                return ['ok' => false, 'error' => $err ?: 'MNotify request failed'];
            }

            if ($httpCode >= 400) {
                return ['ok' => false, 'error' => 'MNotify error (HTTP ' . $httpCode . ')'];
            }

            $body = trim((string)$body);
            if ($body === '') {
                return ['ok' => false, 'error' => 'Empty gateway response'];
            }

            $decoded = json_decode($body, true);
            if (is_array($decoded)) {
                $status = (string)($decoded['status'] ?? $decoded['code'] ?? '');
                if (in_array($status, ['1000', 'success', 'ok'], true)) {
                    return ['ok' => true, 'raw' => $body];
                }

                $messageText = $decoded['message'] ?? $decoded['msg'] ?? null;
                if ($messageText) {
                    return ['ok' => false, 'error' => (string)$messageText];
                }
            }

            if ($body === '1000' || str_contains(strtolower($body), 'success')) {
                return ['ok' => true, 'raw' => $body];
            }

            $knownErrors = [
                '1002' => 'SMS sending failed',
                '1003' => 'Insufficient SMS credit balance',
                '1004' => 'Invalid API key',
                '1005' => 'Invalid recipient phone number',
                '1006' => 'Invalid sender ID',
                '1007' => 'Message scheduled for later delivery',
                '1008' => 'Empty message',
            ];

            if (isset($knownErrors[$body])) {
                return ['ok' => false, 'error' => $knownErrors[$body]];
            }

            return ['ok' => false, 'error' => 'Gateway response: ' . substr($body, 0, 120)];
        } catch (Exception $e) {
            return ['ok' => false, 'error' => 'MNotify error'];
        }
    }

    // #region debug-point sms-debug
    private function dbgEvent(string $hypothesisId, string $runId, array $data): void {
        try {
            $root = defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__, 2);
            $dir = rtrim(str_replace('\\', '/', $root), '/') . '/.dbg';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $path = $dir . '/trae-debug-log-' . $this->debugSessionId . '.ndjson';
            $event = [
                'ts' => (int)round(microtime(true) * 1000),
                'sessionId' => $this->debugSessionId,
                'hypothesisId' => $hypothesisId,
                'runId' => $runId,
                'source' => 'php',
                'data' => $data
            ];
            @file_put_contents($path, json_encode($event, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND);
        } catch (Throwable $e) {
        }
    }

    private function dbgTrunc(string $text, int $maxLen): string {
        $t = trim($text);
        if ($t === '') return '';
        if (mb_strlen($t) <= $maxLen) return $t;
        return mb_substr($t, 0, $maxLen) . '...';
    }

    private function redactUrlSecrets(string $url): string {
        $url = trim($url);
        if ($url === '') return $url;
        $url = preg_replace('/([?&]key=)[^&]+/i', '$1***', $url);
        return (string)$url;
    }
    // #endregion debug-point sms-debug

    private function shouldFallbackToLegacyMnotify(array $response) {
        $http = (int)($response['http'] ?? 0);
        $error = strtolower(trim((string)($response['error'] ?? '')));

        if (in_array($http, [0, 401, 403, 404], true)) {
            return true;
        }

        foreach ([
            'invalid api key',
            'unauthorized',
            'forbidden',
            'not found',
            'invalid key',
            'authentication',
        ] as $needle) {
            if ($error !== '' && str_contains($error, $needle)) {
                return true;
            }
        }

        return false;
    }
}
