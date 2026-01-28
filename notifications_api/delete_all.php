<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../database.php';

header('Content-Type: application/json');

$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(403);
    echo json_encode(['success' => false]);
    exit;
}

/* =============================
   DELETE ALL USER NOTIFICATIONS
============================= */
$stmt = $conn->prepare("
    DELETE FROM notifications
    WHERE recipient_user_id = ?
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true]);
exit;
