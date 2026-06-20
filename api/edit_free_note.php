<?php
require_once __DIR__ . '/_guard.php';

requireMethod('POST');
requireAdminApi();

$id           = (int)trim($_POST['id']            ?? 0);
$customerName = trim($_POST['customer_name'] ?? '');
$note         = trim($_POST['note']          ?? '');
$noteDate     = trim($_POST['note_date']     ?? '');

if ($id <= 0)          jsonResponse(false, 'Select a valid note.');
if ($customerName === '') jsonResponse(false, 'Enter the customer name.');
if ($note         === '') jsonResponse(false, 'Write a note.');
if ($noteDate     === '') $noteDate = date('Y-m-d');

$row = Database::fetchOne('SELECT id FROM free_notes WHERE id = ? LIMIT 1', [$id]);
if (!$row) jsonResponse(false, 'Note not found.');

Database::execute(
    'UPDATE free_notes SET customer_name = ?, note = ?, note_date = ? WHERE id = ?',
    [$customerName, $note, $noteDate, $id]
);

jsonResponse(true, 'Note updated.');
