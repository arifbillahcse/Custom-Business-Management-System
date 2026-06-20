<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Quotation.php';
require_once __DIR__ . '/../classes/Setting.php';
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Provide a valid ID.');
$q = Quotation::getById($id);
if (!$q) jsonResponse(false, 'Quotation not found.');
jsonResponse(true, '', [
    'data'         => $q,
    'shop_name'    => Setting::get('shop_name',    APP_NAME),
    'shop_address' => Setting::get('shop_address', ''),
    'shop_phone'   => Setting::get('shop_phone',   ''),
]);
