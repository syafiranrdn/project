<?php
include 'auth.php';
include '../database.php';
require_once __DIR__ . '/notifications/notifications_create.php';

/* =============================
   PAGE TITLE (FOR NAVBAR)
============================= */
$pageTitle = 'Activities';
$activePage = 'activities';

/* =============================
   ACCESS CONTROL
============================= */
if (isStaff()) die('Access denied');

$userId      = (int)($_SESSION['user_id'] ?? 0);
$currentDept = (int)($_SESSION['department_id'] ?? 0);

$isAdmin = isSystemAdmin();
$isHOD   = isHOD();

/* =============================
   DEPARTMENT FILTER
============================= */
$filterDeptId = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
if ($isHOD) $filterDeptId = $currentDept;

/* =============================
   LOAD DEPARTMENTS (ADMIN ONLY)
============================= */
$departments = [];
if ($isAdmin) {
    $res = $conn->query("
        SELECT department_id, department_name
        FROM departments
        WHERE status='Active'
        ORDER BY department_name
    ");
    while ($d = $res->fetch_assoc()) $departments[] = $d;
}

/* =============================
   WHERE CLAUSE
============================= */
$where  = '';
$params = [];
$types  = '';

if ($filterDeptId > 0) {
    $where = ' AND a.department_id = ? ';
    $params[] = $filterDeptId;
    $types .= 'i';
}

/* =============================
   ACTIVE ACTIVITIES
============================= */
$sqlActive = "
SELECT 
    a.activity_id,
    a.title,
    a.status,
    a.created_at,
    a.department_id,
    d.department_name,
    IFNULL(
        ROUND(
            (SUM(CASE WHEN s.status='Completed' THEN 1 ELSE 0 END)
            / NULLIF(COUNT(s.subtask_id),0)) * 100
        ),0
    ) AS progress,
    GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') AS assigned_to
FROM activities a
LEFT JOIN departments d ON d.department_id = a.department_id
LEFT JOIN activity_subtasks s ON s.activity_id = a.activity_id
LEFT JOIN activity_users au ON au.activity_id = a.activity_id
LEFT JOIN users u ON u.user_id = au.user_id
WHERE a.status != 'Completed'
{$where}
GROUP BY a.activity_id
ORDER BY a.created_at DESC
";

$stmt = $conn->prepare($sqlActive);
if ($types) $stmt->bind_param($types, ...$params);
$stmt->execute();
$activeActivities = $stmt->get_result();

/* =============================
   COMPLETED ACTIVITIES
============================= */
$sqlCompleted = "
SELECT 
    a.activity_id,
    a.title,
    a.created_at,
    a.department_id,
    d.department_name,
    GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') AS assigned_to
FROM activities a
LEFT JOIN departments d ON d.department_id = a.department_id
LEFT JOIN activity_users au ON au.activity_id = a.activity_id
LEFT JOIN users u ON u.user_id = au.user_id
WHERE a.status = 'Completed'
{$where}
GROUP BY a.activity_id
ORDER BY a.created_at DESC
";

$stmt2 = $conn->prepare($sqlCompleted);
if ($types) $stmt2->bind_param($types, ...$params);
$stmt2->execute();
$completedActivities = $stmt2->get_result();

/* =============================
   PERMISSION CHECK
============================= */
function canManageActivity(int $activityDeptId, bool $isAdmin, int $currentDept): bool {
    return $isAdmin || ($activityDeptId === $currentDept);
}

$themeClass = (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Activities</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
/* ===============================
   MAGENTA PASTEL THEME
=============================== */
:root{
  --magenta:#ff0090;
  --magenta-soft:#f49ac2;
  --salmon:#ff9999;
  --black:#000;
}

/* HEADERS */
.content-header h1{
  color:var(--magenta);
  font-weight:600;
}

/* CARDS */
.card{
  border-top:3px solid var(--magenta);
}
.card-header{
  background:#fff;
  border-bottom:1px solid var(--magenta-soft);
}

/* TABLE */
.table thead th{
  background:var(--black);
  color:var(--magenta);
  border:none;
}
.table tbody tr{
  cursor:pointer;
  transition:background .2s ease;
}
.table tbody tr:hover{
  background:rgba(244,154,194,.25);
}
.table td{
  vertical-align:middle;
}

/* ACTION DISABLED */
.action-disabled{
  pointer-events:none;
  opacity:.45;
  filter:grayscale(1);
}

/* BUTTONS */
.btn-primary{
  background:var(--magenta);
  border-color:var(--magenta);
}
.btn-primary:hover{
  background:#e60080;
  border-color:#e60080;
}

.btn-warning{
  background:var(--magenta-soft);
  border-color:var(--magenta-soft);
  color:#000;
}
.btn-warning:hover{
  background:#f080b3;
}

.btn-success{
  background:var(--magenta);
  border-color:var(--magenta);
}
.btn-success:hover{
  background:#e60080;
}

.btn-danger{
  background:var(--salmon);
  border-color:var(--salmon);
  color:#000;
}
.btn-danger:hover{
  background:#ff7f7f;
}

/* COMPLETED TITLE */
.text-success{
  color:var(--magenta)!important;
}

/* FILTER SELECT */
select.form-control{
  border:1px solid var(--magenta-soft);
}
select.form-control:focus{
  border-color:var(--magenta);
  box-shadow:0 0 0 .1rem rgba(255,0,144,.25);
}

/* FOOTER */
.main-footer{
  border-top:1px solid var(--magenta-soft);
}
</style>
</head>

<body class="hold-transition sidebar-mini layout-fixed <?= $themeClass ?>">
<div class="wrapper">

<?php include 'partials/navbar.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<div class="content-wrapper">

<section class="content-header">
<div class="container-fluid d-flex justify-content-between align-items-center">
  <h1 class="mb-0">Activities</h1>

  <?php if ($isAdmin): ?>
  <form method="GET">
    <select name="dept" class="form-control form-control-sm" onchange="this.form.submit()">
      <option value="0">All Departments</option>
      <?php foreach ($departments as $d): ?>
      <option value="<?= (int)$d['department_id'] ?>" <?= $filterDeptId===(int)$d['department_id']?'selected':'' ?>>
        <?= htmlspecialchars($d['department_name']) ?>
      </option>
      <?php endforeach; ?>
    </select>
  </form>
  <?php endif; ?>
</div>
</section>

<section class="content">
<div class="container-fluid">

<!-- ACTIVE ACTIVITIES -->
<div class="card">
  <div class="card-header"><h3 class="card-title">Active Activities</h3></div>
  <div class="card-body table-responsive p-0">
    <table class="table table-hover table-clickable">
      <thead>
        <tr>
          <th>Title</th>
          <th>Department</th>
          <th>Assigned</th>
          <th>Status</th>
          <th>Progress</th>
          <th>Created</th>
          <th width="260">Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($activeActivities && $activeActivities->num_rows): ?>
        <?php while ($r = $activeActivities->fetch_assoc()):
          $canManage = canManageActivity((int)$r['department_id'], $isAdmin, $currentDept); ?>
          <tr data-id="<?= (int)$r['activity_id'] ?>" id="row-<?= (int)$r['activity_id'] ?>">
            <td><?= htmlspecialchars($r['title']) ?></td>
            <td><?= htmlspecialchars($r['department_name'] ?? '-') ?></td>
            <td><?= $r['assigned_to'] ?: '-' ?></td>
            <td><?= htmlspecialchars($r['status']) ?></td>
            <td><?= (int)$r['progress'] ?>%</td>
            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            <td onclick="event.stopPropagation()">
              <a href="project_view.php?id=<?= (int)$r['activity_id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>
              <a href="edit_activity.php?id=<?= (int)$r['activity_id'] ?>" class="btn btn-sm btn-warning <?= $canManage?'':'action-disabled' ?>"><i class="fas fa-edit"></i></a>
              <button class="btn btn-sm btn-success <?= $canManage?'':'action-disabled' ?>" onclick="markCompleted(event,<?= (int)$r['activity_id'] ?>)"><i class="fas fa-check"></i></button>
              <button class="btn btn-sm btn-danger <?= $canManage?'':'action-disabled' ?>" onclick="deleteActivity(event,<?= (int)$r['activity_id'] ?>)"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="7" class="text-muted p-3">No active activities found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- COMPLETED ACTIVITIES -->
<div class="card mt-4">
  <div class="card-header"><h3 class="card-title text-success">Completed Activities</h3></div>
  <div class="card-body table-responsive p-0">
    <table class="table table-hover table-clickable">
      <thead>
        <tr>
          <th>Title</th>
          <th>Department</th>
          <th>Assigned</th>
          <th>Completed</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($completedActivities && $completedActivities->num_rows): ?>
        <?php while ($r = $completedActivities->fetch_assoc()):
          $canManage = canManageActivity((int)$r['department_id'], $isAdmin, $currentDept); ?>
          <tr data-id="<?= (int)$r['activity_id'] ?>" id="row-<?= (int)$r['activity_id'] ?>">
            <td><?= htmlspecialchars($r['title']) ?></td>
            <td><?= htmlspecialchars($r['department_name'] ?? '-') ?></td>
            <td><?= $r['assigned_to'] ?: '-' ?></td>
            <td><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            <td onclick="event.stopPropagation()">
              <a href="project_view.php?id=<?= (int)$r['activity_id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a>
              <button class="btn btn-sm btn-danger <?= $canManage?'':'action-disabled' ?>" onclick="deleteActivity(event,<?= (int)$r['activity_id'] ?>)"><i class="fas fa-trash"></i></button>
            </td>
          </tr>
        <?php endwhile; ?>
      <?php else: ?>
        <tr><td colspan="5" class="text-muted p-3">No completed activities found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
</section>
</div>

<footer class="main-footer">Monitoring System</footer>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
document.querySelectorAll('tr[data-id]').forEach(r=>{
  r.addEventListener('click',()=>location.href='project_view.php?id='+r.dataset.id);
});

function markCompleted(e,id){
  e.preventDefault(); e.stopPropagation();
  if(!confirm('Mark this activity as completed?')) return;
  fetch('activities_api/mark_completed.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'id='+encodeURIComponent(id)
  }).then(r=>r.text()).then(t=>{
    if(t.trim()==='OK') location.reload();
    else alert(t);
  });
}

function deleteActivity(e,id){
  e.preventDefault(); e.stopPropagation();
  if(!confirm('Delete this activity permanently?')) return;
  fetch('activities_api/delete_activity.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'id='+encodeURIComponent(id)
  }).then(r=>r.json()).then(d=>{
    if(d && d.success){
      document.getElementById('row-'+id)?.remove();
    } else {
      alert((d && d.error) ? d.error : 'Delete failed');
    }
  });
}
</script>

</body>
</html>
