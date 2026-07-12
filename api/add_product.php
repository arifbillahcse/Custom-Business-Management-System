<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_upload.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Category.php';

requireMethod('POST');
requireStrictAdminApi(); // central product list: owner/admin only

// Handle optional image upload first
$imagePath = saveProductImage('image');
if (is_string($imagePath) && str_starts_with($imagePath, 'IMG_')) {
    jsonResponse(false, Product::errorMessage($imagePath));
}

$result = Product::addProduct(
    (int)($_POST['category_id'] ?? 0),
    $_POST['name']       ?? '',
    $_POST['size_brand'] ?? '',
    $_POST['unit']       ?? 'pcs',
    (float)($_POST['buy_price']  ?? 0),
    (float)($_POST['sell_price'] ?? 0),
    (float)($_POST['min_stock']  ?? 0),
    (int)($_POST['sub_category_id'] ?? 0) ?: null,
    (float)($_POST['wholesale_price'] ?? 0),
    $_POST['product_code'] ?? '',
    $imagePath
);

if (is_int($result)) {
    jsonResponse(true, 'Product added successfully.', ['id' => $result]);
}

// Validation failed after the file was stored — don't leave orphans behind
deleteProductImage($imagePath);
jsonResponse(false, Product::errorMessage($result));
