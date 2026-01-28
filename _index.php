<?php
include 'auth.php';
include '../database.php';
require_once __DIR__ . '/notifications/notifications_create.php';

/* =============================
   ROLE CONTEXT
============================= */
$role          = $_SESSION['role'] ?? 'Staff';
$userId        = (int)($_SESSION['user_id'] ?? 0);
$departmentId  = (int)($_SESSION['department_id'] ?? 0);
$activePage = 'dashboard';

/* =============================
   SAFE COUNT HELPERS
============================= */
function countGlobal(mysqli $conn, string $status): int {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM activities WHERE status = ?");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $stmt->bind_result($c);
    $stmt->fetch();
    $stmt->close();
    return (int)$c;
}

function countDepartment(mysqli $conn, string $status, int $deptId): int {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM activities WHERE status = ? AND department_id = ?");
    $stmt->bind_param("si", $status, $deptId);
    $stmt->execute();
    $stmt->bind_result($c);
    $stmt->fetch();
    $stmt->close();
    return (int)$c;
}

function countAssigned(mysqli $conn, string $status, int $userId): int {
    $stmt = $conn->prepare("
        SELECT COUNT(*)
        FROM activities a
        JOIN activity_users au ON a.activity_id = au.activity_id
        WHERE au.user_id = ? AND a.status = ?
    ");
    $stmt->bind_param("is", $userId, $status);
    $stmt->execute();
    $stmt->bind_result($c);
    $stmt->fetch();
    $stmt->close();
    return (int)$c;
}

/* =============================
   STATUS COUNTS BY ROLE
============================= */
if ($role === 'Admin') {
    $pending     = countGlobal($conn, 'Pending');
    $in_progress = countGlobal($conn, 'In Progress');
    $completed   = countGlobal($conn, 'Completed');
} elseif ($role === 'HOD') {
    $pending     = countDepartment($conn, 'Pending', $departmentId);
    $in_progress = countDepartment($conn, 'In Progress', $departmentId);
    $completed   = countDepartment($conn, 'Completed', $departmentId);
} else {
    $pending     = countAssigned($conn, 'Pending', $userId);
    $in_progress = countAssigned($conn, 'In Progress', $userId);
    $completed   = countAssigned($conn, 'Completed', $userId);
}

$total = $pending + $in_progress + $completed;

/* =============================
   LATEST ACTIVITIES
============================= */
if ($role === 'Admin') {

    $latestActivities = $conn->query("
        SELECT 
            a.activity_id,
            a.title,
            a.status,
            a.created_at,
            GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') AS assigned_to
        FROM activities a
        LEFT JOIN activity_users au ON a.activity_id = au.activity_id
        LEFT JOIN users u ON au.user_id = u.user_id
        GROUP BY a.activity_id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");

} elseif ($role === 'HOD') {

    $stmt = $conn->prepare("
        SELECT 
            a.activity_id,
            a.title,
            a.status,
            a.created_at,
            GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') AS assigned_to
        FROM activities a
        LEFT JOIN activity_users au ON a.activity_id = au.activity_id
        LEFT JOIN users u ON au.user_id = u.user_id
        WHERE a.department_id = ?
        GROUP BY a.activity_id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $departmentId);
    $stmt->execute();
    $latestActivities = $stmt->get_result();

} else {

    $stmt = $conn->prepare("
        SELECT 
            a.activity_id,
            a.title,
            a.status,
            a.created_at,
            GROUP_CONCAT(u.name ORDER BY u.name SEPARATOR ', ') AS assigned_to
        FROM activities a
        JOIN activity_users au ON a.activity_id = au.activity_id
        LEFT JOIN users u ON au.user_id = u.user_id
        WHERE au.user_id = ?
        GROUP BY a.activity_id
        ORDER BY a.created_at DESC
        LIMIT 5
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $latestActivities = $stmt->get_result();
}

/* =============================
   TODAY TODOS
============================= */
$todayTodos = [];
$stmt = $conn->prepare("
    SELECT note_id, title
    FROM user_notes
    WHERE user_id = ?
      AND status = 'Todo'
    ORDER BY sort_order ASC, created_at ASC
    LIMIT 5
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $todayTodos[] = $r;
$stmt->close();

/* =============================
   PROJECTS BY MONTH
============================= */
$projectsByMonth = array_fill(1, 12, 0);
$params = [];
$types  = '';

$sql = "
    SELECT MONTH(created_at) m, COUNT(*) total
    FROM activities
    WHERE YEAR(created_at) = YEAR(CURDATE())
";

if ($role === 'HOD') {
    $sql .= " AND department_id = ?";
    $params[] = $departmentId;
    $types .= 'i';
} elseif ($role !== 'Admin') {
    $sql .= " AND activity_id IN (
        SELECT activity_id FROM activity_users WHERE user_id = ?
    )";
    $params[] = $userId;
    $types .= 'i';
}

$sql .= " GROUP BY MONTH(created_at)";
$stmt = $conn->prepare($sql);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) {
    $projectsByMonth[(int)$r['m']] = (int)$r['total'];
}
$stmt->close();

$themeClass = (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '';
$pageTitle  = 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Monitoring Dashboard</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro">
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
/* ===============================
   MAGENTA PASTEL THEME
=============================== */
:root {
  --magenta: #ff0090;
  --magenta-soft: #f49ac2;
  --salmon: #ff9999;
  --black: #000;
}

/* HEADERS */
.content-header h1 {
  color: var(--magenta);
  font-weight: 600;
}

/* CARDS */
.card {
  border-top: 3px solid var(--magenta);
}
.card-header {
  background: #fff;
  border-bottom: 1px solid var(--magenta-soft);
}

/* ===============================
   SMALL BOX OVERRIDES (IMPORTANT)
=============================== */
.small-box {
  border-radius: 12px;
}

/* TOTAL */
.small-box.bg-dark {
  background: var(--black) !important;
  color: #fff !important;
}

/* PENDING */
.small-box.bg-warning {
  background: var(--salmon) !important;
  color: #000 !important;
}

/* IN PROGRESS */
.small-box.bg-info {
  background: var(--magenta-soft) !important;
  color: #000 !important;
}

/* COMPLETED */
.small-box.bg-success {
  background: var(--magenta) !important;
  color: #fff !important;
}

/* ICONS INSIDE BOX */
.small-box .icon {
  color: rgba(0,0,0,0.15);
}
.small-box.bg-dark .icon,
.small-box.bg-success .icon {
  color: rgba(255,255,255,0.25);
}

/* LIST GROUP */
.list-group-item:hover {
  background: rgba(244,154,194,0.25);
}

/* TABLE */
.table thead th {
  background: var(--black);
  color: var(--magenta);
  border: none;
}
.table tbody tr {
  transition: background 0.2s ease;
  cursor: pointer;
}
.table tbody tr:hover {
  background: rgba(244,154,194,0.25);
}
.table td {
  vertical-align: middle;
}

/* BADGES */
.badge-success {
  background: var(--magenta);
}
.badge-warning {
  background: var(--salmon);
  color: #000;
}
.badge-info {
  background: var(--magenta-soft);
  color: #000;
}

/* BUTTONS */
.btn-primary {
  background: var(--magenta);
  border-color: var(--magenta);
}
.btn-primary:hover {
  background: #e60080;
  border-color: #e60080;
}

.btn-secondary {
  background: transparent;
  color: var(--magenta);
  border: 1px solid var(--magenta);
}
.btn-secondary:hover {
  background: var(--magenta-soft);
  color: #000;
}

/* FOOTER */
.main-footer {
  border-top: 1px solid var(--magenta-soft);
}


</style>

</head>

<body class="hold-transition sidebar-mini layout-fixed <?= $themeClass ?>">
<div class="wrapper">

<?php include 'partials/navbar.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<div class="content-wrapper">
<section class="content-header">
<div class="container-fluid">
<h1>Dashboard</h1>
</div>
</section>

<section class="content">
<div class="container-fluid">

<!-- STAT BOXES -->
<div class="row">
<?php
$boxes = [
  ['Total Tasks', $total, 'dark', 'tasks'],
  ['Pending', $pending, 'warning', 'clock'],
  ['In Progress', $in_progress, 'info', 'spinner'],
  ['Completed', $completed, 'success', 'check']
];
foreach ($boxes as $b): ?>
<div class="col-lg-3 col-12">
<div class="small-box bg-<?= $b[2] ?>">
<div class="inner">
<h3><?= $b[1] ?></h3>
<p><?= $b[0] ?></p>
</div>
<div class="icon"><i class="fas fa-<?= $b[3] ?>"></i></div>
</div>
</div>
<?php endforeach; ?>
</div>

<!-- NOTES + GRAPH -->
<div class="row mt-4">
<div class="col-md-6">
<div class="card">
<div class="card-header">
<h3 class="card-title"><i class="fas fa-sticky-note"></i> My To-Do Notes</h3>
</div>
<div class="card-body p-0">
<ul class="list-group list-group-flush">
<?php if (!$todayTodos): ?>
<li class="list-group-item text-muted">No pending notes</li>
<?php endif; ?>
<?php foreach ($todayTodos as $n): ?>
<li class="list-group-item"><?= htmlspecialchars($n['title']) ?></li>
<?php endforeach; ?>
</ul>
</div>
<div class="card-footer text-right">
<a href="my_notes.php" class="btn btn-sm btn-primary">View All Notes</a>
</div>
</div>
</div>

<div class="col-md-6">
<div class="card">
<div class="card-header">
<h3 class="card-title"><i class="fas fa-chart-line"></i> Projects by Month (<?= date('Y') ?>)</h3>
</div>
<div class="card-body">
<canvas id="projectsByMonthChart" height="180"></canvas>
</div>
</div>
</div>
</div>

<!-- LATEST ACTIVITIES -->
<div class="card mt-4">
<div class="card-header"><h3 class="card-title">Latest Activities</h3></div>
<div class="card-body table-responsive p-0">
<table class="table table-hover">
<thead>
<tr><th>Project</th><th>Assigned To</th><th>Status</th><th>Created</th></tr>
</thead>
<tbody>
<?php if ($latestActivities && $latestActivities->num_rows):
while ($row = $latestActivities->fetch_assoc()): ?>
<tr onclick="location.href='project_view.php?id=<?= (int)$row['activity_id'] ?>'">
<td><?= htmlspecialchars($row['title']) ?></td>
<td><?= $row['assigned_to'] ?: '-' ?></td>
<td>
<span class="badge badge-<?=
$row['status']==='Completed'?'success':($row['status']==='Pending'?'warning':'info') ?>">
<?= htmlspecialchars($row['status']) ?>
</span>
</td>
<td><?= date('d M Y', strtotime($row['created_at'])) ?></td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="4" class="text-muted">No activities found.</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

</div>
</section>
</div>

<footer class="main-footer"><strong>Monitoring System</strong></footer>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById('projectsByMonthChart'), {
  type: 'line',
  data: {
    labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
    datasets: [{
      label: 'Projects',
      data: <?= json_encode(array_values($projectsByMonth)) ?>,

      /* 🔥 MAGENTA THEME */
      borderColor: '#ff0090',
      backgroundColor: 'rgba(255,0,144,0.20)',
      pointBackgroundColor: '#ff0090',
      pointBorderColor: '#ffffff',
      pointHoverBackgroundColor: '#ffffff',
      pointHoverBorderColor: '#ff0090',

      borderWidth: 3,
      fill: true,
      tension: 0.35
    }]
  },
  options: {
    responsive: true,
    plugins: {
      legend: {
        labels: {
          color: '#ff0090',
          font: { weight: '600' }
        }
      }
    },
    scales: {
      x: {
        ticks: { color: '#ff0090' },
        grid: { display: false }
      },
      y: {
        beginAtZero: true,
        ticks: { color: '#ff0090' },
        grid: {
          color: 'rgba(255,0,144,0.15)'
        }
      }
    }
  }
});
</script>


</body>
</html>
