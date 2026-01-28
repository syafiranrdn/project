<?php
require_once __DIR__ . '/../auth.php';  
include '../../database.php';


header('Content-Type: application/json; charset=utf-8');

/* =============================
   AUTH CHECK
============================= */
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['Admin', 'Staff'])
) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'forbidden'
    ]);
    exit;
}

/* =============================
   CONTEXT
============================= */
$mode = $_POST['mode'] ?? '';          // kept for compatibility
$uid  = (int) $_SESSION['user_id'];    // calendar owner ONLY
$id   = (int) ($_POST['id'] ?? 0);     // event id

/* =============================
   VALIDATE ID
============================= */
if ($id <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'invalid_id'
    ]);
    exit;
}

/* =====================================================
   MARK EVENT AS DONE (OWNER ONLY)
===================================================== */
$stmt = $conn->prepare("
    UPDATE calendar_events
    SET status = 'Done'
    WHERE id = ?
      AND is_template = 0
      AND user_id = ?
    LIMIT 1
");
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'prepare_failed'
    ]);
    exit;
}

$stmt->bind_param("ii", $id, $uid);
$ok = $stmt->execute();
$stmt->close();

echo json_encode([
    'success' => $ok
]);
