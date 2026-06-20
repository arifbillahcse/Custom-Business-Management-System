<?php
require_once __DIR__ . '/_guard.php';

requireMethod('POST');
requireAdminApi();

$id     = (int)trim($_POST['id']     ?? 0);
$action = trim($_POST['action'] ?? '');   // 'toggle_pin' | 'set_status'

if ($id <= 0) jsonResponse(false, 'Select a valid note.');

$row = Database::fetchOne('SELECT * FROM free_notes WHERE id = ? LIMIT 1', [$id]);
if (!$row) jsonResponse(false, 'Note not found.');

if ($action === 'toggle_pin') {
    $newPin = $row['is_pinned'] ? 0 : 1;
    Database::execute('UPDATE free_notes SET is_pinned = ? WHERE id = ?', [$newPin, $id]);
    jsonResponse(true, $newPin ? 'Note has been pinned.' : 'Pin removed.', ['is_pinned' => $newPin]);
}

if ($action === 'set_status') {
    $newStatus = trim($_POST['status'] ?? '');
    if (!in_array($newStatus, ['pending', 'done'], true)) {
        jsonResponse(false, 'Provide a valid status.');
    }
    Database::execute('UPDATE free_notes SET status = ? WHERE id = ?', [$newStatus, $id]);
    $msg = $newStatus === 'done' ? 'Marked as done.' : 'Marked as pending.';
    jsonResponse(true, $msg, ['status' => $newStatus]);
}

jsonResponse(false, 'Unknown action.');
