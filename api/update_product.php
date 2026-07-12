<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/_upload.php';
require_once __DIR__ . '/../classes/Product.php';
require_once __DIR__ . '/../classes/Category.php';

requireMethod('POST');
requireStrictAdminApi(); // central product list: owner/admin only

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    jsonResponse(false, 'Select a valid product.');
}

$oldProduct = Product::getProductById($id);

// Optional new image — replaces the current one on success
$imagePath = saveProductImage('image');
if (is_string($imagePath) && str_starts_with($imagePath, 'IMG_')) {
    jsonResponse(false, Product::errorMessage($imagePath));
}

$data = [
    'category_id'     => $_POST['category_id']     ?? null,
    'sub_category_id' => $_POST['sub_category_id'] ?? 0,
    'product_code'    => $_POST['product_code']    ?? '',
    'name'            => $_POST['name']            ?? null,
    'size_brand'      => $_POST['size_brand']      ?? null,
    'unit'            => $_POST['unit']            ?? null,
    'buy_price'       => $_POST['buy_price']       ?? null,
    'sell_price'      => $_POST['sell_price']      ?? null,
    'wholesale_price' => $_POST['wholesale_price'] ?? null,
    'min_stock'       => $_POST['min_stock']       ?? null,
];
if ($imagePath !== null) {
    $data['image_path'] = $imagePath;
}

$result = Product::updateProduct($id, $data);

if ($result === true) {
    // New image saved — remove the replaced file
    if ($imagePath !== null && $oldProduct && !empty($oldProduct['image_path'])) {
        deleteProductImage($oldProduct['image_path']);
    }
    jsonResponse(true, 'Product updated successfully.');
}

// Update failed — clean up the newly uploaded file
deleteProductImage($imagePath);
jsonResponse(false, Product::errorMessage($result));
