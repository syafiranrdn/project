<?php
session_start();
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../auth.php';

header('Content-Type: application/json; charset=utf-8');

$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'invalid_method']);
    exit;
}

$noteId = (int)($_POST['note_id'] ?? 0);
$status = trim($_POST['status'] ?? '');
$order  = (int)($_POST['sort_order'] ?? 0);

$allowed = ['Todo','Doing','Done'];
if ($noteId <= 0 || !in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'invalid_input']);
    exit;
}

/* ensure user owns note */
$chk = $conn->prepare("SELECT note_id FROM user_notes WHERE note_id=? AND user_id=? LIMIT 1");
$chk->bind_param("ii", $noteId, $user_id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows <= 0) {
    $chk->close();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'forbidden']);
    exit;
}
$chk->close();

/* update status + order */
$stmt = $conn->prepare("UPDATE user_notes SET status=?, sort_order=? WHERE note_id=? AND user_id=?");
$stmt->bind_param("siii", $status, $order, $noteId, $user_id);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => (bool)$ok]);
