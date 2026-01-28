<?php
include 'auth.php';
include '../database.php';
require_once __DIR__ . '/notifications/notifications_create.php';

/* =============================
   PAGE META (MUST BE FIRST)
============================= */
$pageTitle   = 'Add User';
$activePage  = 'add_user';
$themeClass  = (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '';
$message     = '';

/* =============================
   ACCESS CONTROL
============================= */
if (isStaff()) die('Access denied');

$isSuperAdmin = isSystemAdmin();
$isHOD        = isHOD();

/* =============================
   FETCH DEPARTMENTS (SUPER ADMIN)
============================= */
$departments = [];
if ($isSuperAdmin) {
    $res = $conn->query("
        SELECT department_id, department_name
        FROM departments
        WHERE status='Active'
        ORDER BY department_name
    ");
    while ($r = $res->fetch_assoc()) $departments[] = $r;
}

/* =============================
   HANDLE SUBMIT
============================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $roleReq  = $_POST['role'] ?? '';

    if ($name === '' || $email === '' || $password === '') {
        $message = '<div class="alert alert-danger">All fields are required.</div>';
    } else {

        $role = 'Staff';
        $adminLevel = null;
        $department = null;

        if ($isSuperAdmin) {
            if ($roleReq === 'HOD') {
                $role = 'Admin';
                $adminLevel = 'Department';
                $department = (int)$_POST['department_id'];
            } elseif ($roleReq === 'ADMIN') {
                $role = 'Admin';
                $adminLevel = 'Super';
            } else {
                $department = (int)$_POST['department_id'];
            }
        } elseif ($isHOD) {
            $department = currentDepartmentId();
            if ($roleReq === 'ADMIN') {
                $role = 'Admin';
                $adminLevel = 'Department';
            }
        }

        if ($role !== 'Admin' && !$department) {
            $message = '<div class="alert alert-danger">Department required.</div>';
        } else {

            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users
                (name,email,password,role,admin_level,department_id,status,created_at)
                VALUES (?,?,?,?,?,?, 'Active', NOW())
            ");
            $stmt->bind_param("sssssi",
                $name,$email,$hash,$role,$adminLevel,$department
            );

            if ($stmt->execute()) {
                if ($isHOD) {
                    notifyAdmins($conn,
                        "👤 HOD added new user: {$name}",
                        'info',
                        'users_report.php'
                    );
                }
                $message = '<div class="alert alert-success">User added successfully.</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to add user.</div>';
            }
            $stmt->close();
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Add User</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
/* =====================================================
   ADD USER / FORM PAGES – PASTEL MAGENTA THEME (FIXED)
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
   🔥 REMOVE ADMINLTE BLUE (SAFE)
   ❗ BADGE COLOR NOT TOUCHED HERE
===================================================== */
.bg-primary,
.bg-info,
.card-primary:not(.card-outline) > .card-header,
.card-info:not(.card-outline) > .card-header,
.btn-info{
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
   CARD BASE (ALL FORM BOXES)
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

/* outline fix */
.card.card-outline.card-primary{
  border:1px solid var(--magenta-soft) !important;
}

/* =====================================================
   FORM LABELS
===================================================== */
label{
  color:var(--magenta);
  font-weight:600;
}

/* =====================================================
   FORM INPUTS & SELECT
===================================================== */
.form-control{
  border-radius:10px;
  border:1px solid var(--magenta-soft);
}

.form-control:focus{
  border-color:var(--magenta);
  box-shadow:0 0 0 .15rem rgba(255,0,144,.25);
}

/* select dropdown */
select.form-control{
  background:#fff;
}
select.form-control option:checked{
  background:var(--magenta-soft);
}

/* =====================================================
   PRIMARY BUTTON
===================================================== */
.btn-primary{
  background:linear-gradient(
    90deg,
    var(--magenta),
    var(--magenta-soft)
  );
  border:none;
  color:#fff;
  font-weight:600;
}
.btn-primary:hover{
  opacity:.9;
}

/* =====================================================
   ALERTS
===================================================== */
.alert-success{
  background:rgba(255,0,144,.14);
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

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

<?php include 'partials/navbar.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<div class="content-wrapper">

<section class="content-header">
<div class="container-fluid">
  <h1>Add User</h1>
</div>
</section>

<section class="content">
<div class="container-fluid">

<?= $message ?>

<div class="card card-outline card-primary">
<div class="card-header">
<h3 class="card-title">New User</h3>
</div>

<form method="POST" autocomplete="off">
<div class="card-body">

<div class="form-group">
<label>Name</label>
<input type="text" name="name" class="form-control" required>
</div>

<div class="form-group">
<label>Email</label>
<input type="email" name="email" class="form-control" required>
</div>

<div class="form-group">
<label>Password</label>
<input type="password" name="password" class="form-control" required>
</div>

<div class="form-group">
<label>Role</label>
<select name="role" class="form-control" required>
  <option value="Staff">Staff</option>

  <?php if ($isHOD) { ?>
    <option value="ADMIN">Admin (Department)</option>
  <?php } ?>

  <?php if ($isSuperAdmin) { ?>
    <option value="HOD">HOD</option>
    <option value="ADMIN">Admin (Super)</option>
  <?php } ?>
</select>
</div>

<?php if ($isSuperAdmin) { ?>
<div class="form-group">
<label>Department</label>
<select name="department_id" class="form-control">
  <option value="">-- Select Department --</option>
  <?php foreach ($departments as $d) { ?>
    <option value="<?= (int)$d['department_id'] ?>">
      <?= htmlspecialchars($d['department_name']) ?>
    </option>
  <?php } ?>
</select>
</div>
<?php } ?>

</div>

<div class="card-footer">
<button type="submit" class="btn btn-primary">
<i class="fas fa-save"></i> Save User
</button>
</div>
</form>

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
