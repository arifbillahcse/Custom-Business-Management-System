<?php
require_once __DIR__ . '/_guard.php';

requireMethod('POST');
requireAdminApi();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) jsonResponse(false, 'Select a valid note.');

$row = Database::fetchOne('SELECT id FROM free_notes WHERE id = ? LIMIT 1', [$id]);
if (!$row) jsonResponse(false, 'Note not found.');

Database::execute('DELETE FROM free_notes WHERE id = ?', [$id]);
jsonResponse(true, 'Note has been deleted.');
