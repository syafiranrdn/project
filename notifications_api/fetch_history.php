<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../database.php';

header('Content-Type: application/json; charset=utf-8');

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success'=>false]);
    exit;
}

$stmt = $conn->prepare("
    SELECT
        notification_id AS id,
        title,
        message,
        created_at
    FROM notifications
    WHERE recipient_user_id = ?
      AND is_deleted = 1
    ORDER BY created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$data = [];
while ($row = $res->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $data
]);
