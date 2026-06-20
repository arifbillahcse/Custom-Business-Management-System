<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Customer.php';

requireMethod('POST');

$name    = trim($_POST['name']    ?? '');
$phone   = trim($_POST['phone']   ?? '');
$address = trim($_POST['address'] ?? '');

$result = Customer::addCustomer($name, $phone, $address);
if (is_int($result)) {
    jsonResponse(true, 'Customer has been added.', ['id' => $result]);
} else {
    jsonResponse(false, Customer::errorMessage($result));
}
