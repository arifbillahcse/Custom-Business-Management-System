<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Installment.php';
requireMethod('POST');
requireAdminApi();

$id     = (int)($_POST['id']     ?? 0);
$amount = (float)($_POST['amount'] ?? 0);
$note   = trim($_POST['note']    ?? '');

if ($id <= 0) jsonResponse(false, 'Select a valid installment.');

$result = Installment::payInstallment($id, $amount, $note);
if ($result === true) jsonResponse(true, 'Installment payment completed.');

$msgs = ['NOT_FOUND' => 'Installment not found.',
         'ALREADY_PAID' => 'This installment has already been paid.',
         'INVALID_AMOUNT' => 'The quantity is invalid.'];
jsonResponse(false, $msgs[$result] ?? 'Something went wrong.');
