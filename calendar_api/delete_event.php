<?php
require_once __DIR__ . '/../auth.php';
require_once '../../database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin','Staff'])) {
    echo json_encode(['success'=>false]);
    exit;
}

$uid  = (int)$_SESSION['user_id'];
$id   = (int)($_POST['id'] ?? 0);
$type = $_POST['type'] ?? 'event';

if ($id <= 0) {
    echo json_encode(['success'=>false,'error'=>'invalid_id']);
    exit;
}

$stmt = ($type === 'template')
    ? $conn->prepare("DELETE FROM calendar_events WHERE id=? AND user_id=? AND is_template=1 LIMIT 1")
    : $conn->prepare("DELETE FROM calendar_events WHERE id=? AND user_id=? AND is_template=0 LIMIT 1");

$stmt->bind_param("ii", $id, $uid);
$stmt->execute();

echo json_encode(['success'=>($stmt->affected_rows > 0)]);
