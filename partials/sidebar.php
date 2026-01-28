<?php
// admin/partials/sidebar.php
// auth.php already included

$activePage = $activePage ?? '';
$brandLogo  = 'assets/img/monitoring.png';

/* =============================
   ROLE FLAGS
============================= */
$isStaff = isStaff();
$isHOD   = isHOD();
$isAdmin = isSystemAdmin();

/* =============================
   ACTIVE PAGE HELPER
============================= */
$isActive = function (string $key) use ($activePage): string {
    return ($activePage === $key) ? 'active' : '';
};

/* =============================
   FETCH ACTIVE DEPARTMENTS
============================= */
$departments = [];

if (!$isStaff && isset($conn)) {
    $res = mysqli_query(
        $conn,
        "SELECT department_id, department_name
         FROM departments
         WHERE status='Active'
         ORDER BY department_name ASC"
    );

    if ($res instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($res)) {
            $departments[] = $row;
        }
    }
}
?>

<style>
/* Sidebar scroll fix */
.main-sidebar .sidebar {
    height: calc(100vh - 120px);
    overflow-y: auto;
}


/* =====================================================
   SIDEBAR – BLACK + MAGENTA THEME (FINAL)
===================================================== */
:root{
  --sidebar-bg:#0b0b0b;
  --sidebar-border:rgba(255,0,144,.25);
  --magenta:#ff0090;
  --magenta-soft:#f49ac2;
  --magenta-glass:rgba(255,0,144,.12);
  --text-white:#f5f5f5;
  --text-muted:#9aa0a6;
}

/* =====================================================
   BASE SIDEBAR
===================================================== */
.main-sidebar{
  background:var(--sidebar-bg) !important;
  border-right:1px solid var(--sidebar-border);
}

.main-sidebar .sidebar{
  background:transparent !important;
}

/* =====================================================
   BRAND LOGO AREA
===================================================== */
.brand-link{
  background:var(--sidebar-bg) !important;
  border-bottom:1px solid var(--sidebar-border);
}

.brand-link img{
  filter:drop-shadow(0 4px 12px rgba(255,0,144,.35));
}

/* =====================================================
   NAV LINKS – TEXT
===================================================== */
.nav-sidebar .nav-link{
  color:var(--text-white) !important;
  border-radius:12px;
  margin:4px 10px;
  transition:none !important;
}

.nav-sidebar .nav-link p{
  color:var(--text-white) !important;
  font-weight:500;
}

/* =====================================================
   ICONS – MAGENTA
===================================================== */
.nav-sidebar .nav-icon{
  color:var(--magenta) !important;
  font-size:1.05rem;
}

/* =====================================================
   HOVER EFFECT
===================================================== */
.nav-sidebar .nav-link:hover{
  background:var(--magenta-glass) !important;
  color:#fff !important;
}

.nav-sidebar .nav-link:hover .nav-icon{
  color:var(--magenta-soft) !important;
}

/* =====================================================
   ACTIVE ITEM
===================================================== */
.nav-sidebar .nav-link.active{
  background:linear-gradient(
    90deg,
    rgba(255,0,144,.35),
    rgba(244,154,194,.25)
  ) !important;
  box-shadow:0 0 0 1px rgba(255,0,144,.45),
             0 6px 18px rgba(255,0,144,.35);
}

.nav-sidebar .nav-link.active p{
  color:#fff !important;
  font-weight:600;
}

.nav-sidebar .nav-link.active .nav-icon{
  color:#fff !important;
}

/* =====================================================
   NAV HEADERS (ADMIN / HOD)
===================================================== */
.nav-header{
  color:var(--text-muted) !important;
  font-size:11px;
  letter-spacing:.12em;
  margin-top:14px;
}

/* =====================================================
   REMOVE ADMINLTE BLUE / YELLOW
===================================================== */
.sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link{
  background:transparent !important;
}

.sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active{
  background:linear-gradient(
    90deg,
    rgba(255,0,144,.35),
    rgba(244,154,194,.25)
  ) !important;
}

/* =====================================================
   SCROLLBAR (OPTIONAL BUT NICE)
===================================================== */
.main-sidebar .sidebar::-webkit-scrollbar{
  width:6px;
}
.main-sidebar .sidebar::-webkit-scrollbar-thumb{
  background:rgba(255,0,144,.45);
  border-radius:6px;
}
.main-sidebar .sidebar::-webkit-scrollbar-track{
  background:transparent;
}



</style>

<aside class="main-sidebar elevation-4 sidebar-dark-primary">

  <!-- BRAND -->
  <a href="index.php"
     class="brand-link d-flex align-items-center justify-content-center"
     style="padding:14px 10px;">
    <img src="<?= htmlspecialchars($brandLogo) ?>"
         alt="Monitoring Logo"
         style="height:70px;width:auto;opacity:.95;">
  </a>

  <div class="sidebar">
    <nav class="mt-3">
      <ul class="nav nav-pills nav-sidebar flex-column" data-accordion="false">

        <!-- ================= DASHBOARD ================= -->
        <li class="nav-item">
          <a href="index.php" class="nav-link <?= $isActive('dashboard') ?>">
            <i class="nav-icon fas fa-tachometer-alt"></i>
            <p>Dashboard</p>
          </a>
        </li>

        <!-- ================= ACTIVITIES ================= -->
        <?php if (!$isStaff): ?>
          <li class="nav-item">
            <a href="activities.php" class="nav-link <?= $isActive('activities') ?>">
              <i class="nav-icon fas fa-tasks"></i>
              <p>Activities</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="add_activity.php" class="nav-link <?= $isActive('Add activity') ?>">
              <i class="nav-icon fas fa-plus-circle"></i>
              <p>Add Activity</p>
            </a>
          </li>
        <?php else: ?>
          <li class="nav-item">
            <a href="my_activities.php" class="nav-link <?= $isActive('my_activities') ?>">
              <i class="nav-icon fas fa-clipboard-list"></i>
              <p>My Activities</p>
            </a>
          </li>
        <?php endif; ?>

        <!-- ================= PERSONAL ================= -->
        <li class="nav-item">
          <a href="my_notes.php" class="nav-link <?= $isActive('my_notes') ?>">
            <i class="nav-icon fas fa-sticky-note"></i>
            <p>My Notes</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="calendar.php" class="nav-link <?= $isActive('calendar') ?>">
            <i class="nav-icon fas fa-calendar-alt"></i>
            <p>Calendar</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="profile.php" class="nav-link <?= $isActive('profile') ?>">
            <i class="nav-icon fas fa-user-cog"></i>
            <p>My Profile</p>
          </a>
        </li>

        <li class="nav-item">
          <a href="faq.php" class="nav-link <?= $isActive('faq') ?>">
            <i class="nav-icon fas fa-question-circle"></i>
            <p>FAQ</p>
          </a>
        </li>

        <!-- ================= ADMIN ================= -->
        <?php if ($isAdmin): ?>
          <li class="nav-header text-muted px-3 mt-3">ADMIN</li>

          <li class="nav-item">
            <a href="add_department.php" class="nav-link <?= $isActive('add_department') ?>">
              <i class="nav-icon fas fa-building"></i>
              <p>Add Department</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="add_user.php" class="nav-link <?= $isActive('add_user') ?>">
              <i class="nav-icon fas fa-user-plus"></i>
              <p>Add User</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="users_report.php" class="nav-link <?= $isActive('users_report') ?>">
              <i class="nav-icon fas fa-file-alt"></i>
              <p>Users Report</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="user_delete_requests.php"
               class="nav-link <?= $isActive('user_delete_requests') ?>">
              <i class="nav-icon fas fa-user-slash"></i>
              <p>User Delete Requests</p>
            </a>
          </li>

    
        <?php endif; ?>

        <!-- ================= HOD ================= -->
        <?php if ($isHOD): ?>
          <li class="nav-header text-muted px-3 mt-3">HOD</li>

          <li class="nav-item">
            <a href="add_user.php" class="nav-link <?= $isActive('add_user') ?>">
              <i class="nav-icon fas fa-user-plus"></i>
              <p>Add Staff</p>
            </a>
          </li>

          <li class="nav-item">
            <a href="users_report.php" class="nav-link <?= $isActive('users_report') ?>">
              <i class="nav-icon fas fa-users"></i>
              <p>Users Report</p>
            </a>
          </li>
        <?php endif; ?>
		

      </ul>
    </nav>
  </div>
</aside>
