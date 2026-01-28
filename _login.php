<?php
// auth/login.php  (or wherever your login.php is)
session_start();
require_once __DIR__ . '/../database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {

    $email    = trim($_POST['email'] ?? '');
    $password = (string)($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = "Please enter email and password.";
    } else {

        // ✅ Use prepare safely
        $stmt = $conn->prepare("
            SELECT user_id, name, email, password, role, status, admin_level, department_id
            FROM users
            WHERE email = ?
            LIMIT 1
        ");

        if (!$stmt) {
            $error = "Server error: failed to prepare query.";
        } else {

            $stmt->bind_param("s", $email);
            $stmt->execute();
            $res  = $stmt->get_result();
            $user = $res ? $res->fetch_assoc() : null;
            $stmt->close();

            // ✅ OPTIONAL DEBUG (TEMPORARY)
            // Uncomment only for debugging login issue, then comment back.
            // header('Content-Type: text/plain; charset=utf-8');
            // var_dump($user);
            // exit;

            if (!$user) {
                $error = "Invalid email or password.";
            } else {

                // ✅ Block inactive accounts
                if (($user['status'] ?? 'Active') !== 'Active') {
                    $error = "Your account is not active. Please contact admin.";
                } else {

                    // ✅ Password verify
                    if (!password_verify($password, (string)$user['password'])) {
                        $error = "Invalid email or password.";
                    } else {

                        // ✅ Allow only Admin & Staff
                        if (!in_array($user['role'], ['Admin', 'Staff'], true)) {
                            $error = "Your account is not authorized to access this system.";
                        } else {

                            // ✅ Login success
                            session_regenerate_id(true);

                            $_SESSION['user_id']       = (int)$user['user_id'];
                            $_SESSION['name']          = $user['name'];
                            $_SESSION['email']         = $user['email'];
                            $_SESSION['role']          = $user['role'];

                            // ✅ New fields (for your department system)
                            $_SESSION['admin_level']   = $user['admin_level'] ?? null;
                            $_SESSION['department_id'] = isset($user['department_id']) ? (int)$user['department_id'] : null;

                            // Let auth.php reload profile properly
                            unset($_SESSION['profile_loaded']);

                            // ✅ Redirect to admin dashboard (adjust if your dashboard path differs)
                            header("Location: ../admin/index.php");
                            exit;
                        }
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Login | Monitoring System</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro">
<link rel="stylesheet" href="../admin/plugins/fontawesome-free/css/all.min.css">
<link rel="stylesheet" href="../admin/dist/css/adminlte.min.css">

<style>
.toggle-password { cursor: pointer; }

/* =====================================================
   LOGIN – PASTEL MAGENTA THEME
===================================================== */
:root{
  --magenta:#ff0090;
  --magenta-strong:#e60080;
  --magenta-soft:#f49ac2;
  --magenta-pastel:#fde6ef;
  --magenta-glass:rgba(255,0,144,.08);
  --salmon:#ff9999;
  --text-dark:#222;
}

/* =====================================================
   PAGE BACKGROUND
===================================================== */
.login-page{
  background:
    linear-gradient(
      135deg,
      rgba(255,0,144,.10),
      rgba(244,154,194,.20)
    );
}

/* =====================================================
   LOGIN LOGO
===================================================== */
.login-logo{
  color:var(--magenta);
  font-weight:700;
}
.login-logo b{
  color:var(--magenta-strong);
}

/* =====================================================
   LOGIN CARD
===================================================== */
.login-box .card{
  background:var(--magenta-pastel);
  border-radius:16px;
  border:1px solid var(--magenta-soft);
  box-shadow:0 18px 45px rgba(255,0,144,.25);
}

.login-card-body{
  background:transparent;
  border-radius:16px;
}

/* =====================================================
   TEXT
===================================================== */
.login-box-msg{
  color:#555;
  font-weight:500;
}

/* =====================================================
   INPUT GROUP
===================================================== */
.form-control{
  border-radius:10px;
  border:1px solid var(--magenta-soft);
}

.form-control:focus{
  border-color:var(--magenta);
  box-shadow:0 0 0 .15rem rgba(255,0,144,.25);
}

/* icon box */
.input-group-text{
  background:rgba(255,0,144,.10);
  border:1px solid var(--magenta-soft);
  color:var(--magenta);
}

/* =====================================================
   PASSWORD TOGGLE
===================================================== */
.toggle-password{
  cursor:pointer;
}
.toggle-password:hover{
  background:rgba(255,0,144,.20);
}

/* =====================================================
   LOGIN BUTTON
===================================================== */
.btn-primary{
  background:linear-gradient(
    90deg,
    var(--magenta),
    var(--magenta-soft)
  );
  border:none;
  font-weight:600;
}
.btn-primary:hover{
  opacity:.9;
}

/* =====================================================
   REQUEST ACCESS BUTTON
===================================================== */
.btn-outline-secondary{
  border:1.5px solid var(--magenta-soft);
  color:var(--magenta);
}
.btn-outline-secondary:hover{
  background:var(--magenta-soft);
  color:#000;
}

/* =====================================================
   ALERTS
===================================================== */
.alert-danger{
  background:rgba(255,153,153,.25);
  border:1px solid var(--salmon);
  color:var(--text-dark);
}

/* =====================================================
   REMOVE ADMINLTE BLUE
===================================================== */
.bg-primary,
.bg-info,
.text-info,
.text-primary{
  background:transparent !important;
  color:inherit !important;
}


</style>
</head>

<body class="hold-transition login-page">

<div class="login-box">
  <div class="login-logo">
    <b>Monitoring</b>System
  </div>

  <div class="card">
    <div class="card-body login-card-body">

      <p class="login-box-msg">Sign in to start your session</p>

      <?php if ($error) { ?>
        <div class="alert alert-danger">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php } ?>

      <form method="POST" autocomplete="off">

        <!-- EMAIL -->
        <div class="input-group mb-3">
          <input type="email"
                 name="email"
                 class="form-control"
                 placeholder="Email"
                 required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>

        <!-- PASSWORD WITH TOGGLE -->
        <div class="input-group mb-3">
          <input type="password"
                 name="password"
                 id="password"
                 class="form-control"
                 placeholder="Password"
                 required>

          <div class="input-group-append">
            <div class="input-group-text toggle-password" id="togglePassword">
              <i class="fas fa-eye"></i>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <button type="submit"
                    name="login"
                    class="btn btn-primary btn-block">
              <i class="fas fa-sign-in-alt"></i> Sign In
            </button>
          </div>
        </div>

      </form>

      <hr>

      <!-- REQUEST ACCESS -->
      <a href="request_access.php"
         class="btn btn-outline-secondary btn-block">
        <i class="fas fa-user-plus"></i> Request Access
      </a>

    </div>
  </div>
</div>

<script src="../admin/plugins/jquery/jquery.min.js"></script>
<script src="../admin/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../admin/dist/js/adminlte.min.js"></script>

<script>
document.getElementById('togglePassword').addEventListener('click', function () {
  const input = document.getElementById('password');
  const icon  = this.querySelector('i');

  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.remove('fa-eye');
    icon.classList.add('fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.remove('fa-eye-slash');
    icon.classList.add('fa-eye');
  }
});
</script>

</body>
</html>
