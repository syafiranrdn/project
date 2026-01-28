<?php
include '../auth.php';
include '../../database.php';

header('Content-Type: application/json');

$activity_id = (int)($_POST['activity_id'] ?? 0);
$title       = trim($_POST['title'] ?? '');

if ($activity_id <= 0 || $title === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid input'
    ]);
    exit;
}

/* =============================
   INSERT SUBTASK
============================= */
$stmt = $conn->prepare("
    INSERT INTO activity_subtasks (activity_id, title, status)
    VALUES (?, ?, 'Pending')
");
$stmt->bind_param("is", $activity_id, $title);

if (!$stmt->execute()) {
    echo json_encode([
        'success' => false,
        'message' => 'Insert failed'
    ]);
    exit;
}

echo json_encode([
    'success'    => true,
    'subtask_id'=> $stmt->insert_id,
    'title'     => htmlspecialchars($title, ENT_QUOTES)
]);

$stmt->close();
