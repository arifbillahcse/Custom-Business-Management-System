<?php
require_once __DIR__ . '/_guard.php';

requireMethod('POST');
requireStrictAdminApi();

$id       = (int)($_POST['id'] ?? 0);
$password = (string)($_POST['password'] ?? '');

if ($id <= 0) jsonResponse(false, 'Valid ID Provide.');

$result = User::resetPassword($id, $password);
if ($result === true) {
    jsonResponse(true, 'Password has been changed.');
} else {
    jsonResponse(false, User::errorMessage($result));
}
