<?php
declare(strict_types=1);

require_once '../auth.php';
require_once '../../database.php';
require_once __DIR__ . '/../notifications/notifications_create.php';

header('Content-Type: application/json; charset=utf-8');

if (!isSystemAdmin()) {
    http_response_code(403);
    echo json_encode(['success'=>false,'error'=>'unauthorized']);
    exit;
}

$requestId = (int)($_POST['request_id'] ?? 0);
$userId    = (int)($_POST['user_id'] ?? 0);

if ($requestId <= 0 || $userId <= 0) {
    echo json_encode(['success'=>false,'error'=>'invalid_input']);
    exit;
}

$conn->begin_transaction();

try {

    /* Lock request */
    $stmt = $conn->prepare("
        SELECT requested_by
        FROM user_delete_requests
        WHERE request_id=? AND status='Pending'
        FOR UPDATE
    ");
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new Exception('request_not_pending');
    }

    /* Approve request */
    $stmt = $conn->prepare("
        UPDATE user_delete_requests
        SET status='Approved', decided_at=NOW(), decided_by=?
        WHERE request_id=?
    ");
    $stmt->bind_param("ii", $_SESSION['user_id'], $requestId);
    $stmt->execute();
    $stmt->close();

    /* Deactivate user */
    $stmt = $conn->prepare("
        UPDATE users
        SET status='Inactive'
        WHERE user_id=?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    $conn->commit();

    notifyUser(
        $conn,
        (int)$row['requested_by'],
        "✅ User deletion approved",
        'success',
        'users_report.php'
    );

    echo json_encode(['success'=>true]);

} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success'=>false,'error'=>$e->getMessage()]);
}
