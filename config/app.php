<?php

define('APP_NAME', 'Custom-Business-Management-System');
define('APP_VERSION', '2.2.1');
// Auto-detect base URL; override with env var BASE_URL if set
if (!defined('BASE_URL')) {
    $detectedUrl = (isset($_SERVER['BASE_URL']))
        ? $_SERVER['BASE_URL']
        : (function () {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            // Auto-detect subdirectory: derive from SCRIPT_NAME (e.g. /Custom-Business-Management-System/index.php)
            $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            // Walk up until we find the app root (the folder containing index.php at the top level)
            // ROOT_PATH is not defined yet, so use __DIR__ (config/) -> parent is app root
            $appRoot = dirname(__DIR__);
            $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/');
            $subPath = '';
            if ($docRoot !== '' && str_starts_with($appRoot, $docRoot)) {
                $subPath = substr($appRoot, strlen($docRoot));
            }
            return rtrim($scheme . '://' . $host . $subPath, '/');
        })();
    define('BASE_URL', rtrim($detectedUrl, '/'));
}
define('ROOT_PATH', dirname(__DIR__));

// Session lifetime in seconds (2 hours)
define('SESSION_LIFETIME', 7200);

// Timezone
date_default_timezone_set('Asia/Dhaka');
