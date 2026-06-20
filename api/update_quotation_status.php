<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Quotation.php';
requireMethod('POST');
requireAdminApi();

$id     = (int)($_POST['id']     ?? 0);
$status = trim($_POST['status']  ?? '');
if ($id <= 0) jsonResponse(false, 'Provide a valid ID.');

$result = Quotation::updateStatus($id, $status);
if ($result === true) {
    $msg = $status === 'converted' ? 'Converted to a sale.' : 'Quotation cancelled.';
    jsonResponse(true, $msg);
}
$msgs = ['NOT_FOUND' => 'not found.', 'NOT_ACTIVE' => 'Not active.', 'INVALID_STATUS' => 'Invalid status.'];
jsonResponse(false, $msgs[$result] ?? 'Something went wrong.');
