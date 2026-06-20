<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Product.php';

requireMethod('POST');
requireAdminApi();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    jsonResponse(false, 'Select a valid product.');
}

$result = Product::deleteProduct($id);

if ($result === true) {
    jsonResponse(true, 'Product deleted.');
}

jsonResponse(false, Product::errorMessage($result));
