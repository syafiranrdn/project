<?php
require_once __DIR__ . '/../auth.php';
require_once '../../database.php';

header('Content-Type: application/json; charset=utf-8');

/* ================= AUTH ================= */
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin','Staff'])) {
    echo json_encode(['success' => false, 'error' => 'forbidden']);
    exit;
}

$uid = (int) $_SESSION['user_id'];
$id  = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    echo json_encode(['success' => false, 'error' => 'invalid_id']);
    exit;
}

/* ================= INPUT ================= */
$notes = isset($_POST['notes']) ? (string)$_POST['notes'] : null;
$start = isset($_POST['start']) ? (string)$_POST['start'] : null;

/*
  🔥 CRITICAL FIX
  - start is DATETIME
  - allDay MUST be 0 if event has time
  - we always keep event visible after refresh
*/
$sql = "
    UPDATE calendar_events
    SET
        notes  = COALESCE(?, notes),
        start  = COALESCE(?, start),
        allDay = 0
    WHERE id = ?
      AND user_id = ?
      AND is_template = 0
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'error'   => 'prepare_failed',
        'sql'     => $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    "ssii",
    $notes,
    $start,
    $id,
    $uid
);

$stmt->execute();

if ($stmt->affected_rows < 0) {
    echo json_encode(['success' => false, 'error' => 'update_failed']);
    $stmt->close();
    exit;
}

$stmt->close();

echo json_encode(['success' => true]);
