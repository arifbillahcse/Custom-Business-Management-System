<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Quotation.php';
requirePostMethod();
requireAdminApi();

$id    = (int)($_POST['id'] ?? 0);
$items = json_decode($_POST['items'] ?? '[]', true);
if ($id <= 0)       jsonResponse(false, 'Provide a valid ID.');
if (!is_array($items)) jsonResponse(false, 'The item information is invalid.');

$result = Quotation::update($id, $_POST, $items);
if ($result === true) {
    jsonResponse(true, 'Quotation updated.');
}
$msg = match($result) {
    'NOT_FOUND'    => 'Quotation not found.',
    'NOT_ACTIVE'   => 'Only active quotations can be edited.',
    'NO_ITEMS'     => 'Add at least one product.',
    'INVALID_ITEM' => 'The product information is invalid.',
    default        => 'Something went wrong.',
};
jsonResponse(false, $msg);
