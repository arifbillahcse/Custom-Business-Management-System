<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Sale.php';
require_once __DIR__ . '/../classes/Stock.php';
requirePostMethod();
requireAdminApi();

$id    = (int)($_POST['id'] ?? 0);
$items = json_decode($_POST['items'] ?? '[]', true);
if ($id <= 0)          jsonResponse(false, 'Provide a valid ID.');
if (!is_array($items)) jsonResponse(false, 'The item information is invalid.');

$result = Sale::updateSale($id, $_POST, $items);
if ($result === true) {
    jsonResponse(true, 'Sale updated.');
}
jsonResponse(false, Sale::errorMessage(is_string($result) ? $result : 'DB_ERROR'));
