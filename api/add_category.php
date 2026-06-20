<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Category.php';

requireMethod('POST');
requireAdminApi();

$result = Category::add($_POST['name'] ?? '');

if (is_int($result)) {
    jsonResponse(true, 'Category has been added.', ['id' => $result]);
}
jsonResponse(false, Category::errorMessage($result));
