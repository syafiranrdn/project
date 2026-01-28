<?php 
include 'auth.php';
include '../database.php';

/* =============================
   VALIDATE ID
============================= */
if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) {
    die('Invalid activity ID');
}
$activity_id = (int)$_GET['id'];

/* =============================
   SMART BACK URL
============================= */
$backUrl = 'activities.php';
if (!empty($_SERVER['HTTP_REFERER']) &&
    strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) !== false) {
    $backUrl = $_SERVER['HTTP_REFERER'];
}

/* =============================
   FETCH ACTIVITY
============================= */
$stmt = $conn->prepare("
    SELECT a.*, d.department_name
    FROM activities a
    LEFT JOIN departments d ON d.department_id = a.department_id
    WHERE a.activity_id = ?
    LIMIT 1
");
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$activity) die('Activity not found');

/* =============================
   FETCH ASSIGNED USERS
============================= */
$assignedUsers = [];
$res = $conn->query("
    SELECT u.name
    FROM activity_users au
    JOIN users u ON u.user_id = au.user_id
    WHERE au.activity_id = {$activity_id}
    ORDER BY u.name
");
while ($r = $res->fetch_assoc()) {
    $assignedUsers[] = $r['name'];
}

/* =============================
   FETCH SUBTASKS
============================= */
$subtasks = [];
$res = $conn->query("
    SELECT title, status
    FROM activity_subtasks
    WHERE activity_id = {$activity_id}
    ORDER BY subtask_id
");
while ($r = $res->fetch_assoc()) {
    $subtasks[] = $r;
}

/* =============================
   CALCULATE PROGRESS
============================= */
$totalSub = count($subtasks);
$doneSub  = 0;
foreach ($subtasks as $s) {
    if ($s['status'] === 'Completed') $doneSub++;
}
$progress = ($totalSub > 0) ? round(($doneSub / $totalSub) * 100) : 0;

$themeClass = (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '';
$pageTitle  = 'Project Details';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Project Details</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
/* =====================================================
   🔥 STANDARD HEADER – MATCH DASHBOARD / ACTIVITY
===================================================== */
.card-header{
  background:#100c08;
  color:#ff0090;
  border-bottom:1px solid rgba(255,0,144,.35);
}
.card-title{
  font-weight:600;
}

/* FORCE EDIT ACTIVITY BORDER COLOR */
.card-outline.card-primary{
  border-top: 3px solid #ff0090 !important; /* magenta */
}


/* STATUS BADGE (KEEP FUNCTION, STANDARD COLOR) */
.badge-success,
.badge-info,
.badge-warning{
  background:linear-gradient(
    90deg,
    #ff0090,
    #f49ac2
  ) !important;
  color:#fff!important;
}

/* PROGRESS BAR */
.progress{
  height:18px;
  border-radius:10px;
  background:#f1f1f1;
}
.progress-bar{
  background:linear-gradient(
    90deg,
    #ff0090,
    #f49ac2
  ) !important;
  font-weight:600;
}

/* DESCRIPTION BOX */
.bg-light{
  background:rgba(255,0,144,.06)!important;
  border:1px solid rgba(255,0,144,.35);
}

/* SUBTASK LIST */
.list-group-item:hover{
  background:rgba(244,154,194,.18);
}

/* ICONS */
.fa-check-circle{
  color:#ff0090!important;
}

/* BACK BUTTON */
.btn-secondary{
  background:transparent;
  border:1px solid #ff0090;
  color:#ff0090;
}
.btn-secondary:hover{
  background:#f49ac2;
  color:#000;
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
  <h1>Project Details</h1>
</div>
</section>

<section class="content">
<div class="container-fluid">

<div class="card card-outline card-primary">

<div class="card-header">
  <h3 class="card-title">
    <i class="fas fa-folder-open mr-1"></i>
    <?= htmlspecialchars($activity['title']) ?>
  </h3>
</div>

<div class="card-body">

<div class="mb-3">
<span class="badge badge-<?=
  $activity['status']==='Completed'?'success':
  ($activity['status']==='In Progress'?'info':'warning')
?>">
<?= htmlspecialchars($activity['status']) ?>
</span>
</div>

<div class="mb-4">
<label>Progress</label>
<div class="progress">
  <div class="progress-bar" style="width:<?= $progress ?>%">
    <?= $progress ?>%
  </div>
</div>
</div>

<hr>

<p><strong>Department:</strong>
<?= htmlspecialchars($activity['department_name'] ?? '—') ?>
</p>

<p><strong>Created At:</strong>
<?= date('d M Y', strtotime($activity['created_at'])) ?>
</p>

<hr>

<p><strong>Assigned Staff</strong></p>
<?php if ($assignedUsers): ?>
<ul class="list-unstyled">
<?php foreach ($assignedUsers as $name): ?>
<li><i class="fas fa-user mr-1"></i> <?= htmlspecialchars($name) ?></li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p class="text-muted">No staff assigned</p>
<?php endif; ?>

<hr>

<p><strong>Description</strong></p>
<div class="border rounded p-3 bg-light mb-4">
<?= nl2br(htmlspecialchars($activity['description'])) ?>
</div>

<hr>

<p><strong>Subtasks</strong></p>
<?php if ($subtasks): ?>
<ul class="list-group">
<?php foreach ($subtasks as $s): ?>
<li class="list-group-item d-flex align-items-center">
<i class="fas <?= $s['status']==='Completed'
  ? 'fa-check-circle'
  : 'fa-circle text-muted' ?> mr-2"></i>
<?= htmlspecialchars($s['title']) ?>
</li>
<?php endforeach; ?>
</ul>
<?php else: ?>
<p class="text-muted">No subtasks available</p>
<?php endif; ?>

</div>

<div class="card-footer d-flex justify-content-end">
<a href="<?= htmlspecialchars($backUrl) ?>" class="btn btn-secondary">
<i class="fas fa-arrow-left"></i> Back
</a>
</div>

</div>
</div>
</section>
</div>

<footer class="main-footer">
<strong>Monitoring System</strong>
</footer>

</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>
</body>
</html>
