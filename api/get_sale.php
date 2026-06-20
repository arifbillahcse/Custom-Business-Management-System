<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Sale.php';

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Valid ID Provide.');

$sale = Sale::getSaleById($id);
if (!$sale) jsonResponse(false, 'Sales record not found.');

jsonResponse(true, '', $sale);
