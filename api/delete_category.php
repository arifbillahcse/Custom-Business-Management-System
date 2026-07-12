<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Category.php';

requireMethod('POST');
requireStrictAdminApi(); // central product list: owner/admin only

$id     = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Select a valid category.');

$result = Category::delete($id);
if ($result === true) {
    jsonResponse(true, 'Category has been deleted.');
}
jsonResponse(false, Category::errorMessage($result));
