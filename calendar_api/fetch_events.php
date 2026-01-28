<?php
require_once __DIR__ . '/../auth.php';
require_once '../../database.php';

header('Content-Type: application/json; charset=utf-8');

/* =============================
   AUTH CHECK
============================= */
if (
    !isset($_SESSION['user_id']) ||
    !in_array($_SESSION['role'] ?? '', ['Admin','Staff'], true)
) {
    http_response_code(403);
    echo json_encode([]);
    exit;
}

$uid  = (int)$_SESSION['user_id'];
$mode = $_GET['mode'] ?? '';

/* =====================================================
   TEMPLATES (HTML FOR LEFT PANEL)
===================================================== */
if ($mode === 'templates') {

    $stmt = $conn->prepare("
        SELECT id, title
        FROM calendar_events
        WHERE is_template = 1
          AND user_id = ?
        ORDER BY id DESC
    ");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();

    if (!$res || $res->num_rows === 0) {
        echo '<small class="text-muted">No templates</small>';
        exit;
    }

    while ($r = $res->fetch_assoc()) {
        $id = (int)$r['id'];
        $t  = htmlspecialchars($r['title'], ENT_QUOTES);

        echo "
        <div class='external-event bg-primary p-2 mb-2 text-white'
             data-id='{$id}'
             data-title='{$t}'>
          <span>{$t}</span>
          <span class='t-actions'>
            <button type='button'
                    class='btn btn-xs btn-light btn-del-template'
                    data-id='{$id}'>
              <i class='fas fa-times'></i>
            </button>
          </span>
        </div>";
    }
    exit;
}

/* =====================================================
   EVENTS (JSON FOR FULLCALENDAR)
   ✅ RETURN allDay from DB
   ✅ For allDay=1 → return start as "YYYY-MM-DD"
   ✅ For allDay=0 → return start as "YYYY-MM-DD HH:MM:SS"
===================================================== */
$stmt = $conn->prepare("
    SELECT
        id,
        title,
        start,
        end,
        notes,
        status,
        allDay
    FROM calendar_events
    WHERE is_template = 0
      AND user_id = ?
    ORDER BY start ASC
");
$stmt->bind_param("i", $uid);
$stmt->execute();
$res = $stmt->get_result();

$events = [];

while ($r = $res->fetch_assoc()) {
    $allDay = ((int)$r['allDay'] === 1);

    $startRaw = (string)($r['start'] ?? '');
    // If allDay, normalize to YYYY-MM-DD (important!)
    if ($allDay && strlen($startRaw) >= 10) {
        $startOut = substr($startRaw, 0, 10);
    } else {
        $startOut = $startRaw;
    }

    $events[] = [
        'id'    => (string)$r['id'],
        'title' => (string)$r['title'],
        'start' => $startOut,
        'allDay'=> $allDay,
        'extendedProps' => [
            'notes'  => $r['notes'] ?? '',
            'status' => $r['status'] ?? 'Active'
        ]
    ];
}

echo json_encode($events);
