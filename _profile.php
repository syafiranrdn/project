<?php
include 'auth.php';
include '../database.php';

/* =============================
   LOAD USER DATA (DB)
============================= */
$user_id = (int)($_SESSION['user_id'] ?? 0);
$activePage = 'profile';

$stmt = mysqli_prepare(
    $conn,
    "SELECT name, email, role, theme, profile_photo
     FROM users
     WHERE user_id=?"
);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$res  = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($res);

if (!$user) {
    // safety fallback
    header("Location: logout.php");
    exit;
}

/* =============================
   SESSION THEME + PHOTO SYNC
============================= */
$_SESSION['theme'] = $user['theme'] ?? ($_SESSION['theme'] ?? 'light');
$_SESSION['profile_photo'] = $user['profile_photo'] ?? ($_SESSION['profile_photo'] ?? null);

$themeClass = ($_SESSION['theme'] === 'dark') ? 'dark-mode' : '';
$roleColor  = ($_SESSION['role'] === 'Admin') ? 'danger' : 'info';
$pageTitle  = 'My Profile';

$message = '';
$error   = '';

/* =============================
   HANDLE PROFILE PHOTO UPLOAD
============================= */
if (isset($_POST['upload_photo']) && isset($_FILES['photo'])) {

    if ($_FILES['photo']['error'] === 0) {

        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];

        if (in_array($ext, $allowed)) {

            if (!is_dir('uploads/profile')) {
                mkdir('uploads/profile', 0777, true);
            }

            // overwrite by user id (simple & clean)
            $filename = 'user_' . $user_id . '.' . $ext;
            $path = 'uploads/profile/' . $filename;

            if (move_uploaded_file($_FILES['photo']['tmp_name'], $path)) {

                $up = mysqli_prepare($conn, "UPDATE users SET profile_photo=? WHERE user_id=?");
                mysqli_stmt_bind_param($up, "si", $filename, $user_id);
                mysqli_stmt_execute($up);

                $_SESSION['profile_photo'] = $filename;
                $user['profile_photo'] = $filename;

                $message = "Profile photo updated.";
            } else {
                $error = "Failed to upload file. Please try again.";
            }

        } else {
            $error = "Only JPG, JPEG, PNG or WEBP allowed.";
        }
    } else {
        $error = "Upload error. Please try again.";
    }
}

/* =============================
   UPDATE PROFILE INFO
============================= */
if (isset($_POST['update_profile'])) {

    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $theme = $_POST['theme'] ?? 'light';

    if ($name === '' || $email === '') {
        $error = "Name and email are required.";
    } else {

        // duplicate email check
        $check = mysqli_prepare(
            $conn,
            "SELECT user_id FROM users WHERE email=? AND user_id!=?"
        );
        mysqli_stmt_bind_param($check, "si", $email, $user_id);
        mysqli_stmt_execute($check);
        mysqli_stmt_store_result($check);

        if (mysqli_stmt_num_rows($check) > 0) {
            $error = "Email already exists.";
        } else {

            $update = mysqli_prepare(
                $conn,
                "UPDATE users SET name=?, email=?, theme=? WHERE user_id=?"
            );
            mysqli_stmt_bind_param($update, "sssi", $name, $email, $theme, $user_id);

            if (mysqli_stmt_execute($update)) {

                $_SESSION['name']  = $name;
                $_SESSION['email'] = $email;
                $_SESSION['theme'] = $theme;

                $user['name']  = $name;
                $user['email'] = $email;
                $user['theme'] = $theme;

                $themeClass = ($theme === 'dark') ? 'dark-mode' : '';

                $message = "Profile updated successfully.";
            } else {
                $error = "Failed to update profile.";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>My Profile | Monitoring System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro">
<link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<link rel="stylesheet" href="dist/css/custom.css">

<style>
/* =====================================================
   GLOBAL THEME VARIABLES
===================================================== */
:root{
  --nav-bg:#100c08;
  --magenta:#ff0090;
  --magenta-soft:#f49ac2;
  --magenta-pastel:#fde6ef;
  --magenta-glass:rgba(255,0,144,.12);
  --text-white:#ffffff;
  --text-muted:#b5b5b5;
  --text-dark:#222;
}

/* =====================================================
   🔒 NAVBAR — FORCE BLACK (FIX WHITE HEADER)
===================================================== */
.main-header.navbar{
  background:var(--nav-bg)!important;
  border-bottom:1px solid rgba(255,0,144,.25);
}

/* DO NOT NUKE BACKGROUND WITH * (THIS CAUSED YOUR BUG) */
.main-header.navbar *{
  transition:none!important;
  animation:none!important;
}

/* =====================================================
   NAVBAR TEXT
===================================================== */
.main-header .nav-link,
.main-header .nav-link span,
.main-header a{
  color:var(--text-white)!important;
}

.main-header .text-white-50{
  color:var(--text-muted)!important;
}

/* =====================================================
   NAVBAR ICONS — MAGENTA
===================================================== */
.main-header i,
.main-header .fas,
.main-header .far,
.main-header .nav-icon{
  color:var(--magenta)!important;
}

/* ❌ NO HOVER EFFECT (AS REQUESTED) */
.main-header .nav-link:hover{
  background:transparent!important;
}

/* =====================================================
   AVATAR
===================================================== */
.navbar-avatar{
  width:36px;
  height:36px;
  border-radius:50%;
  object-fit:cover;
  border:2px solid rgba(255,255,255,.2);
}

.navbar-avatar-icon{
  color:var(--magenta)!important;
}

/* =====================================================
   🔒 BADGES — ADMIN / HOD / STAFF (MAGENTA ONLY)
===================================================== */
.badge,
.badge-primary,
.badge-info,
.badge-warning,
.badge-danger,
.badge-success,
.badge-secondary{
  background:linear-gradient(
    90deg,
    var(--magenta),
    var(--magenta-soft)
  ) !important;
  color:#fff!important;
  border:none!important;
  font-weight:600!important;
  box-shadow:0 2px 6px rgba(255,0,144,.35);
}

/* notification counter */
#notifBadge{
  background:var(--magenta)!important;
  color:#fff!important;
}

/* =====================================================
   PAGE TITLE
===================================================== */
.content-header h1{
  color:var(--magenta);
  font-weight:600;
}

/* =====================================================
   CARDS (PROFILE BOXES)
===================================================== */
.card{
  background:var(--magenta-pastel);
  border-radius:14px;
  border:1px solid var(--magenta-soft);
  box-shadow:0 10px 26px rgba(255,0,144,.14);
}

.card-header{
  background:transparent!important;
  border-bottom:1px solid var(--magenta-soft);
}

.card-header .card-title{
  color:var(--magenta);
  font-weight:600;
}

/* remove AdminLTE blue headers */
.card-primary > .card-header,
.card-info > .card-header{
  background:transparent!important;
}

/* =====================================================
   PROFILE PHOTO
===================================================== */
.img-circle{
  border:4px solid var(--magenta-soft);
  box-shadow:0 8px 22px rgba(255,0,144,.25);
}

/* =====================================================
   FORM LABELS
===================================================== */
label{
  color:var(--magenta);
  font-weight:600;
}

/* =====================================================
   FORM INPUTS
===================================================== */
.form-control{
  border-radius:10px;
  border:1px solid var(--magenta-soft);
}

.form-control:focus{
  border-color:var(--magenta);
  box-shadow:0 0 0 .15rem rgba(255,0,144,.25);
}

/* =====================================================
   BUTTONS
===================================================== */
.btn-primary,
.btn-info{
  background:linear-gradient(
    90deg,
    var(--magenta),
    var(--magenta-soft)
  ) !important;
  border:none!important;
  color:#fff!important;
}

.btn-primary:hover,
.btn-info:hover{
  opacity:.9;
}

/* =====================================================
   ALERTS
===================================================== */
.alert-success{
  background:rgba(255,0,144,.12);
  border:1px solid var(--magenta-soft);
  color:var(--text-dark);
}

.alert-danger{
  background:rgba(255,153,153,.2);
  border:1px solid #ff9999;
  color:var(--text-dark);
}

/* =====================================================
   TABLES (CONSISTENT WITH OTHER PAGES)
===================================================== */
.table thead th{
  color:var(--magenta);
  border-bottom:1px solid var(--magenta-soft);
}

.table tbody tr:hover{
  background:rgba(255,0,144,.05);
}

/* =====================================================
   🔥 KILL ADMINLTE BLUE OUTLINE (CARD-OUTLINE FIX)
===================================================== */

/* Card outline border */
.card.card-outline{
  border:1px solid var(--magenta-soft)!important;
}

/* Remove AdminLTE blue top border */
.card-primary.card-outline,
.card-info.card-outline,
.card-success.card-outline,
.card-warning.card-outline,
.card-danger.card-outline{
  border-top:1px solid var(--magenta-soft)!important;
}

/* Ensure header does not reintroduce color */
.card.card-outline > .card-header{
  border-bottom:1px solid var(--magenta-soft)!important;
}

/* Safety: kill any leftover blue */
.card.card-outline::before,
.card.card-outline::after{
  display:none!important;
}


/* =====================================================
   FOOTER
===================================================== */
.main-footer{
  border-top:1px solid var(--magenta-soft);
  background:#fff;
  color:#666;
}


<style/>
</head>

<body class="hold-transition sidebar-mini layout-fixed <?= $themeClass ?>">
<div class="wrapper">

<?php include 'partials/navbar.php'; ?>
<?php include 'partials/sidebar.php'; ?>



<!-- ================= CONTENT ================= -->
<div class="content-wrapper">

  <section class="content-header">
    <div class="container-fluid">
      <h1>My Profile</h1>
    </div>
  </section>

  <section class="content">
    <div class="container-fluid">

      <?php if ($message) { ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
      <?php } ?>

      <?php if ($error) { ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php } ?>

      <div class="row">

        <!-- LEFT: PHOTO -->
        <div class="col-md-4">
          <div class="card card-outline card-primary">
            <div class="card-header">
              <h3 class="card-title">Profile Photo</h3>
            </div>

            <div class="card-body d-flex flex-column align-items-center justify-content-center">
              <img
			  src="<?= !empty($user['profile_photo'])
				? 'uploads/profile/'.htmlspecialchars($user['profile_photo'])
				: 'dist/img/avatar.png'
			  ?>"
			  class="avatar-circle mb-3"
			  style="width:140px;height:140px"
			  alt="Profile Photo"
				>


              <form method="POST" enctype="multipart/form-data">
                <div class="form-group mb-2">
                  <input type="file" name="photo" class="form-control" required>
                </div>
                <button type="submit" name="upload_photo" class="btn btn-info btn-block">
                  <i class="fas fa-upload"></i> Upload Photo
                </button>
              </form>

              <small class="text-muted d-block mt-2">
                Allowed: JPG, PNG, WEBP
              </small>
            </div>
          </div>
        </div>

        <!-- RIGHT: PROFILE INFO -->
        <div class="col-md-8">
          <div class="card card-primary">
            <div class="card-header">
              <h3 class="card-title">Profile Information</h3>
            </div>

            <form method="POST" autocomplete="off">
              <div class="card-body">

                <div class="form-group">
                  <label>Full Name</label>
                  <input type="text" name="name" class="form-control"
                         value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>

                <div class="form-group">
                  <label>Email Address</label>
                  <input type="email" name="email" class="form-control"
                         value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>


              </div>

              <div class="card-footer">
                <button type="submit" name="update_profile" class="btn btn-primary">
                  <i class="fas fa-save"></i> Save Changes
                </button>
              </div>

            </form>
          </div>
        </div>

      </div>

    </div>
  </section>
</div>

<footer class="main-footer">
  <strong>Monitoring System</strong>
</footer>

</div>

<!-- ================= SCRIPTS ================= -->
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="dist/js/adminlte.min.js"></script>

</body>
</html>
