<?php
include 'auth.php';
include '../database.php';

/* =============================
   CONTEXT
============================= */
$user_id = (int)($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    die('Invalid session');
}
$actorId = $user_id;

/* =============================
   DELETE NOTE
============================= */
if (isset($_POST['delete_note'])) {

    $note_id = (int)($_POST['note_id'] ?? 0);

    if ($note_id > 0) {
        $stmt = $conn->prepare("
            DELETE FROM user_notes
            WHERE note_id = ? AND user_id = ?
            LIMIT 1
        ");
        if (!$stmt) {
            die("SQL ERROR (DELETE): " . $conn->error);
        }

        $stmt->bind_param("ii", $note_id, $user_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: my_notes.php");
    exit;
}

/* =============================
   ADD NOTE
============================= */
if (isset($_POST['add_note'])) {

    $title  = trim($_POST['title'] ?? '');
    $note   = trim($_POST['note'] ?? '');
    $status = trim($_POST['status'] ?? 'Todo');

    $allowed = ['Todo','Doing','Done'];
    if (!in_array($status, $allowed, true)) $status = 'Todo';

    if ($title !== '' && $note !== '') {

        // next sort order in that column
        $maxStmt = $conn->prepare("
            SELECT COALESCE(MAX(sort_order), 0) AS mx
            FROM user_notes
            WHERE user_id = ? AND status = ?
        ");
        $maxStmt->bind_param("is", $user_id, $status);
        $maxStmt->execute();
        $mxRow = $maxStmt->get_result()->fetch_assoc();
        $maxStmt->close();

        $nextOrder = (int)($mxRow['mx'] ?? 0) + 1;

        $stmt = $conn->prepare("
            INSERT INTO user_notes (user_id, title, note, status, sort_order, created_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if (!$stmt) {
            die("SQL ERROR (INSERT): " . $conn->error);
        }

        $stmt->bind_param("isssii", $user_id, $title, $note, $status, $nextOrder, $actorId);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: my_notes.php");
    exit;
}

/* =============================
   FETCH NOTES (BY COLUMN)
============================= */
function fetch_notes(mysqli $conn, int $userId, string $status): array {
    $stmt = $conn->prepare("
        SELECT note_id, title, note, created_at, status, sort_order
        FROM user_notes
        WHERE user_id = ? AND status = ?
        ORDER BY sort_order ASC, created_at DESC
    ");
    if (!$stmt) return [];
    $stmt->bind_param("is", $userId, $status);
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = [];
    while ($r = $res->fetch_assoc()) $rows[] = $r;
    $stmt->close();
    return $rows;
}

$todo  = fetch_notes($conn, $user_id, 'Todo');
$doing = fetch_notes($conn, $user_id, 'Doing');
$done  = fetch_notes($conn, $user_id, 'Done');

/* =============================
   UI META
============================= */
$pageTitle  = 'My Notes';
$activePage = 'my_notes';
$themeClass = (($_SESSION['theme'] ?? 'light') === 'dark') ? 'dark-mode' : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>My Notes</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">

<style>
  .kanban-board { display:flex; gap:16px; flex-wrap:wrap; }
  .kanban-col { flex: 1 1 320px; min-width: 300px; }
  .kanban-col .card-header { display:flex; justify-content:space-between; align-items:center; }
  .kanban-list { min-height: 120px; padding: 10px; background: rgba(0,0,0,.02); border-radius: 10px; }
  .kanban-item { border-radius: 12px; box-shadow: 0 8px 18px rgba(0,0,0,.06); margin-bottom: 10px; }
  .kanban-item .card-body { padding: 12px; }
  .kanban-title { font-weight: 700; margin-bottom: 6px; }
  .kanban-note { white-space: pre-wrap; }
  .kanban-meta { font-size: 12px; color: #777; display:flex; justify-content:space-between; margin-top: 8px; }
  .drag-handle { cursor: grab; opacity: .8; }
  .drag-ghost { opacity: .4; }
  
  
  
  /* ===============================
   MY NOTES – MAGENTA KANBAN THEME
=============================== */
:root{
  --magenta:#ff0090;
  --magenta-soft:#f49ac2;
  --salmon:#ff9999;
  --black:#000;
}

/* MAIN CARD (ADD NOTE) */
.card-outline.card-primary{
  border-top:3px solid var(--magenta);
}
.card-outline.card-primary .card-header{
  border-bottom:1px solid var(--magenta-soft);
}
.card-outline.card-primary .card-title{
  color:var(--magenta);
  font-weight:600;
}

/* FORM INPUT */
.form-control:focus{
  border-color:var(--magenta);
  box-shadow:0 0 0 .1rem rgba(255,0,144,.25);
}

/* PRIMARY BUTTON */
.btn-primary{
  background:var(--magenta);
  border-color:var(--magenta);
}
.btn-primary:hover{
  background:#e60080;
  border-color:#e60080;
}

/* ===============================
   KANBAN COLUMNS (HEADER)
=============================== */
.kanban-col .card{
  border-top:3px solid var(--magenta);
}

.kanban-col .card-header{
  background:linear-gradient(
    90deg,
    var(--magenta),
    var(--magenta-soft)
  ) !important;
  color:#fff;
}

.kanban-col .card-header .card-title{
  color:#fff;
  font-weight:600;
}

.kanban-col .badge{
  background:#fff;
  color:var(--magenta);
  font-weight:700;
}

/* REMOVE DEFAULT COLORS */
.bg-info,
.bg-warning,
.bg-success{
  background:none !important;
}

/* ===============================
   KANBAN LIST AREA
=============================== */
.kanban-list{
  background:rgba(255,0,144,0.05);
  border:1px dashed rgba(255,0,144,0.35);
}

/* ===============================
   KANBAN ITEM (NOTE CARD)
=============================== */
.kanban-item{
  border-left:6px solid var(--magenta);
  transition:transform .15s ease, box-shadow .15s ease;
}

.kanban-item:hover{
  transform:translateY(-2px);
  box-shadow:0 12px 22px rgba(255,0,144,.18);
}

/* TITLE */
.kanban-title{
  color:var(--magenta);
}

/* DRAG HANDLE */
.drag-handle{
  color:var(--magenta);
}
.drag-handle:hover{
  color:#e60080;
}

/* META */
.kanban-meta{
  color:#666;
}

/* DELETE BUTTON */
.btn-danger{
  background:transparent;
  border:1px solid var(--salmon);
  color:var(--salmon);
}
.btn-danger:hover{
  background:var(--salmon);
  color:#000;
}

/* DRAG GHOST */
.drag-ghost{
  opacity:.35;
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
  <h1>My Notes</h1>
</div>
</section>

<section class="content">
<div class="container-fluid">

<!-- ADD NOTE -->
<div class="card card-outline card-primary">
  <div class="card-header">
    <h3 class="card-title">Add New Note</h3>
  </div>

  <form method="POST" autocomplete="off">
    <div class="card-body">

      <div class="form-row">
        <div class="form-group col-md-6">
          <label>Title</label>
          <input type="text" name="title" class="form-control" maxlength="255" required>
        </div>

        <div class="form-group col-md-6">
          <label>Column</label>
          <select name="status" class="form-control">
            <option value="Todo">To Do</option>
            <option value="Doing">Doing</option>
            <option value="Done">Done</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label>Note</label>
        <textarea name="note" class="form-control" rows="3" required></textarea>
      </div>

    </div>

    <div class="card-footer">
      <button type="submit" name="add_note" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Note
      </button>
    </div>
  </form>
</div>

<!-- KANBAN -->
<div class="kanban-board mt-3">

  <!-- TODO -->
  <div class="kanban-col">
    <div class="card">
      <div class="card-header bg-info">
        <h3 class="card-title mb-0 text-white"><i class="fas fa-list"></i> To Do</h3>
        <span class="badge badge-light"><?= count($todo) ?></span>
      </div>
      <div class="card-body">
        <div class="kanban-list" id="col-Todo" data-status="Todo">
          <?php if (count($todo) === 0): ?>
            <div class="text-muted small">No notes</div>
          <?php endif; ?>

          <?php foreach ($todo as $n): ?>
            <div class="card kanban-item" data-id="<?= (int)$n['note_id'] ?>">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="kanban-title"><?= htmlspecialchars($n['title']) ?></div>
                  <div class="drag-handle" title="Drag">
                    <i class="fas fa-grip-vertical"></i>
                  </div>
                </div>

                <div class="kanban-note"><?= htmlspecialchars($n['note']) ?></div>

                <div class="kanban-meta">
                  <span><?= date('d M Y H:i', strtotime($n['created_at'])) ?></span>

                  <form method="POST" onsubmit="return confirm('Delete this note?');">
                    <input type="hidden" name="note_id" value="<?= (int)$n['note_id'] ?>">
                    <button type="submit" name="delete_note" class="btn btn-xs btn-danger">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- DOING -->
  <div class="kanban-col">
    <div class="card">
      <div class="card-header bg-warning">
        <h3 class="card-title mb-0"><i class="fas fa-spinner"></i> Doing</h3>
        <span class="badge badge-dark"><?= count($doing) ?></span>
      </div>
      <div class="card-body">
        <div class="kanban-list" id="col-Doing" data-status="Doing">
          <?php if (count($doing) === 0): ?>
            <div class="text-muted small">No notes</div>
          <?php endif; ?>

          <?php foreach ($doing as $n): ?>
            <div class="card kanban-item" data-id="<?= (int)$n['note_id'] ?>">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="kanban-title"><?= htmlspecialchars($n['title']) ?></div>
                  <div class="drag-handle" title="Drag">
                    <i class="fas fa-grip-vertical"></i>
                  </div>
                </div>

                <div class="kanban-note"><?= htmlspecialchars($n['note']) ?></div>

                <div class="kanban-meta">
                  <span><?= date('d M Y H:i', strtotime($n['created_at'])) ?></span>

                  <form method="POST" onsubmit="return confirm('Delete this note?');">
                    <input type="hidden" name="note_id" value="<?= (int)$n['note_id'] ?>">
                    <button type="submit" name="delete_note" class="btn btn-xs btn-danger">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- DONE -->
  <div class="kanban-col">
    <div class="card">
      <div class="card-header bg-success">
        <h3 class="card-title mb-0 text-white"><i class="fas fa-check"></i> Done</h3>
        <span class="badge badge-light"><?= count($done) ?></span>
      </div>
      <div class="card-body">
        <div class="kanban-list" id="col-Done" data-status="Done">
          <?php if (count($done) === 0): ?>
            <div class="text-muted small">No notes</div>
          <?php endif; ?>

          <?php foreach ($done as $n): ?>
            <div class="card kanban-item" data-id="<?= (int)$n['note_id'] ?>">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="kanban-title"><?= htmlspecialchars($n['title']) ?></div>
                  <div class="drag-handle" title="Drag">
                    <i class="fas fa-grip-vertical"></i>
                  </div>
                </div>

                <div class="kanban-note"><?= htmlspecialchars($n['note']) ?></div>

                <div class="kanban-meta">
                  <span><?= date('d M Y H:i', strtotime($n['created_at'])) ?></span>

                  <form method="POST" onsubmit="return confirm('Delete this note?');">
                    <input type="hidden" name="note_id" value="<?= (int)$n['note_id'] ?>">
                    <button type="submit" name="delete_note" class="btn btn-xs btn-danger">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

</div><!-- /kanban-board -->

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

<!-- SortableJS for drag & drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
(function(){

  function postUpdate(noteId, status, sortOrder){
    return fetch('notes_api/update_note_board.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: 'note_id=' + encodeURIComponent(noteId) +
            '&status=' + encodeURIComponent(status) +
            '&sort_order=' + encodeURIComponent(sortOrder)
    }).then(r => r.json()).catch(() => ({success:false}));
  }

  function syncColumn(container){
    const status = container.dataset.status;
    const items = Array.from(container.querySelectorAll('.kanban-item'));
    // update order sequentially
    items.forEach((el, idx) => {
      const id = el.getAttribute('data-id');
      postUpdate(id, status, idx + 1);
    });
  }

  document.querySelectorAll('.kanban-list').forEach(list => {
    new Sortable(list, {
      group: 'kanban',
      animation: 180,
      handle: '.drag-handle',
      ghostClass: 'drag-ghost',
      onEnd: function(evt){
        // after drop, sync both origin & destination
        if (evt.from) syncColumn(evt.from);
        if (evt.to && evt.to !== evt.from) syncColumn(evt.to);
      }
    });
  });

})();
</script>

</body>
</html>
