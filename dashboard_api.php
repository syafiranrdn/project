<?php
include 'auth.php';
include '../database.php';

header('Content-Type: application/json');

/* =========================
   STATUS COUNTS
========================= */
$pending = (int) mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM activities WHERE status='Pending'")
)['total'];

$in_progress = (int) mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM activities WHERE status='In Progress'")
)['total'];

$completed = (int) mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT COUNT(*) AS total FROM activities WHERE status='Completed'")
)['total'];

/* =========================
   SLA METRICS (COMPLETED ONLY)
========================= */

// Total completed tasks
$sla_total = (int) mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM activities
        WHERE status = 'Completed'
        AND completed_at IS NOT NULL
    ")
)['total'];

// Completed within SLA
$sla_met = (int) mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT COUNT(*) AS total
        FROM activities
        WHERE status = 'Completed'
        AND completed_at IS NOT NULL
        AND sla_due_at IS NOT NULL
        AND completed_at <= sla_due_at
    ")
)['total'];

$sla_percentage = ($sla_total > 0)
    ? round(($sla_met / $sla_total) * 100, 2)
    : 0;

/* =========================
   AVG RESOLUTION TIME (HOURS)
========================= */
$avg_minutes = mysqli_fetch_assoc(
    mysqli_query($conn, "
        SELECT AVG(TIMESTAMPDIFF(MINUTE, created_at, completed_at)) AS avg_minutes
        FROM activities
        WHERE status = 'Completed'
        AND completed_at IS NOT NULL
    ")
)['avg_minutes'];

$avg_resolution_hours = $avg_minutes
    ? round($avg_minutes / 60, 2)
    : 0;

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
