<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Category.php';

requireMethod('POST');
requireStrictAdminApi(); // central product list: owner/admin only

$categoryId = (int)($_POST['category_id'] ?? 0);
if ($categoryId <= 0) jsonResponse(false, 'Select a valid category.');

$result = Category::addSubCategory($categoryId, $_POST['name'] ?? '');

if (is_int($result)) {
    jsonResponse(true, 'Sub-category has been added.', ['id' => $result]);
}
jsonResponse(false, [
    'NAME_REQUIRED' => 'Enter the sub-category name.',
    'DUPLICATE'     => 'This sub-category already exists in the category.',
    'NOT_FOUND'     => 'Category not found.',
][$result] ?? 'Something went wrong.');
