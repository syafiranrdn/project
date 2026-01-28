<?php
include 'auth.php';
include '../database.php';

/* =============================
   ACCESS CONTROL
============================= */
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = (int)$_SESSION['user_id'];
$pageTitle = 'Notifications';

/* =============================
   HANDLE MARK ALL READ
============================= */
if (isset($_POST['mark_all'])) {
    $stmt = $conn->prepare("
        UPDATE notifications
        SET is_read = 1
        WHERE recipient_user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();

    header("Location: notifications.php");
    exit;
}

/* =============================
   FETCH NOTIFICATIONS
============================= */
$stmt = $conn->prepare("
    SELECT
        notification_id,
        title,
        message,
        type,
        link,
        is_read,
        created_at
    FROM notifications
    WHERE recipient_user_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$notifications = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Notifications</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
.notification-unread {
  background: #1e1e1e;
  border-left: 4px solid #ff0090;
}
.notification-item:hover {
  background: #2a2a2a;
}
</style>
</head>

<body class="hold-transition sidebar-mini">
<div class="wrapper">

<?php include 'partials/navbar.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<div class="content-wrapper">

<section class="content-header">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <h1>Notifications</h1>

    <form method="POST">
      <button name="mark_all" class="btn btn-sm btn-outline-secondary">
        <i class="fas fa-check-double"></i> Mark all as read
      </button>
    </form>
  </div>
</section>

<section class="content">
<div class="container-fluid">

<div class="card">
<div class="card-body p-0">

<?php if ($notifications->num_rows === 0): ?>
  <div class="p-4 text-muted text-center">
    No notifications found.
  </div>
<?php endif; ?>

<ul class="list-group list-group-flush">

<?php while ($n = $notifications->fetch_assoc()): ?>
<li class="list-group-item notification-item
    <?= !$n['is_read'] ? 'notification-unread' : '' ?>">

  <div class="d-flex justify-content-between">
    <div>
      <strong><?= htmlspecialchars($n['title']) ?></strong><br>
      <small class="text-muted"><?= htmlspecialchars($n['message']) ?></small><br>
      <small class="text-muted">
        <?= date('d M Y, h:i A', strtotime($n['created_at'])) ?>
      </small>
    </div>

    <div class="text-right">
      <?php if (!$n['is_read']): ?>
        <span class="badge badge-warning">Unread</span>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($n['link']): ?>
  <div class="mt-2">
    <a href="<?= htmlspecialchars($n['link']) ?>"
       onclick="markRead(<?= (int)$n['notification_id'] ?>)"
       class="btn btn-sm btn-primary">
      View
    </a>
  </div>
  <?php endif; ?>

</li>
<?php endwhile; ?>

</ul>

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
function markRead(id){
  fetch('notifications_api/mark_read.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'id='+id
  });
}
</script>

</body>
</html>
