<?php
include 'auth.php';
include '../database.php';

/* =============================
   ACCESS CONTROL
============================= */
if (isStaff()) {
    die('Access denied');
}

/* =============================
   PAGE CONTEXT
============================= */
$activePage = 'department_activities';

/* =============================
   VALIDATE DEPARTMENT
============================= */
if (!isset($_GET['dept_id']) || !ctype_digit($_GET['dept_id'])) {
    die('Invalid department');
}

$deptId = (int)$_GET['dept_id'];

/* =============================
   LOAD DEPARTMENT
============================= */
$stmt = $conn->prepare(
    "SELECT department_name 
     FROM departments 
     WHERE department_id = ? AND status = 'Active'
     LIMIT 1"
);
$stmt->bind_param("i", $deptId);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    die('Department not found');
}

$dept = $res->fetch_assoc();
$stmt->close();

/* =============================
   PERMISSION
============================= */
$canManage = canManageActivityDepartment($deptId);

/* =============================
   LOAD ACTIVITIES
============================= */
$activities = $conn->prepare(
    "SELECT activity_id, title, status, created_at
     FROM activities
     WHERE department_id = ?
     ORDER BY created_at DESC"
);
$activities->bind_param("i", $deptId);
$activities->execute();
$activityRes = $activities->get_result();

/* =============================
   UI FLAGS
============================= */
$readOnly = !$canManage;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($dept['department_name']) ?> - Activities</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

<?php include 'partials/navbar.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<div class="content-wrapper">

<section class="content-header">
<div class="container-fluid d-flex justify-content-between align-items-center">
  <h1><?= htmlspecialchars($dept['department_name']) ?> Department</h1>

  <?php if ($readOnly) { ?>
    <span class="badge badge-secondary">
      View only
    </span>
  <?php } ?>
</div>
</section>

<section class="content">
<div class="container-fluid">

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <h3 class="card-title">Activities</h3>

    <?php if ($canManage) { ?>
      <a href="add_activity.php?dept_id=<?= $deptId ?>"
         class="btn btn-sm btn-primary">
        <i class="fas fa-plus"></i> Add Activity
      </a>
    <?php } ?>
  </div>

  <div class="card-body p-0">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>Title</th>
          <th>Status</th>
          <th>Created</th>
          <th width="140">Action</th>
        </tr>
      </thead>
      <tbody>

<?php if ($activityRes->num_rows > 0) { ?>
<?php while ($a = $activityRes->fetch_assoc()) { ?>
<tr>
  <td><?= htmlspecialchars($a['title']) ?></td>

  <td>
    <span class="badge badge-<?=
      $a['status'] === 'Completed' ? 'success' :
      ($a['status'] === 'Pending' ? 'warning' : 'info')
    ?>">
      <?= htmlspecialchars($a['status']) ?>
    </span>
  </td>

  <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>

  <td>
    <!-- VIEW ALWAYS -->
    <a href="project_view.php?id=<?= (int)$a['activity_id'] ?>"
       class="btn btn-sm btn-primary">
      <i class="fas fa-eye"></i>
    </a>

    <!-- EDIT ONLY IF CAN MANAGE -->
    <a href="edit_activity.php?id=<?= (int)$a['activity_id'] ?>"
       class="btn btn-sm btn-warning <?= $canManage ? '' : 'disabled' ?>"
       title="<?= $canManage ? 'Edit' : 'No permission' ?>">
      <i class="fas fa-edit"></i>
    </a>
  </td>
</tr>
<?php } ?>
<?php } else { ?>
<tr>
  <td colspan="4" class="text-muted text-center p-3">
    No activities found.
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
</body>
</html>
