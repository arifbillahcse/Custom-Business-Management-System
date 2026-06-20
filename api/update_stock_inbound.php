<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Supplier.php';
require_once __DIR__ . '/../classes/Stock.php';

requireMethod('POST');

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Select a valid record.');

$result = Stock::updateStockInbound($id, [
    'quantity'     => $_POST['quantity']     ?? null,
    'buy_price'    => $_POST['buy_price']    ?? null,
    'supplier_id'  => $_POST['supplier_id']  ?? null,
    'inbound_date' => $_POST['inbound_date'] ?? null,
    'note'         => $_POST['note']         ?? null,
    'branch_id'    => $_POST['branch_id']    ?? null,
]);

if ($result === true) {
    jsonResponse(true, 'Stock updated.');
}
jsonResponse(false, Stock::errorMessage($result));
