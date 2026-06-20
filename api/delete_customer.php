<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Customer.php';

requireMethod('POST');
requireAdminApi();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Valid ID Provide.');

$result = Customer::deleteCustomer($id);
if ($result === true) {
    jsonResponse(true, 'Customer has been deleted.');
} else {
    jsonResponse(false, Customer::errorMessage($result));
}
