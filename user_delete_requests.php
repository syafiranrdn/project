<?php
require_once 'auth.php';
require_once '../database.php';
require_once __DIR__ . '/notifications/notifications_create.php';

/* =============================
   PAGE META (FIXED)
============================= */
$pageTitle  = 'User Delete Requests';
$activePage = 'user_delete_requests';

/* =============================
   ACCESS CONTROL
============================= */
if (!isSystemAdmin()) die('Access denied');

/* =============================
   FETCH PENDING REQUESTS
============================= */
$sql = "
    SELECT 
        r.request_id,
        r.reason,
        r.created_at,
        r.target_user_id,
        r.target_user_name,
        u.name AS requested_by_name
    FROM user_delete_requests r
    INNER JOIN users u ON u.user_id = r.requested_by
    WHERE r.status = 'Pending'
    ORDER BY r.created_at DESC
";
$pending = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">


<style>

/* =====================================================
   USER DELETE REQUESTS – PASTEL MAGENTA THEME
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
   FORCE REMOVE ADMINLTE BLUE / YELLOW
===================================================== */
.bg-info,
.bg-warning,
.bg-primary,
.badge-info,
.badge-warning,
.text-info,
.text-warning,
.card-primary > .card-header{
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
   CARD BASE
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

/* icon in title */
.card-title i{
  color:var(--magenta);
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

/* Reason text */
.table td:nth-child(4){
  color:#555;
}

/* =====================================================
   ACTION BUTTONS
===================================================== */
.btn{
  border-radius:8px;
  font-weight:600;
}

/* APPROVE (DELETE USER) */
.btn-danger{
  background:transparent;
  border:1.5px solid var(--salmon);
  color:var(--salmon);
}
.btn-danger:hover{
  background:var(--salmon);
  color:#000;
}

/* REJECT */
.btn-secondary{
  background:rgba(255,0,144,.12);
  border:1px solid var(--magenta-soft);
  color:#666;
}
.btn-secondary:hover{
  background:var(--magenta-soft);
  color:#000;
}

/* Processing state */
button:disabled{
  opacity:.6;
  cursor:not-allowed;
}

/* =====================================================
   EMPTY STATE
===================================================== */
#noPendingRow td{
  background:#fff;
  color:#999;
  font-style:italic;
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

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

<?php include 'partials/navbar.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<div class="content-wrapper">

<section class="content-header">
  <div class="container-fluid">
    <h1>User Delete Requests</h1>
  </div>
</section>

<section class="content">
<div class="container-fluid">

<div class="card">
  <div class="card-header">
    <h3 class="card-title">
      <i class="fas fa-exclamation-circle text-warning"></i>
      Pending Requests
    </h3>
  </div>

  <div class="card-body table-responsive p-0">
    <table class="table table-hover">
      <thead>
        <tr>
          <th style="width:70px">#</th>
          <th>Target User</th>
          <th>Requested By</th>
          <th>Reason</th>
          <th>Date</th>
          <th style="width:200px">Action</th>
        </tr>
      </thead>
      <tbody id="pending-body">

<?php if ($pending && mysqli_num_rows($pending) > 0): ?>
<?php while ($r = mysqli_fetch_assoc($pending)): ?>
<tr id="req-<?= (int)$r['request_id'] ?>">
  <td><?= (int)$r['request_id'] ?></td>
  <td><?= htmlspecialchars($r['target_user_name']) ?></td>
  <td><?= htmlspecialchars($r['requested_by_name']) ?></td>
  <td><?= nl2br(htmlspecialchars($r['reason'])) ?></td>
  <td><?= date('d M Y H:i', strtotime($r['created_at'])) ?></td>
  <td>
    <button
      class="btn btn-sm btn-danger"
      onclick="approveRequest(<?= (int)$r['request_id'] ?>, <?= (int)$r['target_user_id'] ?>)">
      <i class="fas fa-check"></i> Approve
    </button>

    <button
      class="btn btn-sm btn-secondary"
      onclick="rejectRequest(<?= (int)$r['request_id'] ?>)">
      <i class="fas fa-times"></i> Reject
    </button>
  </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr id="noPendingRow">
  <td colspan="6" class="text-muted text-center p-3">
    No pending requests
  </td>
</tr>
<?php endif; ?>

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

<!-- =============================
     FIXED JAVASCRIPT
============================= -->
<script>
function removeRowIfEmpty() {
  const tbody = document.getElementById('pending-body');
  if (!tbody.querySelector('tr[id^="req-"]')) {
    tbody.innerHTML = `
      <tr id="noPendingRow">
        <td colspan="6" class="text-muted text-center p-3">
          No pending requests
        </td>
      </tr>
    `;
  }
}

function disableButtons(row) {
  row.querySelectorAll('button').forEach(btn => {
    btn.disabled = true;
    btn.innerHTML = 'Processing...';
  });
}

function approveRequest(requestId, userId) {
  if (!confirm('Approve and delete this user?')) return;

  const row = document.getElementById('req-' + requestId);
  disableButtons(row);

  fetch('users_api/approve_delete_user.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'request_id=' + requestId + '&user_id=' + userId
  })
  .then(r => r.json())
  .then(res => {
    if (!res.success) {
      alert(res.error || 'Approve failed');
      return;
    }
    row.remove();
    removeRowIfEmpty();
  })
  .catch(() => alert('Approve failed'));
}

function rejectRequest(requestId) {
  if (!confirm('Reject this request?')) return;

  const row = document.getElementById('req-' + requestId);
  disableButtons(row);

  fetch('users_api/reject_delete_user.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: 'request_id=' + requestId
  })
  .then(r => r.json())
  .then(res => {
    if (!res.success) {
      alert(res.error || 'Reject failed');
      return;
    }
    row.remove();
    removeRowIfEmpty();
  })
  .catch(() => alert('Reject failed'));
}
</script>

</body>
</html>
