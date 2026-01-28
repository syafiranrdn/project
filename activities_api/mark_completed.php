<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../database.php';
require_once __DIR__ . '/../notifications/notifications_create.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') exit;

/* =============================
   ACCESS CONTROL
============================= */
if (!isSystemAdmin() && !isHOD()) {
    http_response_code(403);
    echo "Forbidden";
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo "Invalid ID";
    exit;
}

$actorId     = (int)$_SESSION['user_id'];
$currentDept = (int)($_SESSION['department_id'] ?? 0);
$isAdmin     = isSystemAdmin();

/* =============================
   LOAD ACTIVITY
============================= */
$stmt = $conn->prepare("
    SELECT title, department_id
    FROM activities
    WHERE activity_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$activity) {
    echo "Not found";
    exit;
}

/* =============================
   PERMISSION CHECK
============================= */
if (!$isAdmin && (int)$activity['department_id'] !== $currentDept) {
    http_response_code(403);
    echo "No permission";
    exit;
}

/* =============================
   MARK COMPLETED
============================= */
$u = $conn->prepare("
    UPDATE activities
    SET status = 'Completed'
    WHERE activity_id = ?
    LIMIT 1
");
$u->bind_param("i", $id);
$u->execute();
$u->close();

/* =============================
   NOTIFICATIONS
============================= */
$title = $activity['title'] ?? 'Activity';
$link  = "project_view.php?id={$id}";
$name  = $_SESSION['name'] ?? 'User';

notifyProjectMembers(
    $conn,
    $id,
    "✅ {$name} completed activity: {$title}",
    'success',
    $link
);

notifyActorOnly(
    $conn,
    $actorId,
    "✅ You completed activity: {$title}",
    'success',
    $link
);

echo "OK";
