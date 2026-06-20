<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Supplier.php';

requireMethod('POST');
requireAdminApi();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Select a valid supplier.');

$result = Supplier::deleteSupplier($id);
if ($result === true) {
    jsonResponse(true, 'Supplier has been deleted.');
}
jsonResponse(false, Supplier::errorMessage($result));
