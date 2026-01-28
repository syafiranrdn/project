<?php
// admin/partials/navbar.php

$pageTitleSafe = $pageTitle ?? 'Dashboard';

/* ROLE BADGE */
$roleLabel = roleBadgeLabel();
$roleColor = roleBadgeColor();

/* USER */
$displayName  = $_SESSION['name'] ?? 'User';
$profilePhoto = $_SESSION['profile_photo'] ?? '';
$profilePath  = !empty($profilePhoto)
  ? 'uploads/profile/' . basename($profilePhoto)
  : 'dist/img/avatar.png';

/* BASE */
$uri  = $_SERVER['REQUEST_URI'] ?? '';
$base = (strpos($uri, '/admin/') !== false)
  ? substr($uri, 0, strpos($uri, '/admin/') + 7)
  : 'admin/';
?>

<style>
/* =====================================================
   GLOBAL VARIABLES
===================================================== */
:root{
  --nav-bg:#100c08;
  --magenta:#ff0090;
  --magenta-soft:#f49ac2;
  --text-white:#ffffff;
  --text-muted:#b5b5b5;
}

/* =====================================================
   NAVBAR BASE
===================================================== */
.main-header.navbar{
  background:var(--nav-bg)!important;
  border-bottom:1px solid rgba(255,0,144,.25);
}

.main-header.navbar *{
  transition:none!important;
  animation:none!important;
}

/* =====================================================
   NAVBAR TEXT
===================================================== */
.main-header .nav-link,
.main-header a,
.main-header span{
  color:var(--text-white)!important;
}

.main-header .text-white-50{
  color:var(--text-muted)!important;
}

/* =====================================================
   ICONS
===================================================== */
.main-header i,
.main-header .fas,
.main-header .far{
  color:var(--magenta)!important;
}

/* =====================================================
   STANDARD AVATAR (GLOBAL)
===================================================== */
.avatar-circle{
  width:36px;
  height:36px;
  border-radius:50%;
  object-fit:cover;
  border:2px solid rgba(255,255,255,.25);
  box-shadow:0 4px 12px rgba(255,0,144,.25);
  display:block;
}

/* =====================================================
   NOTIFICATIONS DROPDOWN
===================================================== */
.notifications-dropdown{
  width:420px;
  padding:0;
  border-radius:10px;
  background:#ffffff;          /* ✅ SOLID BACKGROUND */
  box-shadow:0 10px 30px rgba(0,0,0,.18);
  overflow:hidden;
}


.notifications-dropdown .btn{
  font-size:12px;
  padding:4px 10px;
}

.notification-item{
  padding:10px 16px;
  font-size:14px;
  cursor:pointer;
}

.notification-item:hover{
  background:#fafafa;
}

.notif-empty{
  padding:28px 0;
  text-align:center;
  font-size:14px;
  color:#888;
}

.notif-history-title{
  padding:8px 16px;
  font-weight:700;
  background:#fafafa;
  border-top:1px solid #eee;
}

.notif-history-item{
  padding:8px 16px;
  font-size:13px;
  border-bottom:1px solid #f0f0f0;
}

/* notification badge */
#notifBadge{
  background:var(--magenta)!important;
  color:#fff!important;
}

/* =====================================================
   🔥 FORCE ROLE BADGE = MAGENTA (ADMIN / HOD / STAFF)
===================================================== */
.badge,
.badge-danger,
.badge-info,
.badge-warning,
.badge-success,
.badge-primary,
.badge-secondary{
  background:linear-gradient(
    90deg,
    var(--magenta),
    var(--magenta-soft)
  ) !important;
  color:#ffffff !important;
  border:none !important;
  font-weight:600 !important;
  box-shadow:0 2px 6px rgba(255,0,144,.35);
}



</style>

<nav class="main-header navbar navbar-expand navbar-dark">

<!-- LEFT -->
<ul class="navbar-nav align-items-center">
  <li class="nav-item">
    <a class="nav-link" data-widget="pushmenu" href="#"><i class="fas fa-bars"></i></a>
  </li>
  <li class="nav-item d-none d-sm-inline-block">
    <span class="nav-link font-weight-bold">
      <?= htmlspecialchars($pageTitleSafe) ?>
      <span class="badge badge-<?= $roleColor ?> ml-2"><?= $roleLabel ?></span>
    </span>
  </li>
</ul>

<!-- RIGHT -->
<ul class="navbar-nav ml-auto align-items-center">

<li class="nav-item d-none d-lg-flex mr-2">
  <span class="text-white-50 small"><?= htmlspecialchars($displayName) ?></span>
</li>

<li class="nav-item mx-2">
  <a href="<?= htmlspecialchars($base) ?>profile.php"
     class="nav-link p-0 d-flex align-items-center"
     title="My Profile">
    <img src="<?= htmlspecialchars($profilePath) ?>"
         class="avatar-circle"
         alt="Profile">
  </a>
</li>

<!-- NOTIFICATIONS -->
<li class="nav-item dropdown">
  <a class="nav-link" data-toggle="dropdown" href="#">
    <i id="bellIcon" class="far fa-bell"></i>
    <span class="badge navbar-badge" id="notifBadge" style="display:none">0</span>
  </a>

  <div class="dropdown-menu dropdown-menu-right notifications-dropdown">

    <div class="dropdown-item d-flex justify-content-between align-items-center">
      <strong>Notifications</strong>
      <div>
        <button class="btn btn-sm btn-outline-danger" id="btnDeleteSelected">Delete</button>
        <button class="btn btn-sm btn-outline-secondary" id="btnClearAll">Clear All</button>
      </div>
    </div>

    <div class="dropdown-divider"></div>

    <div id="notifActive">
      <div class="notif-empty">No notifications</div>
    </div>

    <div class="dropdown-divider"></div>

    <div class="notif-history-title">History</div>
    <div id="notifHistory">
      <div class="notif-empty">No history</div>
    </div>

  </div>
</li>

<li class="nav-item ml-2">
  <a href="<?= htmlspecialchars($base) ?>logout.php" class="nav-link text-danger">
    <i class="fas fa-power-off"></i>
  </a>
</li>

</ul>
</nav>

<script>
const ADMIN_BASE = <?= json_encode($base) ?>;

/* ===== LOAD NOTIFICATIONS ===== */
async function loadNotifications(){
  const r = await fetch(ADMIN_BASE+'notifications_api/fetch_notifications.php');
  const j = await r.json();
  if(!j.success) return;

  const badge = document.getElementById('notifBadge');
  const bell  = document.getElementById('bellIcon');
  const box   = document.getElementById('notifActive');

  badge.style.display = j.unread_count ? 'inline' : 'none';
  badge.innerText = j.unread_count || '';
  bell.className = j.unread_count ? 'fas fa-bell' : 'far fa-bell';

  box.innerHTML = '';

  if(!j.data || !j.data.length){
    box.innerHTML = `<div class="notif-empty">No notifications</div>`;
    return;
  }

  j.data.forEach(n=>{
    const row = document.createElement('div');
    row.className = 'notification-item';

    row.innerHTML = `
      <div class="d-flex">
        <input type="checkbox" class="notif-check mr-2" value="${n.id}">
        <div>
          <strong>${n.title}</strong>
          <small>${n.message}</small>
        </div>
      </div>
    `;

    row.onclick = async e => {
      if(e.target.classList.contains('notif-check')) return;
      await fetch(ADMIN_BASE+'notifications_api/mark_read.php',{
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'id='+n.id
      });
      if(n.link){
        location.href = n.link.includes('admin/') ? n.link : ADMIN_BASE+n.link;
      }
    };

    box.appendChild(row);
  });
}

/* ===== HISTORY ===== */
async function loadHistory(){
  const r = await fetch(ADMIN_BASE+'notifications_api/fetch_history.php');
  const j = await r.json();
  const box = document.getElementById('notifHistory');

  box.innerHTML = '';

  if(!j.success || !j.data || !j.data.length){
    box.innerHTML = `<div class="notif-empty">No history</div>`;
    return;
  }

  j.data.forEach(n=>{
    box.innerHTML += `
      <div class="notif-history-item">
        <strong>${n.title}</strong><br>
        ${n.message}
      </div>
    `;
  });
}

/* ===== ACTIONS ===== */
async function deleteSelected(){
  const ids = [...document.querySelectorAll('.notif-check:checked')].map(c=>c.value);
  if(!ids.length) return alert('Select at least one notification');
  await fetch(ADMIN_BASE+'notifications_api/delete_selected.php',{
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body:JSON.stringify({ids})
  });
  loadNotifications(); loadHistory();
}

async function clearAll(){
  if(!confirm('Delete all notifications?')) return;
  await fetch(ADMIN_BASE+'notifications_api/delete_all.php',{method:'POST'});
  loadNotifications(); loadHistory();
}

document.addEventListener('DOMContentLoaded',()=>{
  loadNotifications(); loadHistory();
  document.getElementById('btnDeleteSelected')?.addEventListener('click',e=>{
    e.preventDefault(); e.stopPropagation(); deleteSelected();
  });
  document.getElementById('btnClearAll')?.addEventListener('click',e=>{
    e.preventDefault(); e.stopPropagation(); clearAll();
  });
});
</script>
