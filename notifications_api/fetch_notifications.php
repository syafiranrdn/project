<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../database.php';

header('Content-Type: application/json; charset=utf-8');

/* =============================
   AUTH CHECK
============================= */
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['success' => false]);
    exit;
}

/* =============================
   FETCH ACTIVE NOTIFICATIONS
   (ONLY is_deleted = 0)
============================= */
$stmt = $conn->prepare("
    SELECT
        notification_id AS id,
        title,
        message,
        type,
        link,
        is_read,
        created_at
    FROM notifications
    WHERE recipient_user_id = ?
      AND is_deleted = 0
    ORDER BY created_at DESC
    LIMIT 20
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$data   = [];
$unread = 0;

while ($row = $res->fetch_assoc()) {

    if ((int)$row['is_read'] === 0) {
        $unread++;
    }

    $data[] = [
        'id'         => (int)$row['id'],
        'title'      => $row['title'],
        'message'    => $row['message'],
        'type'       => $row['type'],
        'link'       => $row['link'],
        'is_read'    => (int)$row['is_read'],
        'created_at' => $row['created_at']
    ];
}

$stmt->close();

/* =============================
   RESPONSE (NAVBAR COMPATIBLE)
============================= */
echo json_encode([
    'success'       => true,
    'unread_count' => $unread,
    'data'          => $data
]);
exit;
