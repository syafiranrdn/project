<?php
/**
 * users_api/receive_access.php
 * ---------------------------------------
 * PURPOSE:
 * - ADMIN click "Receive"
 * - ONLY mark access_requests.status = 'Received'
 * - DO NOT create user
 * - DO NOT touch users table
 */

session_start();
require_once '../../database.php';
require_once '../auth.php';

header('Content-Type: application/json; charset=utf-8');

/* =============================
   ACCESS CONTROL (ADMIN ONLY)
============================= */
if (!isSystemAdmin()) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error'   => 'unauthorized'
    ]);
    exit;
}

/* =============================
   VALIDATE INPUT
============================= */
$requestId = (int)($_POST['id'] ?? 0);
if ($requestId <= 0) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error'   => 'invalid_request_id'
    ]);
    exit;
}

/* =============================
   CHECK REQUEST EXISTS
============================= */
$stmt = $conn->prepare("
    SELECT status 
    FROM access_requests 
    WHERE request_id = ?
");
$stmt->bind_param("i", $requestId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error'   => 'request_not_found'
    ]);
    exit;
}

/* =============================
   IF ALREADY RECEIVED
============================= */
if ($row['status'] === 'Received') {
    echo json_encode([
        'success' => true,
        'message' => 'Already received'
    ]);
    exit;
}

/* =============================
   MARK AS RECEIVED
============================= */
$stmt = $conn->prepare("
    UPDATE access_requests
    SET status = 'Received'
    WHERE request_id = ? AND status = 'Pending'
");
$stmt->bind_param("i", $requestId);
$stmt->execute();

$updated = ($stmt->affected_rows > 0);
$stmt->close();

if (!$updated) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'update_failed'
    ]);
    exit;
}

/* =============================
   SUCCESS
============================= */
echo json_encode([
    'success' => true,
    'message' => 'Access request marked as received'
]);
exit;
