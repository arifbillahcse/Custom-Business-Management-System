<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Branch.php';

requireMethod('POST');
requireAdminApi();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Provide a branch ID.');

$result = Branch::deleteBranch($id);

if ($result === true) {
    jsonResponse(true, 'Branch has been deleted.');
}
jsonResponse(false, Branch::errorMessage($result));
