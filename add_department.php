<?php
include 'auth.php';
include '../database.php';

/* =============================
   SUPER ADMIN ONLY
============================= */
if (!isSuperAdmin()) {
    die('Access denied');
}

/* =============================
   PAGE META
============================= */
$themeClass = (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '';
$pageTitle = 'Add Department';
$activePage = 'add_department';
$message    = '';

/* =============================
   ADD DEPARTMENT
============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_department'])) {

    $department_name = strtoupper(trim($_POST['department_name']));
    $department_name = mysqli_real_escape_string($conn, $department_name);

    if ($department_name === '') {
        $message = '<div class="alert alert-danger">Department name is required.</div>';
    } else {

        $check = mysqli_query(
            $conn,
            "SELECT department_id 
             FROM departments 
             WHERE department_name = '$department_name'
             LIMIT 1"
        );

        if ($check && mysqli_num_rows($check) > 0) {
            $message = '<div class="alert alert-warning">Department already exists.</div>';
        } else {
            if (mysqli_query(
                $conn,
                "INSERT INTO departments (department_name, status)
                 VALUES ('$department_name', 'Active')"
            )) {
                $message = '<div class="alert alert-success">Department added successfully.</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to add department.</div>';
            }
        }
    }
}

/* =============================
   UPDATE DEPARTMENT (INLINE)
============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_department'])) {

    $dept_id = (int)$_POST['department_id'];
    $new_name = strtoupper(trim($_POST['department_name']));
    $new_name = mysqli_real_escape_string($conn, $new_name);

    if ($new_name !== '') {
        mysqli_query(
            $conn,
            "UPDATE departments
             SET department_name = '$new_name'
             WHERE department_id = $dept_id"
        );
    }

    header('Location: add_department.php');
    exit;
}

/* =============================
   DELETE DEPARTMENT
============================= */
if (isset($_GET['delete']) && ctype_digit($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    mysqli_query($conn, "DELETE FROM departments WHERE department_id = $did");
    header('Location: add_department.php');
    exit;
}

/* =============================
   FETCH DEPARTMENTS
============================= */
$editId = isset($_GET['edit']) && ctype_digit($_GET['edit'])
    ? (int)$_GET['edit']
    : null;

$deptResult = mysqli_query(
    $conn,
    "SELECT department_id, department_name, status, created_at
     FROM departments
     ORDER BY created_at DESC"
);

/* =============================
   THEME
============================= */
$themeClass = (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Add Department</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro">
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
input[name="department_name"] {
    text-transform: uppercase;
}

/* =====================================================
   ADD DEPARTMENT – PASTEL MAGENTA THEME (NO BLUE)
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
   🔥 HARD KILL ADMINLTE BLUE
===================================================== */
.bg-primary,
.bg-info,
.card-primary:not(.card-outline) > .card-header,
.card-info:not(.card-outline) > .card-header,
.btn-info,
.badge-info,
.text-info{
  background:var(--magenta-pastel) !important;
  border-color:var(--magenta-soft) !important;
  color:var(--text-dark) !important;
}

/* =====================================================
   PAGE TITLE
===================================================== */
.content-header h1{
  color:var(--magenta);
  font-weight:600;
}

/* =====================================================
   CARD BASE (FORM + TABLE)
===================================================== */
.card{
  background:var(--magenta-pastel);
  border-radius:14px;
  border:1px solid var(--magenta-soft);
  box-shadow:0 10px 28px rgba(255,0,144,.14);
}

.card-header{
  background:transparent !important;
  border-bottom:1px solid var(--magenta-soft);
}

.card-title{
  color:var(--magenta);
  font-weight:600;
}

/* outline card fix */
.card.card-outline.card-primary{
  border:1px solid var(--magenta-soft) !important;
}

/* =====================================================
   FORM INPUT
===================================================== */
.form-control{
  border-radius:10px;
}

.form-control:focus{
  border-color:var(--magenta);
  box-shadow:0 0 0 .15rem rgba(255,0,144,.25);
}

/* =====================================================
   BUTTONS
===================================================== */
.btn-primary{
  background:linear-gradient(
    90deg,
    var(--magenta),
    var(--magenta-soft)
  );
  border:none;
  color:#fff;
}
.btn-primary:hover{
  opacity:.9;
}

/* edit (warning → pastel) */
.btn-warning{
  background:rgba(255,0,144,.15);
  border:1px solid var(--magenta-soft);
  color:var(--magenta);
}
.btn-warning:hover{
  background:var(--magenta-soft);
  color:#000;
}

/* delete */
.btn-danger{
  background:rgba(255,153,153,.2);
  border:1px solid var(--salmon);
  color:#c0392b;
}
.btn-danger:hover{
  background:var(--salmon);
  color:#000;
}

/* save / confirm */
.btn-success{
  background:rgba(255,0,144,.18);
  border:1px solid var(--magenta);
  color:var(--magenta);
}
.btn-success:hover{
  background:var(--magenta);
  color:#fff;
}

/* cancel */
.btn-secondary{
  background:transparent;
  border:1px solid var(--magenta-soft);
  color:var(--magenta);
}
.btn-secondary:hover{
  background:var(--magenta-soft);
  color:#000;
}

/* =====================================================
   TABLE
===================================================== */
.table{
  background:#fff;
  border-radius:12px;
  overflow:hidden;
}

.table thead th{
  background:var(--magenta-glass);
  color:var(--magenta);
  border-bottom:1px solid var(--magenta-soft);
}

.table td{
  vertical-align:middle;
}

.table-hover tbody tr:hover{
  background:rgba(255,0,144,.06);
}

/* =====================================================
   BADGE (STATUS)
===================================================== */
.badge-success{
  background:rgba(255,0,144,.18);
  color:var(--magenta);
  border:1px solid var(--magenta-soft);
  font-weight:600;
}

/* =====================================================
   ALERTS
===================================================== */
.alert-success{
  background:rgba(255,0,144,.14);
  border:1px solid var(--magenta-soft);
  color:var(--text-dark);
}

.alert-warning{
  background:rgba(244,154,194,.25);
  border:1px solid var(--magenta-soft);
  color:var(--text-dark);
}

.alert-danger{
  background:rgba(255,153,153,.25);
  border:1px solid var(--salmon);
  color:var(--text-dark);
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
  <h1>Add Department</h1>
</div>
</section>

<section class="content">
<div class="container-fluid">

<?= $message ?>

<!-- ADD FORM -->
<div class="card card-outline card-primary">
  <div class="card-header">
    <h3 class="card-title">New Department</h3>
  </div>

  <form method="POST" autocomplete="off">
    <div class="card-body">
      <div class="form-group">
        <label>Department Name</label>
        <input type="text"
               name="department_name"
               class="form-control"
               placeholder="E.G. IT, FINANCE, HR"
               required>
      </div>
    </div>

    <div class="card-footer">
      <button type="submit" name="add_department" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Department
      </button>
    </div>
  </form>
</div>

<!-- DEPARTMENT LIST -->
<div class="card mt-4">
  <div class="card-header">
    <h3 class="card-title">Department List</h3>
  </div>

  <div class="card-body p-0">
    <table class="table table-hover">
      <thead>
        <tr>
          <th>#</th>
          <th>Department</th>
          <th>Status</th>
          <th>Created</th>
          <th width="15%">Action</th>
        </tr>
      </thead>
      <tbody>

<?php
if ($deptResult instanceof mysqli_result && mysqli_num_rows($deptResult) > 0) {
    $i = 1;
    while ($d = mysqli_fetch_assoc($deptResult)) {
        $isEdit = ($editId === (int)$d['department_id']);
?>
<tr>
  <td><?= $i++ ?></td>

  <td>
    <?php if ($isEdit) { ?>
      <form method="POST" class="d-flex">
        <input type="hidden" name="department_id" value="<?= (int)$d['department_id'] ?>">
        <input type="text"
               name="department_name"
               value="<?= htmlspecialchars($d['department_name']) ?>"
               class="form-control form-control-sm mr-2"
               required>
    <?php } else { ?>
      <strong><?= htmlspecialchars($d['department_name']) ?></strong>
    <?php } ?>
  </td>

  <td>
    <span class="badge badge-success"><?= $d['status'] ?></span>
  </td>

  <td><?= date('d M Y', strtotime($d['created_at'])) ?></td>

  <td>
    <?php if ($isEdit) { ?>
        <button type="submit" name="update_department"
                class="btn btn-sm btn-success mr-1">
          <i class="fas fa-check"></i>
        </button>
        <a href="add_department.php" class="btn btn-sm btn-secondary">
          <i class="fas fa-times"></i>
        </a>
      </form>
    <?php } else { ?>
        <a href="add_department.php?edit=<?= (int)$d['department_id'] ?>"
           class="btn btn-sm btn-warning">
          <i class="fas fa-edit"></i>
        </a>
        <a href="add_department.php?delete=<?= (int)$d['department_id'] ?>"
           class="btn btn-sm btn-danger"
           onclick="return confirm('Delete this department?')">
          <i class="fas fa-trash"></i>
        </a>
    <?php } ?>
  </td>
</tr>
<?php }} else { ?>
<tr>
  <td colspan="5" class="text-muted text-center p-3">
    No departments found.
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
