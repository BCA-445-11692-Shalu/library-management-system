<?php
// dashboard.php — Student dashboard
require_once 'includes/config.php';

if (empty($_SESSION['student_id'])) {
    header('Location: index.php'); exit;
}

$sid = $_SESSION['student_id'];
$page_title = 'Dashboard';

// Stats
$totalBooks   = $pdo->query("SELECT COUNT(*) FROM tblbooks")->fetchColumn();
$issuedBooks  = $pdo->prepare("SELECT COUNT(*) FROM tblissuedbookdetails WHERE student_id=? AND return_status=0");
$issuedBooks->execute([$sid]); $issued = $issuedBooks->fetchColumn();

$historyStmt = $pdo->prepare("SELECT COUNT(*) FROM tblissuedbookdetails WHERE student_id=?");
$historyStmt->execute([$sid]); $totalIssued = $historyStmt->fetchColumn();

// Active issues with fine info
$activeStmt = $pdo->prepare("
    SELECT i.*, b.title, b.isbn, b.cover, a.name AS author_name
    FROM tblissuedbookdetails i
    JOIN tblbooks b ON b.id = i.book_id
    LEFT JOIN tblauthors a ON a.id = b.author_id
    WHERE i.student_id = ? AND i.return_status = 0
    ORDER BY i.issue_date DESC
");
$activeStmt->execute([$sid]);
$activeIssues = $activeStmt->fetchAll();

// Total fine pending
$fineStmt = $pdo->prepare("SELECT COALESCE(SUM(fine_amount),0) FROM tblissuedbookdetails WHERE student_id=? AND return_status=0 AND fine_paid=0");
$fineStmt->execute([$sid]);
$pendingFine = $fineStmt->fetchColumn();

// Notifications
$notifStmt = $pdo->prepare("SELECT * FROM tbl_notifications WHERE user_id=(SELECT id FROM tblstudents WHERE student_id=?) AND user_type='student' AND is_read=0 ORDER BY created_at DESC LIMIT 5");
$notifStmt->execute([$sid]);
$notifications = $notifStmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-wrap">
  <!-- Welcome hero -->
  <div class="page-hero">
    <h1>Hello, <?= clean($_SESSION['student_name']) ?> 👋</h1>
    <p>Your student ID: <strong><?= clean($sid) ?></strong> &nbsp;|&nbsp; <?= date('l, d F Y') ?></p>
  </div>

  <!-- Stat cards -->
  <div class="stat-grid">
    <div class="stat-card">
      <div>
        <div class="stat-val"><?= $totalBooks ?></div>
        <div class="stat-label">Books Available</div>
      </div>
      <div class="stat-icon navy"><i class="fa fa-book"></i></div>
    </div>
    <div class="stat-card">
      <div>
        <div class="stat-val"><?= $issued ?></div>
        <div class="stat-label">Currently Issued</div>
      </div>
      <div class="stat-icon gold"><i class="fa fa-bookmark"></i></div>
    </div>
    <div class="stat-card">
      <div>
        <div class="stat-val"><?= $totalIssued ?></div>
        <div class="stat-label">Total Borrowed</div>
      </div>
      <div class="stat-icon green"><i class="fa fa-rotate-left"></i></div>
    </div>
    <div class="stat-card">
      <div>
        <div class="stat-val" style="<?= $pendingFine > 0 ? 'color:var(--danger)' : '' ?>">
          ₹<?= number_format($pendingFine, 2) ?>
        </div>
        <div class="stat-label">Pending Fine</div>
      </div>
      <div class="stat-icon <?= $pendingFine > 0 ? 'red' : 'green' ?>">
        <i class="fa fa-indian-rupee-sign"></i>
      </div>
    </div>
  </div>

  <!-- Notifications -->
  <?php if (!empty($notifications)): ?>
  <div style="margin-bottom:1.5rem">
    <?php foreach ($notifications as $n): ?>
    <div class="alert alert-info" style="margin-bottom:.5rem">
      <i class="fa fa-bell"></i>
      <div><strong><?= clean($n->title) ?></strong> — <?= clean($n->message) ?>
        <span style="font-size:.75rem;color:var(--text-muted);margin-left:.5rem"><?= date('d M', strtotime($n->created_at)) ?></span>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start">

    <!-- Active issues -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">Currently Issued Books</span>
        <a href="issued-books.php" class="btn btn-sm btn-outline">View All</a>
      </div>
      <?php if (empty($activeIssues)): ?>
      <div class="card-body text-center" style="padding:3rem">
        <i class="fa fa-book-open" style="font-size:3rem;color:var(--border);margin-bottom:1rem;display:block"></i>
        <p style="color:var(--text-muted)">No books currently issued</p>
        <a href="listed-books.php" class="btn btn-gold mt-2">Browse Books</a>
      </div>
      <?php else: ?>
      <div class="table-wrap">
        <table>
          <thead>
            <tr><th>Book</th><th>Issued</th><th>Due Date</th><th>Fine</th><th>Status</th></tr>
          </thead>
          <tbody>
          <?php foreach ($activeIssues as $iss):
            $fine = calculate_fine($pdo, $iss->due_date);
            $overdue = new DateTime() > new DateTime($iss->due_date);
          ?>
          <tr>
            <td>
              <div style="font-weight:600;color:var(--navy)"><?= clean($iss->title) ?></div>
              <div style="font-size:.78rem;color:var(--text-muted)"><?= clean($iss->author_name ?? '') ?></div>
            </td>
            <td><?= date('d M Y', strtotime($iss->issue_date)) ?></td>
            <td>
              <?= date('d M Y', strtotime($iss->due_date)) ?>
              <?php if ($overdue): ?><br><span class="badge badge-danger">Overdue</span><?php endif; ?>
            </td>
            <td>
              <?php if ($fine > 0): ?>
                <span class="fine-badge">₹<?= number_format($fine, 2) ?></span>
              <?php else: ?>
                <span class="badge badge-success">No Fine</span>
              <?php endif; ?>
            </td>
            <td><span class="badge badge-warning">Issued</span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>
    </div>

    <!-- Quick links -->
    <div style="display:flex;flex-direction:column;gap:1rem">
      <div class="card card-body">
        <h3 style="font-family:var(--font-head);font-size:1rem;margin-bottom:1rem">Quick Actions</h3>
        <div style="display:flex;flex-direction:column;gap:.6rem">
          <a href="listed-books.php" class="btn btn-primary"><i class="fa fa-search"></i> Browse Books</a>
          <a href="issued-books.php" class="btn btn-outline"><i class="fa fa-list"></i> My Issued Books</a>
          <a href="my-profile.php" class="btn btn-outline"><i class="fa fa-user"></i> My Profile</a>
          <a href="change-password.php" class="btn btn-outline"><i class="fa fa-lock"></i> Change Password</a>
        </div>
      </div>

      <?php if ($pendingFine > 0): ?>
      <div class="card card-body" style="border-left:4px solid var(--danger)">
        <div style="display:flex;align-items:center;gap:.5rem;color:var(--danger);font-weight:700;margin-bottom:.5rem">
          <i class="fa fa-triangle-exclamation"></i> Fine Alert
        </div>
        <p style="font-size:.85rem;color:var(--text-muted)">You have a pending fine of</p>
        <div style="font-size:1.8rem;font-weight:700;color:var(--danger)">₹<?= number_format($pendingFine, 2) ?></div>
        <p style="font-size:.75rem;color:var(--text-muted);margin-top:.3rem">Please return overdue books to stop fine accumulation.</p>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
