<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Installment.php';
requireMethod('POST');
requireAdminApi();

$result = Installment::createPlan($_POST);
if (is_int($result)) jsonResponse(true, 'Installment plan created.', ['id' => $result]);

$msgs = ['NAME_REQUIRED' => 'Enter the customer name.',
         'INVALID_AMOUNT' => 'The total amount is invalid.',
         'INVALID_COUNT'  => 'The number of installments is invalid.',
         'DB_ERROR' => 'Database problem.'];
jsonResponse(false, $msgs[$result] ?? 'Something went wrong.');
