<?php
include 'auth.php';
include '../database.php';

header('Content-Type: application/json');

/* =========================
   SAFE QUERY HELPER
========================= */
function fetchCount($conn, $sql) {
    $res = mysqli_query($conn, $sql);
    if (!$res) return 0;
    $row = mysqli_fetch_assoc($res);
    return (int)($row['total'] ?? 0);
}

/* =========================
   STATUS COUNTS
========================= */
$pending = fetchCount($conn,
    "SELECT COUNT(*) AS total FROM activities WHERE status='Pending'"
);

$in_progress = fetchCount($conn,
    "SELECT COUNT(*) AS total FROM activities WHERE status='In Progress'"
);

$completed = fetchCount($conn,
    "SELECT COUNT(*) AS total FROM activities WHERE status='Completed'"
);

/* =========================
   SLA METRICS
========================= */
$sla_total = fetchCount($conn,
    "SELECT COUNT(*) AS total FROM activities WHERE completed_at IS NOT NULL"
);

$sla_met = fetchCount($conn,
    "SELECT COUNT(*) AS total
     FROM activities
     WHERE completed_at IS NOT NULL
     AND sla_due_at IS NOT NULL
     AND completed_at <= sla_due_at"
);

$sla_percentage = ($sla_total > 0)
    ? round(($sla_met / $sla_total) * 100, 2)
    : 0;

/* =========================
   AVG RESOLUTION TIME (HOURS)
========================= */
$avg_resolution_hours = 0;

$res = mysqli_query($conn, "
    SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) AS avg_minutes
    FROM activities
    WHERE completed_at IS NOT NULL
");

if ($res) {
    $row = mysqli_fetch_assoc($res);
    if (!empty($row['avg_minutes'])) {
        $avg_resolution_hours = round($row['avg_minutes'] / 60, 2);
    }
}

/* =========================
   OUTPUT JSON
========================= */
echo json_encode([
    'status_counts' => [
        'pending' => $pending,
        'in_progress' => $in_progress,
        'completed' => $completed
    ],
    'sla' => [
        'total' => $sla_total,
        'met' => $sla_met,
        'percentage' => $sla_percentage
    ],
    'avg_resolution_hours' => $avg_resolution_hours
]);
