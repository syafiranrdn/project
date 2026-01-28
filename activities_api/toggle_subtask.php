<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../notifications/notifications_create.php';

header('Content-Type: application/json; charset=utf-8');

/* =============================
   INPUT & AUTH
============================= */
$subtask_id  = (int)($_POST['id'] ?? 0);
$activity_id = (int)($_POST['activity_id'] ?? 0);
$status      = $_POST['status'] ?? '';

$actorId   = (int)($_SESSION['user_id'] ?? 0);
$actorName = $_SESSION['name'] ?? 'Someone';

if (
    $actorId <= 0 ||
    $subtask_id <= 0 ||
    $activity_id <= 0 ||
    !in_array($status, ['Pending','Completed'], true)
) {
    http_response_code(400);
    echo json_encode(['success'=>false,'error'=>'invalid_input']);
    exit;
}

/* =============================
   UPDATE SUBTASK
============================= */
$stmt = $conn->prepare("
    UPDATE activity_subtasks
    SET status = ?
    WHERE subtask_id = ?
      AND activity_id = ?
    LIMIT 1
");
$stmt->bind_param("sii", $status, $subtask_id, $activity_id);
$stmt->execute();
$stmt->close();

/* =============================
   RECALCULATE PROGRESS
============================= */
$res = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status='Completed') AS done
    FROM activity_subtasks
    WHERE activity_id = {$activity_id}
");
$r = $res->fetch_assoc();

$total = (int)$r['total'];
$done  = (int)$r['done'];

$progress = $total > 0 ? round(($done / $total) * 100) : 0;

/* =============================
   DETERMINE ACTIVITY STATUS
============================= */
if ($progress === 0) {
    $aStatus = 'Pending';
} elseif ($progress === 100) {
    $aStatus = 'Completed';
} else {
    $aStatus = 'In Progress';
}

/* =============================
   UPDATE ACTIVITY STATUS
============================= */
$u = $conn->prepare("
    UPDATE activities
    SET status = ?
    WHERE activity_id = ?
    LIMIT 1
");
$u->bind_param("si", $aStatus, $activity_id);
$u->execute();
$u->close();

/* =============================
   FETCH ACTIVITY INFO
============================= */
$q = $conn->prepare("
    SELECT title, created_by
    FROM activities
    WHERE activity_id = ?
    LIMIT 1
");
$q->bind_param("i", $activity_id);
$q->execute();
$activity = $q->get_result()->fetch_assoc();
$q->close();

$title = $activity['title'] ?? 'Activity';
$creatorId = (int)($activity['created_by'] ?? 0);

$link = "project_view.php?id={$activity_id}";

/* =============================
   NOTIFICATIONS
============================= */

/* 1️⃣ Subtask updated */
notifyProjectMembers(
    $conn,
    $activity_id,
    $status === 'Completed'
        ? "✅ {$actorName} completed a subtask in {$title}"
        : "↩️ {$actorName} updated a subtask in {$title}",
    'info',
    $link
);

/* 2️⃣ Activity completed (fire once) */
if ($aStatus === 'Completed' && $total > 0 && $done === $total) {

    notifyProjectMembers(
        $conn,
        $activity_id,
        "🎉 Activity completed: {$title}",
        'success',
        $link
    );

    if ($creatorId && $creatorId !== $actorId) {
        notifyUser(
            $conn,
            $creatorId,
            "🎯 Your activity has been completed",
            $title,
            'success',
            $link
        );
    }
}

/* =============================
   RESPONSE
============================= */
echo json_encode([
    'success'  => true,
    'progress' => $progress,
    'status'   => $aStatus
]);
exit;
