<?php
require_once __DIR__ . '/_guard.php';

requireMethod('POST');
requireAdminApi();

$customerName = trim($_POST['customer_name'] ?? '');
$note         = trim($_POST['note']          ?? '');
$noteDate     = trim($_POST['note_date']     ?? '');

if ($customerName === '') jsonResponse(false, 'Enter the customer name.');
if ($note         === '') jsonResponse(false, 'Write a note.');
if ($noteDate     === '') $noteDate = date('Y-m-d');

$author = $_SESSION['user_name'] ?? 'Unknown';

$id = Database::insert(
    'INSERT INTO free_notes (customer_name, note, note_date, author) VALUES (?, ?, ?, ?)',
    [$customerName, $note, $noteDate, $author]
);

jsonResponse(true, 'Note saved.', ['id' => (int)$id]);
