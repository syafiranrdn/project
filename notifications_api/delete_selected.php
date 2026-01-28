<?php
require_once '../auth.php';
require_once '../../database.php';

header('Content-Type: application/json; charset=utf-8');

/* =============================
   AUTH CHECK
============================= */
$userId = (int)($_SESSION['user_id'] ?? 0);
if ($userId <= 0) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'unauthorized'
    ]);
    exit;
}

/* =============================
   READ JSON BODY
============================= */
$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

if (
    !isset($data['ids']) ||
    !is_array($data['ids']) ||
    empty($data['ids'])
) {
    echo json_encode([
        'success' => false,
        'error'   => 'no_ids'
    ]);
    exit;
}

/* =============================
   SANITIZE IDS
============================= */
$ids = array_map('intval', $data['ids']);
$ids = array_filter($ids, fn($v) => $v > 0);

if (empty($ids)) {
    echo json_encode([
        'success' => false,
        'error'   => 'invalid_ids'
    ]);
    exit;
}

/* =============================
   BUILD PLACEHOLDERS
============================= */
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types        = str_repeat('i', count($ids) + 1);

/* =============================
   SOFT DELETE
   (MOVE TO HISTORY)
============================= */
$sql = "
    UPDATE notifications
    SET is_deleted = 1
    WHERE recipient_user_id = ?
      AND notification_id IN ($placeholders)
";

$stmt = $conn->prepare($sql);

$params = array_merge([$userId], $ids);
$stmt->bind_param($types, ...$params);
$stmt->execute();

$affected = $stmt->affected_rows;
$stmt->close();

/* =============================
   RESPONSE
============================= */
echo json_encode([
    'success' => true,
    'deleted' => $affected
]);
exit;
