<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Installment.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Provide a valid ID.');
$p = Installment::getPlanById($id);
if (!$p) jsonResponse(false, 'Plan not found.');
jsonResponse(true, '', ['data' => $p]);
