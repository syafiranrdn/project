<?php
ob_start();

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../database.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success'=>false,'error'=>'invalid_method']);
    exit;
}

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    ob_clean();
    echo json_encode(['success'=>false,'error'=>'forbidden']);
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    ob_clean();
    echo json_encode(['success'=>false,'error'=>'invalid_id']);
    exit;
}

$actorId = (int)$_SESSION['user_id'];

/* ensure admin meta exists */
if (!isset($_SESSION['department_id']) || !isset($_SESSION['admin_level'])) {
    $meta = $conn->prepare("SELECT department_id, admin_level FROM users WHERE user_id=? LIMIT 1");
    $meta->bind_param("i", $actorId);
    $meta->execute();
    $m = $meta->get_result()->fetch_assoc();
    $meta->close();

    $_SESSION['department_id'] = (int)($m['department_id'] ?? 0);
    $_SESSION['admin_level']   = $m['admin_level'] ?? 'Department';
}

$currentDeptId = (int)$_SESSION['department_id'];
$isSuperAdmin  = (($_SESSION['admin_level'] ?? 'Department') === 'Super');

/* get activity dept for permission */
$q = $conn->prepare("SELECT department_id FROM activities WHERE activity_id=? LIMIT 1");
$q->bind_param("i", $id);
$q->execute();
$act = $q->get_result()->fetch_assoc();
$q->close();

if (!$act) {
    ob_clean();
    echo json_encode(['success'=>false,'error'=>'not_found']);
    exit;
}

$activityDeptId = (int)($act['department_id'] ?? 0);

if (!$isSuperAdmin && $activityDeptId !== $currentDeptId) {
    ob_clean();
    echo json_encode(['success'=>false,'error'=>'no_permission_other_department']);
    exit;
}

/* delete subtasks + activity_users first */
$stmt = $conn->prepare("DELETE FROM activity_subtasks WHERE activity_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

$stmt = $conn->prepare("DELETE FROM activity_users WHERE activity_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

/* delete activity */
$stmt = $conn->prepare("DELETE FROM activities WHERE activity_id=? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$success = ($stmt->affected_rows > 0);
$stmt->close();

ob_clean();
echo json_encode(['success'=>$success]);
exit;
