<?php
require_once '../auth.php';
require_once '../../database.php';

if (!isSystemAdmin() && !isHOD()) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'forbidden']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['success'=>false]);
    exit;
}

$stmt = $conn->prepare("
  UPDATE access_requests
  SET status = 'Received'
  WHERE request_id = ?
  LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

echo json_encode(['success'=>true]);
