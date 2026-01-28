<?php 
include 'auth.php';
include '../database.php';
require_once __DIR__ . '/notifications/notifications_create.php';

/* =============================
   PAGE TITLE
============================= */
$pageTitle = 'add_activity';
$activePage = 'Add activity';

/* =============================
   ACCESS CONTROL
============================= */
if (isStaff()) {
    die('Access denied');
}

$actorId     = (int)($_SESSION['user_id'] ?? 0);
$actorName   = $_SESSION['name'] ?? '';
$currentDept = (int)($_SESSION['department_id'] ?? 0);
$isAdmin     = isSystemAdmin();

/* =============================
   DATE HELPERS
============================= */
$today = date('Y-m-d');

/* =============================
   SELECTED DEPARTMENT
============================= */
$selectedDept = $currentDept;
if ($isAdmin && isset($_POST['department_id'])) {
    $selectedDept = (int)$_POST['department_id'];
}

/* =============================
   LOAD DEPARTMENTS
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
} else {
    $departments[] = [
        'department_id'   => $currentDept,
        'department_name' => $_SESSION['department_name'] ?? ''
    ];
}

/* =============================
   LOAD STAFF BY DEPARTMENT
============================= */
$staffs = [];
if ($selectedDept > 0) {
    $stmt = $conn->prepare("
        SELECT user_id, name
        FROM users
        WHERE department_id = ?
          AND role = 'Staff'
          AND status = 'active'
        ORDER BY name
    ");
    $stmt->bind_param("i", $selectedDept);
    $stmt->execute();
    $staffs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

/* =============================
   HANDLE FORM SUBMIT
============================= */
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {

    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $startDate   = $_POST['start_date'] ?: null;
    $endDate     = $_POST['end_date'] ?: null;
    $assigned    = $_POST['assigned_users'] ?? [];
    $subtasks    = $_POST['subtasks'] ?? [];

    if ($startDate && $startDate < $today) {
        $error = 'Start date cannot be earlier than today.';
    }

    if ($endDate && $startDate && $endDate < $startDate) {
        $error = 'End date cannot be earlier than start date.';
    }

    if ($title === '' || $selectedDept <= 0) {
        $error = 'Invalid input.';
    }

    if ($error === '') {

        $conn->begin_transaction();

        try {
            /* ===== INSERT ACTIVITY ===== */
            $stmt = $conn->prepare("
                INSERT INTO activities
                (title, description, status, start_date, end_date, department_id, created_by)
                VALUES (?, ?, 'Pending', ?, ?, ?, ?)
            ");
            $stmt->bind_param(
                "ssssii",
                $title,
                $description,
                $startDate,
                $endDate,
                $selectedDept,
                $actorId
            );
            $stmt->execute();
            $activityId = $stmt->insert_id;
            $stmt->close();

            /* ===== ASSIGNED USERS + NOTIFICATION ===== */
					if ($assigned) {
						$stmt = $conn->prepare("
							INSERT INTO activity_users (activity_id, user_id)
							VALUES (?, ?)
						");

						foreach ($assigned as $uid) {
							$uid = (int)$uid;
							$stmt->bind_param("ii", $activityId, $uid);
							$stmt->execute();

							// ✅ CORRECT NOTIFICATION CALL
							notifyUser(
								$conn,
								$uid,
								'New Activity Assigned',
								"You have been assigned to: {$title}",
								'info',
								'activities.php'
							);
						}

						$stmt->close();
					}
					
					// Notify creator (confirmation)
						notifyActorOnly(
							$conn,
							$actorId,
							"You created a new activity: {$title}",
							'success',
							'activities.php'
						);



            /* ===== SUBTASKS ===== */
            if ($subtasks) {
                $stmt = $conn->prepare("
                    INSERT INTO activity_subtasks (activity_id, title, status)
                    VALUES (?, ?, 'Pending')
                ");
                foreach ($subtasks as $s) {
                    $s = trim($s);
                    if ($s === '') continue;
                    $stmt->bind_param("is", $activityId, $s);
                    $stmt->execute();
                }
                $stmt->close();
            }

            $conn->commit();
            header("Location: activities.php?created=1");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Failed to create activity.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Add Activity</title>
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

/* CARD */
.card-outline.card-primary{
  border-top:3px solid var(--magenta);
}
.card-header{
  border-bottom:1px solid var(--magenta-soft);
}
.card-title{
  color:var(--magenta);
  font-weight:600;
}

/* FORM LABELS */
label{
  font-weight:600;
  color:#000;
}

/* INPUTS */
.form-control{
  border-radius:6px;
}
.form-control:focus{
  border-color:var(--magenta);
  box-shadow:0 0 0 .1rem rgba(255,0,144,.25);
}

/* CHECKBOX */
.form-check-input:checked{
  background-color:var(--magenta);
  border-color:var(--magenta);
}

/* ALERT */
.alert-danger{
  border-left:4px solid var(--salmon);
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

.btn-secondary{
  background:transparent;
  border:1px solid var(--magenta);
  color:var(--magenta);
}
.btn-secondary:hover{
  background:var(--magenta-soft);
  color:#000;
}

/* SUBTASK INPUT */
#subtaskList input{
  background:#fff;
}

/* CARD FOOTER */
.card-footer{
  background:#fff;
  border-top:1px solid var(--magenta-soft);
}


/* ===============================
   DEPARTMENT INFO (HOD VIEW)
=============================== */
.alert-info{
  background: rgba(255,0,144,0.10);   /* soft magenta bg */
  border: 1.5px solid rgba(255,0,144,0.45);
  border-left: 6px solid var(--magenta); /* lebih pekat & jelas */
  color: #000;
  border-radius: 6px;
}

.alert-info strong{
  color: var(--magenta);
  font-weight: 700;
}

/* ===============================
   ASSIGNED BY (READ-ONLY INFO)
=============================== */
.form-control[readonly]{
  background: rgba(255,0,144,0.10);
  border: 1.5px solid rgba(255,0,144,0.45);
  border-left: 6px solid var(--magenta);
  color: #000;
  font-weight: 500;
}

.form-control[readonly]:focus{
  box-shadow: none;
  border-color: rgba(255,0,144,0.45);
}


</style>
</head>

<body class="hold-transition sidebar-mini layout-fixed <?= $themeClass ?>">
<div class="wrapper">


<?php include 'partials/navbar.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<div class="content-wrapper">
<section class="content p-3">

<form method="POST" class="card card-outline card-primary">
<div class="card-header">
<h3 class="card-title">Add Activity</h3>
</div>

<div class="card-body">

<?php if ($error): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="form-group">
<label>Assigned By</label>
<input type="text" class="form-control" value="<?= htmlspecialchars($actorName) ?>" readonly>
</div>

<?php if ($isAdmin): ?>
<div class="form-group">
<label>Department</label>
<select name="department_id" class="form-control" onchange="this.form.submit()">
<option value="">-- Select Department --</option>
<?php foreach ($departments as $d): ?>
<option value="<?= (int)$d['department_id'] ?>" <?= $selectedDept===(int)$d['department_id']?'selected':'' ?>>
<?= htmlspecialchars($d['department_name']) ?>
</option>
<?php endforeach; ?>
</select>
</div>
<?php else: ?>
<div class="alert alert-info">
<strong>Department:</strong> <?= htmlspecialchars($_SESSION['department_name'] ?? '') ?>
</div>
<?php endif; ?>

<div class="form-group">
<label>Title</label>
<input name="title" class="form-control" required value="<?= htmlspecialchars($_POST['title'] ?? '') ?>">
</div>

<div class="form-group">
<label>Description</label>
<textarea name="description" class="form-control" rows="3" required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
</div>

<div class="form-row">
<div class="form-group col-md-6">
<label>Start Date</label>
<input type="date" name="start_date" class="form-control" min="<?= $today ?>" value="<?= htmlspecialchars($_POST['start_date'] ?? '') ?>">
</div>
<div class="form-group col-md-6">
<label>End Date</label>
<input type="date" name="end_date" class="form-control" min="<?= htmlspecialchars($_POST['start_date'] ?? $today) ?>" value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
</div>
</div>

<div class="form-group">
<label>Assign To</label>
<?php foreach ($staffs as $s): ?>
<div class="form-check">
<input type="checkbox" name="assigned_users[]" value="<?= (int)$s['user_id'] ?>" class="form-check-input"
<?= in_array($s['user_id'], $_POST['assigned_users'] ?? []) ? 'checked' : '' ?>>
<label class="form-check-label"><?= htmlspecialchars($s['name']) ?></label>
</div>
<?php endforeach; ?>
</div>

<div class="form-group">
<label>Subtasks</label>
<div id="subtaskList">
<input name="subtasks[]" class="form-control mb-1" placeholder="Subtask">
</div>
<button type="button" class="btn btn-sm btn-secondary" onclick="addSub()">+ Add Subtask</button>
</div>

</div>

<div class="card-footer">
<button name="add" class="btn btn-primary">
<i class="fas fa-save"></i> Create
</button>
</div>
</form>

</section>
</div>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
function addSub(){
  const d=document.getElementById('subtaskList');
  const i=document.createElement('input');
  i.name='subtasks[]';
  i.className='form-control mb-1';
  i.placeholder='Subtask';
  d.appendChild(i);
}
</script>

</body>
</html>
