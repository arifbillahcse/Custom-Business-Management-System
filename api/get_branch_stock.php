<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Stock.php';

requireMethod('GET');

$branchId = (int)($_GET['branch_id'] ?? 0);
if ($branchId <= 0) jsonResponse(false, 'Select a valid branch.');

$stock = Stock::getBranchStock($branchId);
jsonResponse(true, 'OK', ['stock' => $stock]);
