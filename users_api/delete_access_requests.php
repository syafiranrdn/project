<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../database.php';

header('Content-Type: application/json; charset=utf-8');

/* ADMIN ONLY */
if (!isSystemAdmin()) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'unauthorized']);
    exit;
}

/* VALIDATE ID */
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(['success'=>false,'error'=>'invalid_id']);
    exit;
}

/* DELETE */
$stmt = $conn->prepare("
    DELETE FROM access_requests
    WHERE request_id = ?
    LIMIT 1
");

if (!$stmt) {
    echo json_encode(['success'=>false,'error'=>'prepare_failed']);
    exit;
}

$stmt->bind_param("i", $id);
$stmt->execute();
$deleted = $stmt->affected_rows;
$stmt->close();

echo json_encode([
    'success' => $deleted > 0,
    'deleted' => $deleted
]);
exit;
