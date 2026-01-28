<?php
include '../auth.php';
include '../../database.php';

header('Content-Type: application/json');

$subtask_id  = (int)($_POST['id'] ?? 0);
$activity_id = (int)($_POST['activity_id'] ?? 0);

if ($subtask_id <= 0 || $activity_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit;
}

/* =============================
   DELETE SUBTASK
============================= */
$stmt = $conn->prepare("
    DELETE FROM activity_subtasks
    WHERE subtask_id = ?
      AND activity_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $subtask_id, $activity_id);
$stmt->execute();
$stmt->close();

/* =============================
   UPDATE ACTIVITY STATUS
============================= */
/* If no subtasks left OR any pending → In Progress */
$res = $conn->prepare("
    SELECT COUNT(*) AS total,
           SUM(status='Completed') AS done
    FROM activity_subtasks
    WHERE activity_id = ?
");
$res->bind_param("i", $activity_id);
$res->execute();
$row = $res->get_result()->fetch_assoc();
$res->close();

$total = (int)$row['total'];
$done  = (int)$row['done'];

$newStatus = ($total > 0 && $done === $total)
    ? 'Completed'
    : 'In Progress';

$u = $conn->prepare("
    UPDATE activities
    SET status = ?
    WHERE activity_id = ?
");
$u->bind_param("si", $newStatus, $activity_id);
$u->execute();
$u->close();

echo json_encode(['success' => true]);
