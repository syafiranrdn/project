<?php
include 'auth.php';
include '../database.php';
require_once __DIR__ . '/notifications/notifications_create.php';

/* =============================
   ACCESS CONTROL
============================= */
if (isStaff()) die('Access denied');
$isAdmin = isSystemAdmin();

/* =============================
   PAGE TITLE (FOR NAVBAR)
============================= */
$pageTitle = 'Edit Activity';

if (!isset($_GET['id']) || !ctype_digit($_GET['id'])) die('Invalid activity.');
$activity_id = (int)$_GET['id'];

$actorId   = (int)$_SESSION['user_id'];
$actorName = $_SESSION['name'] ?? 'User';


/* =============================
   FETCH ACTIVITY
============================= */
$stmt = $conn->prepare("SELECT * FROM activities WHERE activity_id=? LIMIT 1");
$stmt->bind_param("i", $activity_id);
$stmt->execute();
$activity = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$activity) die('Activity not found');

/* =============================
   FETCH SUBTASKS
============================= */
$subtasks = $conn->query("
    SELECT subtask_id, title, status
    FROM activity_subtasks
    WHERE activity_id = {$activity_id}
    ORDER BY subtask_id
");

/* =============================
   FETCH STAFF & ASSIGNED USERS
============================= */
$staffs = [];
$assignedUsers = [];

if ($isAdmin) {
    $staffs = $conn->query("
        SELECT user_id, name
        FROM users
        WHERE role='Staff' AND status='Active'
        ORDER BY name
    ");

    $r = $conn->query("
        SELECT user_id FROM activity_users
        WHERE activity_id = {$activity_id}
    ");
    while ($x = $r->fetch_assoc()) {
        $assignedUsers[] = (int)$x['user_id'];
    }
}

/* =============================
   HANDLE UPDATE
============================= */
if (isset($_POST['update'])) {

    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $status      = $_POST['status'];

    if ($title === '' || $description === '') die('Required');

    $u = $conn->prepare("
        UPDATE activities
        SET title=?, description=?, status=?
        WHERE activity_id=? LIMIT 1
    ");
    $u->bind_param("sssi", $title, $description, $status, $activity_id);
    $u->execute();
    $u->close();

    if ($isAdmin) {
        $conn->query("DELETE FROM activity_users WHERE activity_id={$activity_id}");
        foreach ($_POST['assigned_users'] ?? [] as $uid) {
            $uid = (int)$uid;
            if (!$uid) continue;

            $a = $conn->prepare("
                INSERT INTO activity_users(activity_id,user_id)
                VALUES(?,?)
            ");
            $a->bind_param("ii", $activity_id, $uid);
            $a->execute();
            $a->close();
        }
    }

    $link = "project_view.php?id={$activity_id}";

    notifyProjectMembers(
        $conn,
        $activity_id,
        "✏️ Activity updated: {$title}",
        'info',
        $link
    );

    notifyUser(
        $conn,
        $actorId,
        "✅ Activity updated",
        $title,
        'success',
        $link
    );

    header("Location: activities.php");
    exit;
}

$themeClass = (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Edit Activity</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
/* =====================================================
   🔥 STANDARD HEADER – MATCH DASHBOARD
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


/* INPUT FOCUS */
.form-control:focus{
  border-color:#ff0090;
  box-shadow:0 0 0 .1rem rgba(255,0,144,.25);
}

/* PRIMARY BUTTON */
.btn-primary{
  background:#ff0090;
  border-color:#ff0090;
}
.btn-primary:hover{
  background:#e60080;
  border-color:#e60080;
}

/* SECONDARY BUTTON */
.btn-secondary{
  background:transparent;
  border:1px solid #ff0090;
  color:#ff0090;
}
.btn-secondary:hover{
  background:#f49ac2;
  color:#000;
}

/* SUBTASK LIST */
.list-group-item:hover{
  background:rgba(244,154,194,.18);
}
</style>
</head>

<body class="hold-transition sidebar-mini layout-fixed <?= $themeClass ?>">
<div class="wrapper">

<?php include 'partials/navbar.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<div class="content-wrapper">
<section class="content p-3">

<div class="card card-outline card-primary">
<form method="POST">

<div class="card-header">
  <h3 class="card-title">Edit Activity</h3>
</div>

<div class="card-body">

<label>Title</label>
<input class="form-control mb-3" name="title"
value="<?= htmlspecialchars($activity['title']) ?>">

<label>Description</label>
<textarea class="form-control mb-4"
name="description"><?= htmlspecialchars($activity['description']) ?></textarea>

<?php if ($isAdmin): ?>
<label>Assign</label>
<?php while($s=$staffs->fetch_assoc()): ?>
<div class="form-check">
<input type="checkbox" class="form-check-input"
name="assigned_users[]"
value="<?= $s['user_id'] ?>"
<?= in_array($s['user_id'],$assignedUsers,true)?'checked':'' ?>>
<label class="form-check-label"><?= htmlspecialchars($s['name']) ?></label>
</div>
<?php endwhile; ?>
<?php endif; ?>

<hr>

<label>Subtasks</label>
<ul class="list-group mb-3" id="subtaskList">
<?php while($s=$subtasks->fetch_assoc()): ?>
<li class="list-group-item d-flex justify-content-between align-items-center"
    data-id="<?= $s['subtask_id'] ?>">
<div>
<input type="checkbox" class="subtask mr-2"
<?= $s['status']==='Completed'?'checked':'' ?>>
<?= htmlspecialchars($s['title']) ?>
</div>
<button type="button"
class="btn btn-sm btn-danger btn-delete-subtask">
<i class="fas fa-trash"></i>
</button>
</li>
<?php endwhile; ?>
</ul>

<div class="input-group mb-3">
<input id="newSubtask" class="form-control" placeholder="New subtask">
<div class="input-group-append">
<button type="button" class="btn btn-secondary" id="btnAddSubtask">+ Add</button>
</div>
</div>

<label>Status</label>
<select class="form-control" name="status">
<option <?= $activity['status']==='Pending'?'selected':'' ?>>Pending</option>
<option <?= $activity['status']==='In Progress'?'selected':'' ?>>In Progress</option>
<option <?= $activity['status']==='Completed'?'selected':'' ?>>Completed</option>
</select>

</div>

<div class="card-footer text-right">
<button class="btn btn-primary" name="update">Save</button>
<a href="activities.php" class="btn btn-secondary">Cancel</a>
</div>

</form>
</div>

</section>
</div>

<footer class="main-footer"><strong>Monitoring System</strong></footer>
</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

<script>
const activityId = <?= $activity_id ?>;
const list = document.getElementById('subtaskList');

/* ADD SUBTASK */
document.getElementById('btnAddSubtask').addEventListener('click', async () => {
  const input = document.getElementById('newSubtask');
  const title = input.value.trim();
  if (!title) return alert('Subtask title required');

  const res = await fetch('activities_api/add_subtask.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'activity_id='+activityId+'&title='+encodeURIComponent(title)
  });

  const json = await res.json();
  if (!json.success) return alert(json.message || 'Add failed');

  const li = document.createElement('li');
  li.className = 'list-group-item d-flex justify-content-between align-items-center';
  li.dataset.id = json.subtask_id;
  li.innerHTML = `
    <div>
      <input type="checkbox" class="subtask mr-2">
      ${json.title}
    </div>
    <button type="button" class="btn btn-sm btn-danger btn-delete-subtask">
      <i class="fas fa-trash"></i>
    </button>
  `;
  list.appendChild(li);
  input.value = '';
});

/* DELETE + TOGGLE */
list.addEventListener('click', async e => {
  if (e.target.closest('.btn-delete-subtask')) {
    const li = e.target.closest('li');
    if (!confirm('Delete this subtask?')) return;

    const res = await fetch('activities_api/delete_subtask.php',{
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:'id='+li.dataset.id+'&activity_id='+activityId
    });

    const json = await res.json();
    if (json.success) li.remove();
  }
});

list.addEventListener('change', e => {
  if (!e.target.classList.contains('subtask')) return;

  const li = e.target.closest('li');
  fetch('activities_api/toggle_subtask.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:
      'id='+li.dataset.id+
      '&activity_id='+activityId+
      '&status='+(e.target.checked?'Completed':'Pending')
  });
});
</script>

</body>
</html>
