<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Quotation.php';
requireMethod('POST');
requireAdminApi();

$data  = $_POST;
$items = json_decode($_POST['items'] ?? '[]', true);

$result = Quotation::create($data, is_array($items) ? $items : []);
if (is_int($result)) {
    jsonResponse(true, 'Quotation created.', ['id' => $result]);
}
$msgs = ['NO_ITEMS' => 'Add at least one product.',
         'INVALID_ITEM' => 'Enter valid product information.',
         'DB_ERROR' => 'Database problem.'];
jsonResponse(false, $msgs[$result] ?? 'Something went wrong.');
