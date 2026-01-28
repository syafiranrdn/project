<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../database.php';

header('Content-Type: application/json; charset=utf-8');

/* =============================
   AUTH
============================= */
$userId = (int)($_SESSION['user_id'] ?? 0);
$notifId = (int)($_POST['id'] ?? 0);

if ($userId <= 0 || $notifId <= 0) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'invalid_input']);
    exit;
}

/* =============================
   MARK AS READ
============================= */
$stmt = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE notification_id = ?
      AND recipient_user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $notifId, $userId);
$stmt->execute();

$success = ($stmt->affected_rows > 0);
$stmt->close();

echo json_encode(['success'=>$success]);
exit;
