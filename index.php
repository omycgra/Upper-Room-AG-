<?php

/**
 * Church Management System - Entry Point
 */

// Define root path
define('ROOT_PATH', __DIR__);

#region debug-point A:index-start
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
        'hypothesisId' => 'A',
        'location' => 'index.php:1',
        'msg' => '[DEBUG] Request started',
        'data' => ['uri' => $_SERVER['REQUEST_URI'] ?? ''],
        'ts' => microtime(true) * 1000
    ]);
    $opts = ['http' => ['method' => 'POST', 'header' => 'Content-Type: application/json', 'content' => $data, 'timeout' => 0.5]];
    @file_get_contents($serverUrl, false, stream_context_create($opts));
})();
#endregion

require_once ROOT_PATH . '/app/Helpers/Env.php';
Env::load(ROOT_PATH . '/.env');

$appEnv = strtolower((string)Env::get('APP_ENV', 'development'));
$appDebug = Env::bool('APP_DEBUG', $appEnv !== 'production');

// Always show detailed errors on localhost (helps when APP_ENV is production in .env)
$remoteAddr = (string)($_SERVER['REMOTE_ADDR'] ?? '');
$serverName = (string)($_SERVER['SERVER_NAME'] ?? '');
if ($remoteAddr === '127.0.0.1' || $remoteAddr === '::1' || strtolower($serverName) === 'localhost') {
    $appDebug = true;
}

// Enable detailed errors only outside production
error_reporting(E_ALL);
ini_set('display_errors', $appDebug ? '1' : '0');

// Define Base URL for frontend assets and links
$baseUrlOverride = trim((string)Env::get('APP_BASE_URL', ''));
if ($baseUrlOverride !== '') {
    if (substr($baseUrlOverride, 0, 1) === '/') {
        $baseUrl = rtrim($baseUrlOverride, '/');
    } elseif (preg_match('#^https?://#i', $baseUrlOverride)) {
        $baseUrl = rtrim($baseUrlOverride, '/');
    } else {
        $baseUrl = rtrim('https://' . $baseUrlOverride, '/');
    }
} else {
    $scriptName = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $baseUrl = rtrim($scriptName, '/');
}
define('BASE_URL', $baseUrl);

// Load helpers
require_once ROOT_PATH . '/app/Helpers/Database.php';
require_once ROOT_PATH . '/app/Helpers/Session.php';
require_once ROOT_PATH . '/app/Helpers/SchemaState.php';
require_once ROOT_PATH . '/app/Helpers/Auth.php';
require_once ROOT_PATH . '/app/Helpers/AuditLog.php';
require_once ROOT_PATH . '/app/Helpers/AppConfig.php';
require_once ROOT_PATH . '/app/Helpers/BirthdayService.php';
require_once ROOT_PATH . '/app/Helpers/Branding.php';
require_once ROOT_PATH . '/app/Helpers/Router.php';
require_once ROOT_PATH . '/app/Helpers/View.php';

// Initialize session
Session::start();

// Handle routing via query parameter if mod_rewrite is not available
if (isset($_GET['route'])) {
    $_SERVER['REQUEST_URI'] = '/' . $_GET['route'];
}

BirthdayService::runDaily();

// Initialize and dispatch router
try {
    $router = new Router();
    $router->dispatch();
} catch (Throwable $e) {
    // Log the error
    error_log($e->getMessage());
    
    // Display a professional error page or message
    if ($appDebug) {
        echo "<div style='padding: 20px; background: #fee2e2; border: 1px solid #ef4444; border-radius: 8px; font-family: sans-serif;'>";
        echo "<h2 style='color: #991b1b; margin-top: 0;'>System Error</h2>";
        echo "<p style='color: #7f1d1d;'><strong>Message:</strong> " . $e->getMessage() . "</p>";
        echo "<p style='color: #7f1d1d; font-size: 0.8em;'><strong>File:</strong> " . $e->getFile() . " on line " . $e->getLine() . "</p>";
        echo "</div>";
    } else {
        die("A system error occurred. Please contact the administrator.");
    }
}
