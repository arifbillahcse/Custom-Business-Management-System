<?php
require_once __DIR__ . '/_guard.php';
require_once __DIR__ . '/../classes/Customer.php';

requireMethod('POST');
requireAdminApi();   // admin + manager only

$noteId = (int)($_POST['id'] ?? 0);
if ($noteId <= 0) jsonResponse(false, 'Select a valid note.');

Customer::deleteNote($noteId);
jsonResponse(true, 'Note has been deleted.');
