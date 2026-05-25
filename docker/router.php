<?php

$docRoot = dirname(__DIR__);
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
$requestPath = is_string($requestPath) ? $requestPath : '/';

$normalizedPath = '/' . ltrim($requestPath, '/');
$normalizedPath = str_replace(["\0", '\\'], '', $normalizedPath);

$blockedPrefixes = [
    '/app/',
    '/database/',
    '/installer/',
    '/supabase/',
    '/.dbg/',
];

foreach ($blockedPrefixes as $prefix) {
    if (str_starts_with($normalizedPath, $prefix)) {
        http_response_code(404);
        echo 'Not Found';
        return true;
    }
}

if ($normalizedPath === '/.env' || str_starts_with($normalizedPath, '/.env.')) {
    http_response_code(404);
    echo 'Not Found';
    return true;
}

$filePath = $docRoot . $normalizedPath;
if ($normalizedPath !== '/' && is_file($filePath)) {
    return false;
}

require $docRoot . '/index.php';
return true;

