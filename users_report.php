<?php 
require_once 'auth.php';
require_once '../database.php';

if (isStaff()) {
    die('Access denied');
}

$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$departmentId  = (int)($_SESSION['department_id'] ?? 0);

$isAdmin = isSystemAdmin();
$isHOD   = isHOD();

$pageTitle  = 'Users Report';
$activePage = 'users_report';
$themeClass = (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '';

/* =====================================================
   USERS LIST
===================================================== */
$params = [];
$types  = '';

$sql = "
SELECT
    u.user_id,
    u.name,
    u.email,
    u.role,
    u.status,
    d.department_name,
    dr.status AS delete_status
FROM users u
LEFT JOIN departments d ON d.department_id = u.department_id

LEFT JOIN (
    SELECT r1.*
    FROM user_delete_requests r1
    INNER JOIN (
        SELECT target_user_id, MAX(request_id) AS max_id
        FROM user_delete_requests
        GROUP BY target_user_id
    ) r2 ON r1.request_id = r2.max_id
) dr ON dr.target_user_id = u.user_id
";

/* HOD view */
if ($isHOD && !$isAdmin) {
    $sql .= "
        WHERE u.department_id = ?
          AND u.status != 'Deleted'
    ";
    $params[] = $departmentId;
    $types .= 'i';
}

/* Admin / Super Admin view */
if ($isAdmin) {
    $sql .= " WHERE u.status != 'Deleted' ";
}

$sql .= " ORDER BY u.name ASC ";

$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$users = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Users Report</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
.you-label {
  color:#888;
  font-size:12px;
}


/* =====================================================
   USERS REPORT – PASTEL MAGENTA THEME (NO BLUE)
===================================================== */
:root{
  --magenta:#ff0090;
  --magenta-strong:#e60080;
  --magenta-soft:#f49ac2;
  --magenta-pastel:#fde6ef;
  --magenta-glass:rgba(255,0,144,.06);
  --salmon:#ff9999;
  --text-dark:#222;
}

/* =====================================================
   🔥 FORCE REMOVE ADMINLTE BLUE
===================================================== */
.bg-info,
.bg-primary,
.badge-info,
.text-info,
.card-primary > .card-header,
.card-info > .card-header{
  background:var(--magenta-pastel) !important;
  color:var(--text-dark) !important;
  border-color:var(--magenta-soft) !important;
}

/* =====================================================
   PAGE TITLE
===================================================== */
.content-header h1{
  color:var(--magenta);
  font-weight:600;
}

/* =====================================================
   CARD (WRAPPER)
===================================================== */
.card{
  background:var(--magenta-pastel);
  border-radius:14px;
  border:1px solid var(--magenta-soft);
  box-shadow:0 10px 26px rgba(255,0,144,.14);
}

.card-header{
  background:transparent;
  border-bottom:1px solid var(--magenta-soft);
}

.card-title{
  color:var(--magenta);
  font-weight:600;
}

/* =====================================================
   TABLE
===================================================== */
.table{
  margin-bottom:0;
}

.table thead th{
  background:rgba(255,0,144,.08);
  color:var(--magenta);
  border-bottom:1px solid var(--magenta-soft);
  font-weight:600;
}

.table tbody tr{
  background:#fff;
}

.table-hover tbody tr:hover{
  background:rgba(255,0,144,.06);
}

/* =====================================================
   BADGES (ROLE)
===================================================== */
.badge{
  padding:.45em .65em;
  font-weight:600;
  border-radius:8px;
}

/* Staff */
.badge-info{
  background:rgba(255,0,144,.15) !important;
  color:var(--magenta-strong) !important;
}

/* HOD */
.badge-warning{
  background:rgba(244,154,194,.35);
  color:#7a0044;
}

/* Admin */
.badge-danger{
  background:rgba(255,153,153,.35);
  color:#7a1f1f;
}

/* =====================================================
   STATUS TEXT
===================================================== */
td:nth-child(6){
  font-weight:600;
  color:#555;
}

/* =====================================================
   ACTION BUTTONS
===================================================== */
.btn{
  border-radius:8px;
  font-weight:600;
}

/* Delete / Request Delete */
.btn-danger{
  background:transparent;
  border:1px solid var(--salmon);
  color:var(--salmon);
}
.btn-danger:hover{
  background:var(--salmon);
  color:#000;
}

/* Requested (disabled) */
.btn-secondary{
  background:rgba(255,0,144,.12);
  border:1px solid var(--magenta-soft);
  color:#888;
}

/* Re-request */
.btn-warning{
  background:rgba(244,154,194,.35);
  border:1px solid var(--magenta-soft);
  color:#7a0044;
}
.btn-warning:hover{
  background:var(--magenta-soft);
  color:#000;
}

/* =====================================================
   "(you)" LABEL
===================================================== */
.you-label{
  color:#999;
  font-size:12px;
  margin-left:4px;
}

/* =====================================================
   EMPTY ACTION CELL
===================================================== */
td small.text-muted{
  color:#aaa !important;
}

/* =====================================================
   FOOTER
===================================================== */
.main-footer{
  border-top:1px solid var(--magenta-soft);
  background:#fff;
  color:#666;
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
  <h1>Users Report</h1>
</div>
</section>

<section class="content">
<div class="container-fluid">

<div class="card">
<div class="card-header">
  <h3 class="card-title">Registered Users</h3>
</div>

<div class="card-body table-responsive p-0">
<table class="table table-hover">
<thead>
<tr>
  <th>#</th>
  <th>Name</th>
  <th>Email</th>
  <th>Role</th>
  <th>Department</th>
  <th>Status</th>
  <th width="200">Action</th>
</tr>
</thead>
<tbody>

<?php while ($u = $users->fetch_assoc()): ?>
<?php
$isSelf = ((int)$u['user_id'] === $currentUserId);
?>
<tr id="userRow<?= (int)$u['user_id'] ?>">
<td><?= (int)$u['user_id'] ?></td>

<td>
<?= htmlspecialchars($u['name']) ?>
<?php if ($isSelf): ?>
  <span class="you-label">(you)</span>
<?php endif; ?>
</td>

<td><?= htmlspecialchars($u['email']) ?></td>

<td>
<?php
if ($u['role'] === 'Staff') {
    echo '<span class="badge badge-info">Staff</span>';
} elseif ($u['role'] === 'Admin' && $u['department_name']) {
    echo '<span class="badge badge-warning">HOD</span>';
} else {
    echo '<span class="badge badge-danger">Admin</span>';
}
?>
</td>

<td><?= htmlspecialchars($u['department_name'] ?? '-') ?></td>
<td><?= htmlspecialchars($u['status']) ?></td>

<td>

<?php
/* =====================================================
   ACTION LOGIC (FIXED)
===================================================== */

/* SUPER ADMIN: can delete anyone EXCEPT self */
if ($isAdmin && !$isSelf): ?>

    <button class="btn btn-danger btn-sm"
      onclick="deleteInactiveUser(<?= (int)$u['user_id'] ?>)">
      Delete
    </button>


<?php elseif ($isHOD && !$isAdmin && $u['role'] === 'Staff' && !$isSelf): ?>

    <?php if ($u['delete_status'] === 'Pending'): ?>
        <button class="btn btn-secondary btn-sm" disabled>Requested</button>

    <?php elseif ($u['delete_status'] === 'Rejected'): ?>
        <button class="btn btn-warning btn-sm"
          onclick="requestDeleteUser(<?= (int)$u['user_id'] ?>)">
          Re-request
        </button>

    <?php elseif ($u['delete_status'] === null): ?>
        <button class="btn btn-danger btn-sm"
          onclick="requestDeleteUser(<?= (int)$u['user_id'] ?>)">
          Request Delete
        </button>
    <?php endif; ?>

<?php else: ?>
    <small class="text-muted">—</small>
<?php endif; ?>

</td>
</tr>
<?php endwhile; ?>

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
function requestDeleteUser(id){
  const reason = prompt('Reason for deletion:');
  if(!reason) return;

  fetch('users_api/request_delete_user.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'id='+id+'&reason='+encodeURIComponent(reason)
  })
  .then(r=>r.json())
  .then(res=>{
    if(res.success) location.reload();
    else alert(res.error || 'Failed');
  });
}

function deleteInactiveUser(id){
  if(!confirm('Delete this user permanently?')) return;

  fetch('users_api/delete_user.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'id='+id
  })
  .then(r=>r.json())
  .then(res=>{
    if(res.success){
      const row = document.getElementById('userRow'+id);
      if(row) row.remove();
    } else {
      alert(res.error || 'Delete failed');
    }
  });
}
</script>

</body>
</html>
