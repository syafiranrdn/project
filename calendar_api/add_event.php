<?php
require_once __DIR__ . '/../auth.php';
require_once '../../database.php';

header('Content-Type: application/json; charset=utf-8');

/* =============================
   AUTH CHECK
============================= */
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['Admin', 'Staff'], true)
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
$mode = $_POST['mode'] ?? ($_GET['mode'] ?? '');
$uid  = (int)($_SESSION['user_id'] ?? 0);

if ($uid <= 0) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error'   => 'unauthorized'
    ]);
    exit;
}

/* =========================================================
   CREATE TEMPLATE EVENT
========================================================= */
if ($mode === 'template') {

    $title = trim($_POST['title'] ?? '');

    if ($title === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'missing_title'
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO calendar_events
            (title, is_template, status, created_by, user_id)
        VALUES
            (?, 1, 'Active', ?, ?)
    ");

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error'   => 'prepare_failed'
        ]);
        exit;
    }

    $stmt->bind_param("sii", $title, $uid, $uid);
    $stmt->execute();
    $newId = (int)$conn->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'id'      => $newId
    ]);
    exit;
}

/* =========================================================
   CREATE EVENT (DRAG & DROP)
========================================================= */
if ($mode === 'drop') {

    $title  = trim($_POST['title'] ?? '');
    $raw    = trim($_POST['start'] ?? '');
    $notes  = (string)($_POST['notes'] ?? '');
    $allDay = isset($_POST['allDay']) ? (int)$_POST['allDay'] : 1;

    if ($title === '' || $raw === '') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error'   => 'missing_data'
        ]);
        exit;
    }

    /**
     * 🔥 IMPORTANT FIX
     * - allDay = 1 → DATE ONLY (no 12a issue)
     * - allDay = 0 → DATETIME
     */
    try {
        $dt = new DateTime($raw);

        if ($allDay === 1) {
            $start = $dt->format('Y-m-d');
        } else {
            $start = $dt->format('Y-m-d H:i:s');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error'   => 'invalid_date'
        ]);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO calendar_events
            (title, start, notes, allDay, is_template, status, created_by, user_id)
        VALUES
            (?, ?, ?, ?, 0, 'Active', ?, ?)
    ");

    if (!$stmt) {
        echo json_encode([
            'success' => false,
            'error'   => 'prepare_failed'
        ]);
        exit;
    }

    $stmt->bind_param(
        "sssiii",
        $title,
        $start,
        $notes,
        $allDay,
        $uid,
        $uid
    );

    $stmt->execute();
    $newId = (int)$conn->insert_id;
    $stmt->close();

    echo json_encode([
        'success' => true,
        'id'      => $newId,
        'start'   => $start,
        'allDay'  => $allDay
    ]);
    exit;
}

/* =============================
   INVALID MODE
============================= */
http_response_code(400);
echo json_encode([
    'success' => false,
    'error'   => 'invalid_mode'
]);
