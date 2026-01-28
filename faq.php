<?php
include 'auth.php';
include '../database.php'; // optional, but keep for consistency
$themeClass = ($_SESSION['theme'] ?? 'light') === 'dark' ? 'dark-mode' : '';
$roleColor  = $_SESSION['role'] === 'Admin' ? 'danger' : 'info';
$pageTitle  = 'FAQ';
$activePage = 'faq';

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>FAQ</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro">
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <link rel="stylesheet" href="dist/css/custom.css">
  
<style>
  
  /* =====================================================
   FAQ – PASTEL MAGENTA THEME (NO BLUE)
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
   🔥 HARD REMOVE ADMINLTE BLUE
===================================================== */
.bg-primary,
.bg-info,
.card-primary:not(.card-outline) > .card-header,
.card-info:not(.card-outline) > .card-header{
  background:var(--magenta-pastel) !important;
  color:var(--text-dark) !important;
  border-color:var(--magenta-soft) !important;
}

/* =====================================================
   PAGE TITLE
===================================================== */
.content-header h1{
  color:var(--magenta);
  font-weight:600;
}

/* =====================================================
   FAQ MAIN CARD
===================================================== */
.card{
  background:var(--magenta-pastel);
  border-radius:14px;
  border:1px solid var(--magenta-soft);
  box-shadow:0 10px 26px rgba(255,0,144,.12);
}

/* Card outline fix */
.card.card-outline{
  border:1px solid var(--magenta-soft) !important;
}

/* =====================================================
   CARD HEADER
===================================================== */
.card-header{
  background:transparent !important;
  border-bottom:1px solid var(--magenta-soft);
}

.card-header .card-title{
  color:var(--magenta);
  font-weight:600;
}

/* =====================================================
   FAQ ACCORDION ITEMS
===================================================== */
#faqAccordion .card{
  background:#fff;
  border-radius:12px;
  margin-bottom:12px;
  border:1px solid var(--magenta-soft);
  box-shadow:0 6px 18px rgba(255,0,144,.10);
}

/* Question header */
#faqAccordion .card-header{
  background:var(--magenta-glass);
  border-bottom:1px solid var(--magenta-soft);
}

/* =====================================================
   QUESTION BUTTON (REMOVE LINK BLUE)
===================================================== */
#faqAccordion .btn-link{
  color:var(--magenta);
  font-weight:600;
  text-decoration:none;
  width:100%;
  text-align:left;
}

#faqAccordion .btn-link:hover{
  color:var(--magenta-strong);
  text-decoration:none;
}

/* Active (expanded) question */
#faqAccordion .btn-link:not(.collapsed){
  color:var(--magenta-strong);
}

/* =====================================================
   ANSWER BODY
===================================================== */
#faqAccordion .card-body{
  background:#fff;
  color:var(--text-dark);
  border-top:1px dashed var(--magenta-soft);
  line-height:1.6;
}

/* Lists inside FAQ */
#faqAccordion ul{
  padding-left:18px;
}
#faqAccordion li{
  margin-bottom:4px;
}

/* =====================================================
   ACCESS & SECURITY CARD (SECOND CARD)
===================================================== */
.card-outline.card-secondary{
  background:var(--magenta-pastel);
  border:1px solid var(--magenta-soft);
}

.card-outline.card-secondary .card-title{
  color:var(--magenta);
}

/* =====================================================
   ICON COLOR
===================================================== */
.card-title i{
  color:var(--magenta);
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

<?php $pageTitle = 'Dashboard'; ?>
<?php include 'partials/navbar.php'; ?>
<?php include 'partials/sidebar.php'; ?>

<!-- ================= CONTENT ================= -->
<div class="content-wrapper">

  <section class="content-header">
    <div class="container-fluid">
      <h1>FAQ</h1>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <!-- General FAQ -->
      <div class="card card-outline card-primary">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-info-circle mr-1"></i> General</h3>
        </div>

        <div class="card-body">
          <div id="faqAccordion">

            <div class="card">
              <div class="card-header" id="q1">
                <h5 class="mb-0">
                  <button class="btn btn-link" data-toggle="collapse" data-target="#a1" aria-expanded="true">
                    What is this system used for?
                  </button>
                </h5>
              </div>
              <div id="a1" class="collapse show" data-parent="#faqAccordion">
                <div class="card-body">
                  This system helps track projects/activities, statuses, timelines, and responsibilities in one place.
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="q2">
                <h5 class="mb-0">
                  <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#a2">
                    What are the user roles?
                  </button>
                </h5>
              </div>
              <div id="a2" class="collapse" data-parent="#faqAccordion">
                <div class="card-body">
                  <ul class="mb-0">
                    <li><b>Admin</b>: Full access (Add/Edit/Delete).</li>
                    <li><b>Staff</b>: Can Add/Edit, cannot Delete.</li>
                    <li><b>Viewer</b>: Read-only (no action buttons).</li>
                  </ul>
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="q3">
                <h5 class="mb-0">
                  <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#a3">
                    Why can’t I see certain buttons?
                  </button>
                </h5>
              </div>
              <div id="a3" class="collapse" data-parent="#faqAccordion">
                <div class="card-body">
                  Buttons are hidden based on your role to prevent unauthorized actions and keep the UI clean.
                </div>
              </div>
            </div>

            <div class="card">
              <div class="card-header" id="q4">
                <h5 class="mb-0">
                  <button class="btn btn-link collapsed" data-toggle="collapse" data-target="#a4">
                    How do I update progress or status?
                  </button>
                </h5>
              </div>
              <div id="a4" class="collapse" data-parent="#faqAccordion">
                <div class="card-body">
                  Open the project in Activities → click <b>Edit</b> (Staff/Admin) → update status/progress → Save.
                </div>
              </div>
            </div>

          </div>
        </div>
      </div>

      <!-- Role-specific Note -->
      <div class="card card-outline card-secondary">
        <div class="card-header">
          <h3 class="card-title"><i class="fas fa-user-shield mr-1"></i> Access & Security</h3>
        </div>
        <div class="card-body">
          <p class="mb-1"><b>Page-level protection</b> is enabled.</p>
          <p class="mb-0 text-muted">
            Even if someone tries to access a URL directly (e.g. delete page), the system blocks it based on role.
          </p>
        </div>
      </div>

    </div>
  </section>

</div>

<!-- ================= FOOTER ================= -->
<footer class="main-footer">
  <strong>Monitoring System</strong>
</footer>

</div>

<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

</body>
</html>
