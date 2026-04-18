<?php
// my-profile.php — Student profile management
require_once 'includes/config.php';

if (empty($_SESSION['student_id'])) {
    header('Location: index.php'); exit;
}

$sid = $_SESSION['student_id'];
$page_title = 'My Profile';

// Load student
$stmt = $pdo->prepare("SELECT * FROM tblstudents WHERE student_id = ?");
$stmt->execute([$sid]);
$student = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    verify_csrf();
    $name   = trim($_POST['full_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $addr   = trim($_POST['address'] ?? '');

    // Handle profile pic upload
    $pic = $student->profile_pic;
    if (!empty($_FILES['profile_pic']['name'])) {
        $newPic = upload_cover($_FILES['profile_pic'], __DIR__ . '/assets/img/');
        if ($newPic) $pic = $newPic;
        else { set_flash('danger', 'Invalid image format. Use JPG, PNG or GIF.'); header('Location: my-profile.php'); exit; }
    }

    $upd = $pdo->prepare("UPDATE tblstudents SET full_name=?,mobile=?,address=?,profile_pic=?,updated_at=NOW() WHERE student_id=?");
    $upd->execute([$name, $mobile, $addr, $pic, $sid]);
    $_SESSION['student_name'] = $name;
    $_SESSION['profile_pic']  = $pic;
    set_flash('success', 'Profile updated successfully!');
    header('Location: my-profile.php'); exit;
}

include 'includes/header.php';
?>

<div class="page-wrap">
  <div style="display:grid;grid-template-columns:1fr 2fr;gap:1.5rem;align-items:start">

    <!-- Profile card -->
    <div class="card card-body text-center">
      <img src="assets/img/<?= clean($student->profile_pic) ?>" alt="Profile"
           onerror="this.src='assets/img/default.png'"
           style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:4px solid var(--gold);margin:0 auto 1rem">
      <h3 style="font-family:var(--font-head);color:var(--navy)"><?= clean($student->full_name) ?></h3>
      <p style="color:var(--text-muted);font-size:.85rem"><?= clean($student->email) ?></p>
      <div style="margin-top:1rem">
        <span class="badge badge-gold">Student ID: <?= clean($student->student_id) ?></span>
      </div>
      <div style="margin-top:1rem;font-size:.82rem;color:var(--text-muted)">
        Member since <?= date('d M Y', strtotime($student->created_at)) ?>
      </div>
    </div>

    <!-- Edit form -->
    <div class="card">
      <div class="card-header"><span class="card-title">Update Profile</span></div>
      <div class="card-body">
        <form method="POST" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <div class="form-group">
            <label class="form-label">Full Name</label>
            <input type="text" name="full_name" class="form-control" value="<?= clean($student->full_name) ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email (read-only)</label>
            <input type="email" class="form-control" value="<?= clean($student->email) ?>" disabled>
          </div>
          <div class="form-group">
            <label class="form-label">Mobile Number</label>
            <input type="tel" name="mobile" class="form-control" value="<?= clean($student->mobile ?? '') ?>">
          </div>
          <div class="form-group">
            <label class="form-label">Address</label>
            <textarea name="address" class="form-control" rows="3"><?= clean($student->address ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label class="form-label">Profile Picture</label>
            <input type="file" name="profile_pic" class="form-control" accept="image/*">
            <div class="form-hint">JPG, PNG, GIF or WEBP (max 2MB)</div>
          </div>
          <button type="submit" name="update_profile" class="btn btn-primary">
            <i class="fa fa-save"></i> Save Changes
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
