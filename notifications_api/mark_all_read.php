<?php
include '../auth.php';
include '../../database.php';

header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $conn->prepare("
    UPDATE notifications
    SET is_read = 1
    WHERE recipient_user_id = ?
      AND is_read = 0
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
