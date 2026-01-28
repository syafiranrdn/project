<?php
// admin/automation_runner.php
// Run via CRON / Windows Task Scheduler every 5 minutes

require_once __DIR__ . '/../database.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notifications/notifications_create.php';

date_default_timezone_set('Asia/Kuala_Lumpur');

header('Content-Type: application/json; charset=utf-8');

/* =========================
   SECURITY
========================= */
$ALLOW_TOKEN = 'CHANGE_ME_TO_RANDOM_LONG_TOKEN';

$isCli = (php_sapi_name() === 'cli');
$token = $_GET['token'] ?? '';

if (!$isCli && $token !== $ALLOW_TOKEN) {
  http_response_code(403);
  echo json_encode(['success' => false, 'error' => 'forbidden']);
  exit;
}

/* =========================
   HELPERS
========================= */
function logOnce(mysqli $conn, string $ruleKey, string $refType, int $refId): bool {
  // Returns true if INSERT happened (first time), false if duplicate exists
  $now = date('Y-m-d H:i:s');

  $stmt = $conn->prepare("
    INSERT INTO automation_logs (rule_key, ref_type, ref_id, last_run_at)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE last_run_at = last_run_at
  ");
  if (!$stmt) return false;

  $stmt->bind_param("ssis", $ruleKey, $refType, $refId, $now);
  $stmt->execute();
  $affected = $stmt->affected_rows; // 1 insert, 0 if duplicate no update
  $stmt->close();

  return ($affected === 1);
}

function columnExists(mysqli $conn, string $table, string $column): bool {
  $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
  $column = preg_replace('/[^a-zA-Z0-9_]/', '', $column);

  $sql = "SHOW COLUMNS FROM `$table` LIKE ?";
  $stmt = $conn->prepare($sql);
  if (!$stmt) return false;

  $stmt->bind_param("s", $column);
  $stmt->execute();
  $res = $stmt->get_result();
  $exists = ($res && $res->num_rows > 0);
  $stmt->close();
  return $exists;
}

function notifyHODByDepartment(mysqli $conn, int $departmentId, string $title, string $message, string $type, string $link) {
  // Based on your system: HOD = role 'Admin' in same department (adjust if needed)
  $stmt = $conn->prepare("
    SELECT user_id
    FROM users
    WHERE status='Active'
      AND role='Admin'
      AND department_id=?
  ");
  if (!$stmt) return;

  $stmt->bind_param("i", $departmentId);
  $stmt->execute();
  $res = $stmt->get_result();
  $stmt->close();

  if (!$res) return;

  while ($row = $res->fetch_assoc()) {
    $uid = (int)$row['user_id'];
    if ($uid <= 0) continue;

    // notifyUser signature: (conn, userId, title, message, type, link)
    notifyUser($conn, $uid, $title, $message, $type, $link);
  }
}

/* =========================
   RULE 1: OVERDUE → HOD
========================= */
$today = date('Y-m-d');

$q = $conn->prepare("
  SELECT activity_id, title, department_id, due_date
  FROM activities
  WHERE due_date IS NOT NULL
    AND due_date < ?
    AND status NOT IN ('Done','Closed')
");
$q->bind_param("s", $today);
$q->execute();
$overdue = $q->get_result();
$q->close();

$overdueNotified = 0;

if ($overdue) {
  while ($a = $overdue->fetch_assoc()) {
    $aid = (int)$a['activity_id'];
    $deptId = (int)($a['department_id'] ?? 0);
    if ($aid <= 0 || $deptId <= 0) continue;

    if (!logOnce($conn, 'overdue_notify_hod', 'activity', $aid)) continue;

    $title = "⏰ Overdue Task";
    $msg   = "Overdue task: {$a['title']} (Due: {$a['due_date']})";
    $link  = "activities.php?view={$aid}";

    notifyHODByDepartment($conn, $deptId, $title, $msg, "warning", $link);
    $overdueNotified++;
  }
}

/* =========================
   RULE 2: CRITICAL → ADMIN
========================= */
$q = $conn->query("
  SELECT activity_id, title
  FROM activities
  WHERE priority='Critical'
    AND status NOT IN ('Done','Closed')
");

$criticalNotified = 0;

if ($q) {
  while ($a = $q->fetch_assoc()) {
    $aid = (int)$a['activity_id'];
    if ($aid <= 0) continue;

    if (!logOnce($conn, 'critical_notify_admin', 'activity', $aid)) continue;

    // notifyAdmins(title, type, link) -> inside it calls notifyUser
    notifyAdmins(
      $conn,
      "🚨 Critical task: {$a['title']}",
      "danger",
      "activities.php?view={$aid}"
    );

    $criticalNotified++;
  }
}

/* =========================
   RULE 3: AUTO-CLOSE DONE
========================= */
$inactiveDays = 14;
$cutoff = date('Y-m-d H:i:s', time() - ($inactiveDays * 86400));

$stmt = $conn->prepare("
  SELECT activity_id
  FROM activities
  WHERE status='Done'
    AND auto_closed=0
    AND (
      (last_activity_at IS NOT NULL AND last_activity_at < ?)
      OR
      (last_activity_at IS NULL AND created_at < ?)
    )
");
$stmt->bind_param("ss", $cutoff, $cutoff);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();

$closedCount = 0;

if ($res) {
  while ($a = $res->fetch_assoc()) {
    $aid = (int)$a['activity_id'];
    if ($aid <= 0) continue;

    $up = $conn->prepare("
      UPDATE activities
      SET status='Closed', auto_closed=1
      WHERE activity_id=?
      LIMIT 1
    ");
    $up->bind_param("i", $aid);
    $up->execute();
    if ($up->affected_rows > 0) $closedCount++;
    $up->close();
  }
}

/* =========================
   RULE 4: CALENDAR REMINDER (INDIVIDUAL ONLY)
   - Trigger when notify_time is within last 5 minutes
   - Uses remind_notified OR reminder_sent if exists
========================= */
$now = date('Y-m-d H:i:s');
$windowStart = date('Y-m-d H:i:s', time() - 300); // last 5 minutes

// detect which flag column exists
$flagCol = null;
if (columnExists($conn, 'calendar_events', 'remind_notified')) {
  $flagCol = 'remind_notified';
} elseif (columnExists($conn, 'calendar_events', 'reminder_sent')) {
  $flagCol = 'reminder_sent';
}

$calendarReminderSent = 0;

// Build query (prepared) with optional flag column
if ($flagCol) {
  $sql = "
    SELECT id, title, start, remind_before, user_id
    FROM calendar_events
    WHERE is_template = 0
      AND start IS NOT NULL
      AND remind_before IS NOT NULL
      AND remind_before > 0
      AND {$flagCol} = 0
      AND DATE_SUB(start, INTERVAL remind_before MINUTE) <= ?
      AND DATE_SUB(start, INTERVAL remind_before MINUTE) > ?
  ";
} else {
  // fallback: rely on automation_logs only
  $sql = "
    SELECT id, title, start, remind_before, user_id
    FROM calendar_events
    WHERE is_template = 0
      AND start IS NOT NULL
      AND remind_before IS NOT NULL
      AND remind_before > 0
      AND DATE_SUB(start, INTERVAL remind_before MINUTE) <= ?
      AND DATE_SUB(start, INTERVAL remind_before MINUTE) > ?
  ";
}

$stmt = $conn->prepare($sql);
if ($stmt) {
  $stmt->bind_param("ss", $now, $windowStart);
  $stmt->execute();
  $res = $stmt->get_result();
  $stmt->close();

  if ($res) {
    while ($e = $res->fetch_assoc()) {
      $eventId = (int)$e['id'];
      $userId  = (int)$e['user_id'];
      if ($eventId <= 0 || $userId <= 0) continue;

      // prevent duplicates (even if flag column missing)
      if (!logOnce($conn, 'calendar_reminder', 'calendar_event', $eventId)) continue;

      $title = $e['title'] ?: 'Calendar Event';
      $startPretty = date('d M Y, h:i A', strtotime($e['start']));

      // notifyUser signature: (conn, userId, title, message, type, link)
      notifyUser(
        $conn,
        $userId,
        '⏰ Calendar Reminder',
        "Upcoming: {$title} at {$startPretty}",
        'info',
        'calendar.php'
      );

      // mark as notified if column exists
      if ($flagCol) {
        $up = $conn->prepare("UPDATE calendar_events SET {$flagCol}=1 WHERE id=? LIMIT 1");
        if ($up) {
          $up->bind_param("i", $eventId);
          $up->execute();
          $up->close();
        }
      }

      $calendarReminderSent++;
    }
  }
}

/* =========================
   RESULT
========================= */
echo json_encode([
  'success' => true,
  'overdue_notified' => $overdueNotified,
  'critical_notified' => $criticalNotified,
  'auto_closed' => $closedCount,
  'calendar_reminders_sent' => $calendarReminderSent,
  'calendar_flag_column' => ($flagCol ?? 'none')
]);
