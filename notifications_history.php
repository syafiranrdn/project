<?php
require_once 'auth.php';
require_once '../database.php';

$userId = (int)$_SESSION['user_id'];

$pageTitle = 'Notification History';

$stmt = $conn->prepare("
  SELECT
    notification_id,
    title,
    message,
    created_at
  FROM notifications
  WHERE recipient_user_id = ?
    AND is_deleted = 1
  ORDER BY created_at DESC
");
$stmt->bind_param("i", $userId);
$stmt->execute();
$rows = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
<title>Notification History</title>
<link rel="stylesheet" href="dist/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini">
<div class="wrapper">

<?php include 'partials/navbar.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<div class="content-wrapper">
<section class="content p-3">

<div class="card">
<div class="card-header">
  <h3 class="card-title">Notification History</h3>
</div>

<div class="card-body table-responsive p-0">
<table class="table table-hover">
<thead>
<tr>
  <th>Title</th>
  <th>Message</th>
  <th>Date</th>
  <th width="100">Action</th>
</tr>
</thead>
<tbody>

<?php while($n=$rows->fetch_assoc()): ?>
<tr id="row<?= $n['notification_id'] ?>">
<td><?= htmlspecialchars($n['title']) ?></td>
<td><?= htmlspecialchars($n['message']) ?></td>
<td><?= date('d M Y H:i', strtotime($n['created_at'])) ?></td>
<td>
<button class="btn btn-sm btn-danger"
 onclick="deleteForever(<?= $n['notification_id'] ?>)">
 Delete
</button>
</td>
</tr>
<?php endwhile; ?>

</tbody>
</table>
</div>
</div>

</section>
</div>
</div>

<script>
function deleteForever(id){
  if(!confirm('Delete permanently?')) return;

  fetch('notifications_api/delete_forever.php',{
    method:'POST',
    headers:{'Content-Type':'application/x-www-form-urlencoded'},
    body:'id='+id
  })
  .then(r=>r.json())
  .then(res=>{
    if(res.success){
      document.getElementById('row'+id)?.remove();
    }
  });
}
</script>
</body>
</html>
