<?php
include 'auth.php';
include '../database.php';
require_once __DIR__ . '/notifications/notifications_create.php';


$user_id = (int)$_SESSION['user_id'];

/* =============================
   FETCH ACTIVE (ASSIGNED) ACTIVITIES
============================= */
$activeActivities = mysqli_query($conn, "
SELECT 
    a.*,
    IFNULL(
        ROUND((SUM(s.is_done) / NULLIF(COUNT(s.subtask_id),0)) * 100),
        0
    ) AS auto_progress
FROM activities a
JOIN activity_users au 
    ON a.activity_id = au.activity_id
LEFT JOIN activity_subtasks s
    ON a.activity_id = s.activity_id
WHERE au.user_id = $user_id
  AND a.status != 'Completed'
GROUP BY a.activity_id
ORDER BY a.created_at DESC
");

/* =============================
   FETCH COMPLETED (ASSIGNED) ACTIVITIES
============================= */
$completedActivities = mysqli_query($conn, "
SELECT 
    a.*
FROM activities a
JOIN activity_users au 
    ON a.activity_id = au.activity_id
WHERE au.user_id = $user_id
  AND a.status = 'Completed'
ORDER BY a.completed_at DESC
");

$themeClass = (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '';
$pageTitle  = 'My Activities';
$activePage = 'my_activities';

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>My Activities | Monitoring System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
.table-clickable tbody tr {
  cursor: pointer;
}
.table-clickable tbody tr:hover {
  background-color: #ff5ca8 !important;
  color: #fff;
}
.table-clickable tbody tr:hover .badge,
.table-clickable tbody tr:hover small {
  color: #fff;
}
</style>
</head>

<body class="hold-transition sidebar-mini layout-fixed <?= $themeClass ?>">
<div class="wrapper">

<?php
include 'partials/navbar.php';
include 'partials/sidebar.php';
?>

<div class="content-wrapper">

<section class="content-header">
<div class="container-fluid">
  <h1>My Activities</h1>
</div>
</section>

<section class="content">
<div class="container-fluid">

<!-- ================= ACTIVE ACTIVITIES ================= -->
<div class="card">
<div class="card-header">
  <h3 class="card-title">Assigned Activities</h3>
</div>

<div class="card-body table-responsive p-0">
<table class="table table-hover table-clickable">
<thead>
<tr>
  <th>Title</th>
  <th>Status</th>
  <th>Priority</th>
  <th>Progress</th>
  <th>Created</th>
  <th width="220">Actions</th>
</tr>
</thead>
<tbody>

<?php if ($activeActivities && mysqli_num_rows($activeActivities) > 0) {
while ($a = mysqli_fetch_assoc($activeActivities)) { ?>
<tr class="clickable-row" data-id="<?= $a['activity_id'] ?>">

<td><?= htmlspecialchars($a['title']) ?></td>

<td>
<span class="badge badge-<?= 
  $a['status']=='Pending'?'warning':'info' ?>">
<?= $a['status'] ?>
</span>
</td>

<td><?= htmlspecialchars($a['priority']) ?></td>

<td>
<div class="progress progress-sm">
  <div class="progress-bar bg-info"
       style="width:<?= (int)$a['auto_progress'] ?>%"></div>
</div>
<small><?= (int)$a['auto_progress'] ?>%</small>
</td>

<td><?= date('d M Y', strtotime($a['created_at'])) ?></td>

<td onclick="event.stopPropagation()">

<a href="project_view.php?id=<?= $a['activity_id'] ?>"
   class="btn btn-sm btn-primary">
<i class="fas fa-eye"></i>
</a>

<a href="edit_activity.php?id=<?= $a['activity_id'] ?>"
   class="btn btn-sm btn-warning">
<i class="fas fa-edit"></i>
</a>

<button type="button"
        class="btn btn-sm btn-success"
        onclick="markCompleted(event,<?= $a['activity_id'] ?>)">
<i class="fas fa-check"></i>
</button>

</td>
</tr>
<?php }} else { ?>
<tr>
<td colspan="6" class="text-muted p-3">
  No active activities assigned to you.
</td>
</tr>
<?php } ?>

</tbody>
</table>
</div>
</div>

<!-- ================= COMPLETED ACTIVITIES ================= -->
<div class="card mt-4">
<div class="card-header">
<h3 class="card-title text-success">
<i class="fas fa-check-circle mr-1"></i> Completed Activities
</h3>
</div>

<div class="card-body table-responsive p-0">
<table class="table table-hover table-clickable">
<thead>
<tr>
  <th>Title</th>
  <th>Priority</th>
  <th>Completed</th>
  <th width="140">Actions</th>
</tr>
</thead>
<tbody>

<?php if ($completedActivities && mysqli_num_rows($completedActivities) > 0) {
while ($c = mysqli_fetch_assoc($completedActivities)) { ?>
<tr class="clickable-row" data-id="<?= $c['activity_id'] ?>">

<td><?= htmlspecialchars($c['title']) ?></td>
<td><?= htmlspecialchars($c['priority']) ?></td>
<td><?= date('d M Y', strtotime($c['completed_at'])) ?></td>

<td onclick="event.stopPropagation()">
<a class="btn btn-sm btn-outline-success"
   href="project_view.php?id=<?= $c['activity_id'] ?>">
<i class="fas fa-eye"></i>
</a>
</td>
</tr>
<?php }} else { ?>
<tr>
<td colspan="4" class="text-muted p-3">
  No completed activities yet.
</td>
</tr>
<?php } ?>

</tbody>
</table>
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

<script>
// 🔗 ROW CLICK → VIEW
document.querySelectorAll('.clickable-row').forEach(row => {
  row.addEventListener('click', function () {
    window.location.href = 'project_view.php?id=' + this.dataset.id;
  });
});

// ✅ MARK COMPLETED
function markCompleted(e, id) {
  e.preventDefault();
  e.stopPropagation();

  if (!confirm('Mark this activity as completed?')) return;

  fetch('activities_api/mark_completed.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'id=' + encodeURIComponent(id)
  })
  .then(res => res.text())
  .then(resp => {
    if (resp.trim() === 'OK') {
      location.reload();
    } else {
      alert('Failed to mark completed');
    }
  })
  .catch(() => alert('Server error'));
}
</script>

</body>
</html>
