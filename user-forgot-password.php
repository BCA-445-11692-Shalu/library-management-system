<?php
// user-forgot-password.php — Token-based password reset
require_once 'includes/config.php';

$step = $_GET['step'] ?? 'email'; // email | reset
$token = $_GET['token'] ?? '';
$msg = $err = '';

// ── Step 2: Token reset form ─────────────────────────────
if ($step === 'reset' && $token) {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("SELECT * FROM tblstudents WHERE reset_token=? AND token_expiry > ?");
    $stmt->execute([$token, $now]);
    $user = $stmt->fetch();

    if (!$user) {
        $err = 'This reset link is invalid or has expired. Please request a new one.';
        $step = 'email';
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
        verify_csrf();
        $newPass  = $_POST['new_password'] ?? '';
        $confPass = $_POST['confirm_password'] ?? '';
        if (strlen($newPass) < 8) {
            $err = 'Password must be at least 8 characters.';
        } elseif ($newPass !== $confPass) {
            $err = 'Passwords do not match.';
        } else {
            $hashed = md5($newPass);
            $pdo->prepare("UPDATE tblstudents SET password=?, reset_token=NULL, token_expiry=NULL WHERE student_id=?")
                ->execute([$hashed, $user->student_id]);
            set_flash('success', 'Password reset successfully! You can now login.');
            header('Location: index.php'); exit;
        }
    }
}

// ── Step 1: Email submission ─────────────────────────────
if ($step === 'email' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_reset'])) {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $stmt  = $pdo->prepare("SELECT * FROM tblstudents WHERE email=? AND status=1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $tok    = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $pdo->prepare("UPDATE tblstudents SET reset_token=?, token_expiry=? WHERE email=?")
            ->execute([$tok, $expiry, $email]);

        $link = APP_URL . "/user-forgot-password.php?step=reset&token=$tok";
        // In production, send actual email via PHPMailer/SMTP
        // For dev: show the link
        $msg = "Reset link generated! <br><strong>Dev mode link:</strong> <a href='$link'>$link</a><br>
                <small>(In production, this link is emailed to: $email)</small>";
    } else {
        // Don't reveal if email exists (security)
        $msg = "If this email is registered, a reset link has been sent.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Forgot Password — <?= APP_NAME ?></title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    .forgot-wrap {
      min-height: 100vh;
      background: linear-gradient(135deg, var(--navy) 0%, #1e3a5f 100%);
      display: flex; align-items: center; justify-content: center; padding: 2rem;
    }
    .forgot-box {
      background: var(--white);
      border-radius: var(--radius-lg);
      padding: 2.5rem;
      width: 100%; max-width: 440px;
      box-shadow: var(--shadow-lg);
    }
  </style>
</head>
<body>
<div class="forgot-wrap">
  <div class="forgot-box">
    <div style="text-align:center;margin-bottom:2rem">
      <div style="font-size:2.5rem;color:var(--gold);margin-bottom:.5rem"><i class="fa fa-lock-open"></i></div>
      <h2 style="font-family:var(--font-head);color:var(--navy)">
        <?= $step==='reset' ? 'Set New Password' : 'Forgot Password' ?>
      </h2>
      <p style="color:var(--text-muted);font-size:.88rem">
        <?= $step==='reset' ? 'Enter your new password below' : "Enter your registered email to receive a reset link" ?>
      </p>
    </div>

    <?php if ($err): ?>
    <div class="alert alert-danger"><i class="fa fa-circle-exclamation"></i> <?= $err ?></div>
    <?php endif; ?>
    <?php if ($msg): ?>
    <div class="alert alert-success"><i class="fa fa-circle-check"></i> <?= $msg ?></div>
    <?php endif; ?>

    <?php if ($step === 'reset' && !$err): ?>
    <!-- Reset password form -->
    <form method="POST">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" class="form-control" placeholder="Min 8 characters" required>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" placeholder="Repeat password" required>
      </div>
      <button type="submit" name="reset_password" class="btn btn-primary" style="width:100%;justify-content:center">
        <i class="fa fa-key"></i> Reset Password
      </button>
    </form>

    <?php elseif (!$msg): ?>
    <!-- Email form -->
    <form method="POST">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">Registered Email</label>
        <input type="email" name="email" class="form-control" placeholder="your@email.com"
               value="<?= clean($_POST['email'] ?? '') ?>" required>
      </div>
      <button type="submit" name="send_reset" class="btn btn-primary" style="width:100%;justify-content:center">
        <i class="fa fa-paper-plane"></i> Send Reset Link
      </button>
    </form>
    <?php endif; ?>

    <p style="text-align:center;margin-top:1.5rem;font-size:.85rem">
      <a href="index.php" style="color:var(--navy)"><i class="fa fa-arrow-left"></i> Back to Login</a>
    </p>
  </div>
</div>
</body>
</html>
