<?php
require_once '../auth.php';
require_once '../../database.php';

$userId = (int)$_SESSION['user_id'];
$id = (int)($_POST['id'] ?? 0);

if ($userId <= 0 || $id <= 0) {
  echo json_encode(['success'=>false]);
  exit;
}

$stmt = $conn->prepare("
  DELETE FROM notifications
  WHERE notification_id = ?
    AND recipient_user_id = ?
    AND is_deleted = 1
");
$stmt->bind_param("ii", $id, $userId);
$stmt->execute();
$stmt->close();

echo json_encode(['success'=>true]);
