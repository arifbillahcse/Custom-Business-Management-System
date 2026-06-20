<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../classes/User.php';

if (!isLoggedIn() || !User::isAdmin()) {
    jsonResponse(false, 'Forbidden');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method');
}

$file = $_FILES['sql_file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(false, 'There was a problem uploading the file');
}

// Validate extension
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if ($ext !== 'sql') {
    jsonResponse(false, 'Only .sql files accepted');
}

// Max 50 MB
if ($file['size'] > 50 * 1024 * 1024) {
    jsonResponse(false, 'Maximum file size 50 MB may be');
}

$sql = file_get_contents($file['tmp_name']);
if ($sql === false || trim($sql) === '') {
    jsonResponse(false, 'The file cannot be read or is empty');
}

// Split into individual statements (handles semicolons inside strings naively but works for mysqldump-style output)
$statements = array_filter(
    array_map('trim', preg_split('/;\s*\n/', $sql)),
    fn($s) => $s !== '' && !str_starts_with($s, '--')
);

$pdo = Database::getInstance();

try {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
    $pdo->exec('SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"');

    $count = 0;
    foreach ($statements as $stmt) {
        if (trim($stmt) === '') continue;
        $pdo->exec($stmt);
        $count++;
    }

    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');

    jsonResponse(true, "Import successful. Total $count statements were executed.");
} catch (\Throwable $e) {
    $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
    error_log('DB import error: ' . $e->getMessage());
    jsonResponse(false, 'Import failed: ' . $e->getMessage());
}
