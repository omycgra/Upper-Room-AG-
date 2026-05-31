<?php

class View {
    public static function render($view, $data = [], $useLayout = true)
    {
        #region debug-point D:view-render
        $dbgStart = microtime(true);
        #endregion

        // Extract data to make variables available in the view
        extract($data);

        // Path to the view file
        $viewPath = __DIR__ . '/../Views/' . str_replace('.', '/', $view) . '.php';

        if (file_exists($viewPath)) {
            // Start output buffering
            ob_start();
            require_once $viewPath;
            $content = ob_get_clean();

            // Check if a layout should be used
            $layoutPath = __DIR__ . '/../Views/layouts/main.php';
            if ($useLayout && file_exists($layoutPath)) {
                require_once $layoutPath;
            } else {
                echo $content;
            }
        } else {
            die("View $view not found at $viewPath");
        }

        #region debug-point D:view-render-end
        $elapsedMs = (int)round((microtime(true) - $dbgStart) * 1000);
        (function($elapsedMs, $view) {
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
                'hypothesisId' => 'D',
                'location' => 'View.php:render',
                'msg' => '[DEBUG] View rendered',
                'data' => [
                    'ms' => $elapsedMs,
                    'view' => $view
                ],
                'ts' => microtime(true) * 1000
            ]);
            @file_get_contents($serverUrl, false, stream_context_create(['http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => $data, 'timeout' => 0.5]]));
        })($elapsedMs, $view);
        #endregion
    }
}
