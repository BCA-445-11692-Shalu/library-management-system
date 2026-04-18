<?php
// change-password.php — Logged-in user password change
require_once 'includes/config.php';

if (empty($_SESSION['student_id'])) {
    header('Location: index.php'); exit;
}

$sid = $_SESSION['student_id'];
$page_title = 'Change Password';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $old  = $_POST['old_password'] ?? '';
    $new  = $_POST['new_password'] ?? '';
    $conf = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM tblstudents WHERE student_id=?");
    $stmt->execute([$sid]);
    $user = $stmt->fetch();

    if (md5($old) !== $user->password) {
        set_flash('danger', 'Current password is incorrect.');
    } elseif (strlen($new) < 8) {
        set_flash('danger', 'New password must be at least 8 characters.');
    } elseif ($new !== $conf) {
        set_flash('danger', 'New passwords do not match.');
    } else {
        $hashed = md5($new);
        $pdo->prepare("UPDATE tblstudents SET password=? WHERE student_id=?")->execute([$hashed, $sid]);
        set_flash('success', 'Password changed successfully!');
    }
    header('Location: change-password.php'); exit;
}

include 'includes/header.php';
?>
<div class="page-wrap" style="max-width:500px">
  <div class="page-hero"><h1>Change Password</h1></div>
  <div class="card card-body">
    <form method="POST">
      <?= csrf_field() ?>
      <div class="form-group">
        <label class="form-label">Current Password</label>
        <input type="password" name="old_password" class="form-control" required>
      </div>
      <div class="form-group">
        <label class="form-label">New Password</label>
        <input type="password" name="new_password" class="form-control" required>
        <div class="form-hint" style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem">Min. 8 characters</div>
      </div>
      <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <input type="password" name="confirm_password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fa fa-lock"></i> Update Password</button>
    </form>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
