<?php
// index.php — User login page
require_once 'includes/config.php';

// Redirect if already logged in
if (!empty($_SESSION['student_id'])) {
    header('Location: dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    verify_csrf();
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM tblstudents WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && md5($password) === $user->password) {
            if ($user->status == 0) {
                $error = 'Your account has been blocked. Contact admin.';
            } else {
                $_SESSION['student_id']   = $user->student_id;
                $_SESSION['student_name'] = $user->full_name;
                $_SESSION['student_email']= $user->email;
                $_SESSION['profile_pic']  = $user->profile_pic;
                set_flash('success', 'Welcome back, ' . $user->full_name . '!');
                header('Location: dashboard.php'); exit;
            }
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .auth-wrap {
      min-height: 100vh;
      display: grid;
      grid-template-columns: 1fr 1fr;
    }
    .auth-left {
      background: linear-gradient(160deg, var(--navy) 0%, #1e3a5f 100%);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .auth-left::before {
      content: '';
      position: absolute;
      width: 400px; height: 400px;
      border-radius: 50%;
      background: rgba(201,168,76,.06);
      right: -100px; top: -100px;
    }
    .auth-left::after {
      content: '';
      position: absolute;
      width: 250px; height: 250px;
      border-radius: 50%;
      background: rgba(201,168,76,.05);
      left: -60px; bottom: -60px;
    }
    .auth-left-logo {
      font-family: var(--font-head);
      font-size: 2.8rem;
      color: var(--gold);
      margin-bottom: 1rem;
      position: relative;
    }
    .auth-left p {
      color: rgba(255,255,255,.6);
      font-size: .95rem;
      max-width: 280px;
      line-height: 1.7;
      position: relative;
    }
    .auth-right {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 3rem 2rem;
      background: var(--cream);
    }
    .auth-box { max-width: 400px; width: 100%; }
    .auth-box h2 { font-family: var(--font-head); font-size: 1.9rem; color: var(--navy); margin-bottom: .4rem; }
    .auth-box p { color: var(--text-muted); margin-bottom: 2rem; font-size: .92rem; }
    .divider { text-align: center; position: relative; margin: 1.5rem 0; color: var(--text-muted); font-size: .82rem; }
    .divider::before { content: ''; position: absolute; top: 50%; left: 0; right: 0; height: 1px; background: var(--border); }
    .divider span { position: relative; background: var(--cream); padding: 0 .75rem; }
    @media(max-width:768px){
      .auth-wrap { grid-template-columns: 1fr; }
      .auth-left { display: none; }
    }
  </style>
</head>
<body>
<div class="auth-wrap">
  <!-- Left decorative panel -->
  <div class="auth-left">
    <div class="auth-left-logo">
      <i class="fa-solid fa-book-open-reader"></i><br>
      <?= APP_NAME ?>
    </div>
    <p>Your gateway to knowledge. Browse thousands of books, track your borrowing, and manage your reading journey.</p>
    <div style="margin-top:2rem;display:flex;gap:1rem;flex-wrap:wrap;justify-content:center;position:relative">
      <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:.75rem 1.25rem;color:rgba(255,255,255,.75);font-size:.82rem">
        <i class="fa fa-book" style="color:var(--gold);margin-right:.4rem"></i> 1000+ Books
      </div>
      <div style="background:rgba(255,255,255,.08);border-radius:10px;padding:.75rem 1.25rem;color:rgba(255,255,255,.75);font-size:.82rem">
        <i class="fa fa-users" style="color:var(--gold);margin-right:.4rem"></i> 500+ Members
      </div>
    </div>
  </div>

  <!-- Right login panel -->
  <div class="auth-right">
    <div class="auth-box">
      <h2>Welcome back</h2>
      <p>Login to access your library account</p>

      <?php if ($error): ?>
      <div class="alert alert-danger"><i class="fa fa-circle-exclamation"></i> <?= clean($error) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <?= csrf_field() ?>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="your@email.com"
                 value="<?= clean($_POST['email'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label" style="display:flex;justify-content:space-between">
            Password
            <a href="user-forgot-password.php" style="font-weight:400;color:var(--gold);font-size:.82rem">Forgot password?</a>
          </label>
          <div style="position:relative">
            <input type="password" name="password" id="pwdField" class="form-control" placeholder="••••••••" required>
            <button type="button" onclick="togglePwd()" style="position:absolute;right:.75rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted)">
              <i class="fa fa-eye" id="pwdIcon"></i>
            </button>
          </div>
        </div>
        <button type="submit" name="login" class="btn btn-primary w-100" style="width:100%;justify-content:center;padding:.75rem">
          <i class="fa fa-right-to-bracket"></i> Login
        </button>
      </form>

      <div class="divider"><span>OR</span></div>

      <p style="text-align:center;font-size:.88rem;color:var(--text-muted)">
        Don't have an account? <a href="signup.php" style="color:var(--navy);font-weight:600">Register here</a>
      </p>
      <p style="text-align:center;font-size:.82rem;margin-top:1rem">
        <a href="admin/index.php" style="color:var(--text-muted)"><i class="fa fa-shield-halved"></i> Admin Panel</a>
      </p>
    </div>
  </div>
</div>

<script>
function togglePwd() {
  var f = document.getElementById('pwdField');
  var i = document.getElementById('pwdIcon');
  f.type = f.type === 'password' ? 'text' : 'password';
  i.className = f.type === 'password' ? 'fa fa-eye' : 'fa fa-eye-slash';
}
</script>
</body>
</html>
