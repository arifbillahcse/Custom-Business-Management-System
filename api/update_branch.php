<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Branch.php';

requireMethod('POST');
requireAdminApi();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Provide a branch ID.');

$result = Branch::updateBranch(
    $id,
    $_POST['name']    ?? '',
    $_POST['address'] ?? '',
    $_POST['phone']   ?? ''
);

if ($result === true) {
    jsonResponse(true, 'Branch updated.');
}
jsonResponse(false, Branch::errorMessage($result));
