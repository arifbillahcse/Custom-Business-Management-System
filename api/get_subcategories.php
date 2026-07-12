<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Category.php';

$categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== ''
    ? (int)$_GET['category_id']
    : null;

jsonResponse(true, 'OK', ['data' => Category::getSubCategories($categoryId)]);
