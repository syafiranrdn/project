<?php
require_once '../auth.php';
require_once '../../database.php';
require_once __DIR__ . '/../notifications/notifications_create.php';

if (!isSystemAdmin()) {
    echo json_encode(['success'=>false,'error'=>'Unauthorized']);
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) exit;

$conn->query("
    UPDATE access_requests
    SET status='Rejected'
    WHERE request_id={$id}
");

notifyAdmins(
    $conn,
    "❌ Access request rejected",
    'danger',
    'users_report.php'
);

echo json_encode(['success'=>true]);
