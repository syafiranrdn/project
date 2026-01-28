<?php
session_start();
require_once '../database.php';
require_once __DIR__ . '/notifications/notifications_create.php';

$message = '';
$error   = '';
$redirect = false;

if (isset($_POST['request'])) {

    $full_name  = strtoupper(trim($_POST['full_name'] ?? ''));
    $department = strtoupper(trim($_POST['department'] ?? ''));
    $position   = strtoupper(trim($_POST['position'] ?? ''));
    $email      = strtolower(trim($_POST['email'] ?? ''));

    if ($full_name === '' || $department === '' || $position === '' || $email === '') {
        $error = "All fields are required.";
    } else {

        /* ❗ Prevent duplicate request */
        $chk = $conn->prepare("
            SELECT request_id 
            FROM access_requests 
            WHERE email = ? AND status = 'Pending'
            LIMIT 1
        ");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();

        if ($chk->num_rows > 0) {
            $error = "You already have a pending request.";
            $chk->close();
        } else {
            $chk->close();

            $stmt = $conn->prepare("
                INSERT INTO access_requests
                (full_name, email, department, position, status)
                VALUES (?, ?, ?, ?, 'Pending')
            ");

            if ($stmt) {
                $stmt->bind_param("ssss", $full_name, $email, $department, $position);

                if ($stmt->execute()) {

                    notifyAdmins(
                        $conn,
                        "🔐 New access request: {$full_name} ({$department})",
                        'warning',
                        'users_report.php'
                    );

                    $message  = "Request submitted. Redirecting to login...";
                    $redirect = true;
                } else {
                    $error = "Failed to submit request.";
                }

                $stmt->close();
            } else {
                $error = "System error.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Request Access</title>
<link rel="stylesheet" href="dist/css/adminlte.min.css">
<?php if($redirect): ?>
<meta http-equiv="refresh" content="2;url=login.php">
<?php endif; ?>
<style>.uppercase{text-transform:uppercase}

/* =====================================================
   REQUEST ACCESS – PASTEL MAGENTA THEME
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
      rgba(244,154,194,.22)
    );
}

/* =====================================================
   CARD
===================================================== */
.login-box .card{
  background:var(--magenta-pastel);
  border-radius:16px;
  border:1px solid var(--magenta-soft);
  box-shadow:0 18px 45px rgba(255,0,144,.25);
}

.login-card-body{
  background:transparent;
}

/* =====================================================
   FORM INPUT
===================================================== */
.form-control{
  border-radius:10px;
  border:1px solid var(--magenta-soft);
}

.form-control:focus{
  border-color:var(--magenta);
  box-shadow:0 0 0 .15rem rgba(255,0,144,.25);
}

/* uppercase helper */
.uppercase{
  text-transform:uppercase;
}

/* =====================================================
   PRIMARY BUTTON (SUBMIT)
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
   BACK TO LOGIN LINK
===================================================== */
.btn-link{
  color:var(--magenta);
  font-weight:500;
}
.btn-link:hover{
  color:var(--magenta-strong);
  text-decoration:underline;
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
.text-primary,
.card-primary,
.btn-info{
  background:transparent !important;
  color:inherit !important;
  border-color:var(--magenta-soft) !important;
}



</style>
</head>

<body class="hold-transition login-page">
<div class="login-box">
<div class="card">
<div class="card-body login-card-body">

<?php if($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>
<?php if($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

<form method="POST">
<input class="form-control uppercase" name="full_name" placeholder="Full Name" required><br>
<input class="form-control uppercase" name="department" placeholder="Department" required><br>
<input class="form-control uppercase" name="position" placeholder="Position" required><br>
<input class="form-control" name="email" type="email" placeholder="Email" required><br>

<button class="btn btn-primary btn-block" name="request">Submit Request</button>
<a href="login.php" class="btn btn-link btn-block">Back to Login</a>
</form>

</div>
</div>
</div>

<script>
document.querySelectorAll('.uppercase').forEach(el=>{
  el.addEventListener('input',()=>el.value=el.value.toUpperCase());
});
</script>
</body>
</html>
