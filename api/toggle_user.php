<?php
require_once __DIR__ . '/_guard.php';

requireMethod('POST');
requireStrictAdminApi();

$id     = (int)($_POST['id'] ?? 0);
$active = (int)($_POST['active'] ?? 0) === 1;

if ($id <= 0) jsonResponse(false, 'Valid ID Provide.');

$result = User::setStatus($id, $active);
if ($result === true) {
    jsonResponse(true, $active ? 'Account has been activated.' : 'Account has been deactivated.');
} else {
    jsonResponse(false, User::errorMessage($result));
}
