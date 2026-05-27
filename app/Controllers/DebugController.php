<?php
require_once __DIR__ . '/BaseController.php';

class DebugController extends BaseController {
    public function smsLogs() {
        if (!Auth::isAdmin() && !Auth::isStaff()) {
            Session::flash('error', 'Unauthorized access.');
            header('Location: ' . BASE_URL . '/dashboard');
            exit;
        }

        $last = max(1, min(500, (int)($_GET['last'] ?? 200)));
        $path = ROOT_PATH . '/.dbg/trae-debug-log-sms-not-delivered.ndjson';

        $items = [];
        if (file_exists($path)) {
            $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if (is_array($lines) && count($lines) > 0) {
                $slice = array_slice($lines, max(0, count($lines) - $last));
                foreach ($slice as $line) {
                    $decoded = json_decode((string)$line, true);
                    if (is_array($decoded)) {
                        $items[] = $decoded;
                    }
                }
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        echo json_encode([
            'ok' => true,
            'file' => $path,
            'count' => count($items),
            'items' => $items
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    public function clearSmsLogs() {
        $this->isAdmin();

        $path = ROOT_PATH . '/.dbg/trae-debug-log-sms-not-delivered.ndjson';
        if (file_exists($path)) {
            @file_put_contents($path, '');
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
