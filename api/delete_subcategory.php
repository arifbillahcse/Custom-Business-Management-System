<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Category.php';

requireMethod('POST');
requireStrictAdminApi(); // central product list: owner/admin only

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Select a valid sub-category.');

Category::deleteSubCategory($id);
jsonResponse(true, 'Sub-category has been deleted.');
