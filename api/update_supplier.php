<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Supplier.php';

requireMethod('POST');
requireAdminApi();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Select a valid supplier.');

$result = Supplier::updateSupplier($id, [
    'name'    => $_POST['name']    ?? null,
    'phone'   => $_POST['phone']   ?? null,
    'address' => $_POST['address'] ?? null,
]);

if ($result === true) {
    jsonResponse(true, 'Supplier has been updated.');
}
jsonResponse(false, Supplier::errorMessage($result));
