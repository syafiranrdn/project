<?php
ob_start();

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../../database.php';

header('Content-Type: application/json; charset=utf-8');

/* 🔐 ADMIN ONLY */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'unauthorized']);
    exit;
}

/* 🔒 POST ONLY */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'invalid_method']);
    exit;
}

/* VALIDATE ID */
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0 || $id == $_SESSION['user_id']) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'invalid_id']);
    exit;
}

/* DELETE USER */
$stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();

$success = ($stmt->affected_rows > 0);
$stmt->close();

ob_clean();
echo json_encode(['success' => $success]);
exit;
