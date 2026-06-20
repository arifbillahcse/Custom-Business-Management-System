<?php
require_once __DIR__ . '/_guard.php';

requireMethod('POST');
requireStrictAdminApi();

$name     = trim($_POST['name']     ?? '');
$username = trim($_POST['username'] ?? '');
$password = (string)($_POST['password'] ?? '');
$role     = trim($_POST['role']     ?? 'staff');
$branchId = isset($_POST['branch_id']) && $_POST['branch_id'] !== '' ? (int)$_POST['branch_id'] : null;

if ($name === '')              jsonResponse(false, 'Enter a name.');
if ($username === '')          jsonResponse(false, 'Enter a username.');
if (strlen($password) < 4)     jsonResponse(false, 'Password must be at least 4 characters.');
if (!in_array($role, ['admin', 'manager', 'staff'], true)) jsonResponse(false, 'Select a valid role.');

$result = User::create($name, $username, $password, $role, $branchId);
if (is_int($result)) {
    jsonResponse(true, 'User has been added.', ['id' => $result]);
} else {
    jsonResponse(false, User::errorMessage($result));
}
