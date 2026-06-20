<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Stock.php';
requireMethod('POST');
requireAdminApi();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Select a valid record.');

$result = Stock::deleteAdjustment($id);
if ($result === true) {
    jsonResponse(true, 'Stock adjustment record deleted.');
}
jsonResponse(false, Stock::adjustmentErrorMessage($result));
