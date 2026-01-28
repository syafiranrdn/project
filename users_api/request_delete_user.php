<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../notifications/notifications_create.php';

header('Content-Type: application/json; charset=utf-8');

if (!isHOD()) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'forbidden']);
    exit;
}

$target = (int)($_POST['id'] ?? 0);
$reason = trim($_POST['reason'] ?? '');
$by     = (int)($_SESSION['user_id'] ?? 0);

if ($target <= 0 || $reason === '' || $by <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'invalid_input']);
    exit;
}

/* Ensure target user exists + get name */
$stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $target);
$stmt->execute();
$res = $stmt->get_result();
$u = $res->fetch_assoc();
$stmt->close();

if (!$u) {
    echo json_encode(['success'=>false,'error'=>'target_user_not_found']);
    exit;
}

$targetName = $u['name'];

/* Prevent duplicate pending for same target */
$stmt = $conn->prepare("
    SELECT request_id
    FROM user_delete_requests
    WHERE target_user_id = ?
      AND status = 'Pending'
    LIMIT 1
");
$stmt->bind_param("i", $target);
$stmt->execute();
$stmt->store_result();
$hasPending = ($stmt->num_rows > 0);
$stmt->close();

if ($hasPending) {
    echo json_encode(['success'=>false,'error'=>'already_pending']);
    exit;
}

/* Insert new request (snapshot name) */
$stmt = $conn->prepare("
    INSERT INTO user_delete_requests
      (requested_by, target_user_id, target_user_name, reason, status, created_at)
    VALUES
      (?, ?, ?, ?, 'Pending', NOW())
");
$stmt->bind_param("iiss", $by, $target, $targetName, $reason);

if (!$stmt->execute()) {
    echo json_encode(['success'=>false,'error'=>'insert_failed']);
    exit;
}
$stmt->close();

notifyAdmins(
    $conn,
    "🗑️ New user delete request",
    'warning',
    'user_delete_requests.php'
);

echo json_encode(['success'=>true]);
exit;
