<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Branch.php';

requireMethod('POST');
requireAdminApi();

$result = Branch::addBranch(
    $_POST['name']    ?? '',
    $_POST['address'] ?? '',
    $_POST['phone']   ?? ''
);

if (is_int($result)) {
    jsonResponse(true, 'Branch added.', ['id' => $result]);
}
jsonResponse(false, Branch::errorMessage($result));
