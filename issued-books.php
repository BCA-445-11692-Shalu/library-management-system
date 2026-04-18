<?php
// issued-books.php — Student's issued books with fine tracking
require_once 'includes/config.php';

if (empty($_SESSION['student_id'])) {
    header('Location: index.php'); exit;
}

$sid = $_SESSION['student_id'];
$page_title = 'My Issued Books';

$tab = $_GET['tab'] ?? 'active'; // active | history

// Active issues
$activeStmt = $pdo->prepare("
    SELECT i.*, b.title, b.isbn, b.cover, a.name AS author_name
    FROM tblissuedbookdetails i
    JOIN tblbooks b ON b.id = i.book_id
    LEFT JOIN tblauthors a ON a.id = b.author_id
    WHERE i.student_id = ? AND i.return_status = 0
    ORDER BY i.issue_date DESC
");
$activeStmt->execute([$sid]);
$active = $activeStmt->fetchAll();

// History
$histStmt = $pdo->prepare("
    SELECT i.*, b.title, b.isbn, a.name AS author_name
    FROM tblissuedbookdetails i
    JOIN tblbooks b ON b.id = i.book_id
    LEFT JOIN tblauthors a ON a.id = b.author_id
    WHERE i.student_id = ? AND i.return_status = 1
    ORDER BY i.return_date DESC
");
$histStmt->execute([$sid]);
$history = $histStmt->fetchAll();

include 'includes/header.php';
?>

<div class="page-wrap">
  <div class="page-hero">
    <h1>My Issued Books</h1>
    <p>Track your borrowed books, due dates, and fines</p>
  </div>

  <!-- Tabs -->
  <div style="display:flex;gap:.5rem;margin-bottom:1.5rem">
    <a href="?tab=active" class="btn <?= $tab==='active' ? 'btn-primary' : 'btn-outline' ?>">
      <i class="fa fa-bookmark"></i> Currently Issued (<?= count($active) ?>)
    </a>
    <a href="?tab=history" class="btn <?= $tab==='history' ? 'btn-primary' : 'btn-outline' ?>">
      <i class="fa fa-clock-rotate-left"></i> Return History (<?= count($history) ?>)
    </a>
  </div>

  <?php if ($tab === 'active'): ?>
  <!-- Active issued books -->
  <?php if (empty($active)): ?>
  <div class="card card-body text-center" style="padding:4rem">
    <i class="fa fa-book-open" style="font-size:3rem;color:var(--border);display:block;margin-bottom:1rem"></i>
    <h3 style="font-family:var(--font-head)">No active issues</h3>
    <p style="color:var(--text-muted)">You have no books currently issued.</p>
    <a href="listed-books.php" class="btn btn-gold mt-2">Browse Books</a>
  </div>
  <?php else: ?>
  <div class="card">
    <div class="card-header">
      <span class="card-title">Currently Issued Books</span>
      <span style="font-size:.82rem;color:var(--text-muted)">Return before due date to avoid fine</span>
    </div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>#</th><th>Book Details</th><th>ISBN</th>
            <th>Issued Date</th><th>Due Date</th>
            <th>Days Left</th><th>Fine (est.)</th><th>Status</th>
          </tr>
        </thead>
        <tbody>
        <?php $cnt = 1; foreach ($active as $row):
          $fine = calculate_fine($pdo, $row->due_date);
          $now  = new DateTime();
          $due  = new DateTime($row->due_date);
          $diff = $now->diff($due);
          $overdue = $now > $due;
          $daysLeft = $overdue ? -$diff->days : $diff->days;
        ?>
        <tr>
          <td><?= $cnt++ ?></td>
          <td>
            <div style="font-weight:600;color:var(--navy)"><?= clean($row->title) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)"><?= clean($row->author_name ?? '') ?></div>
          </td>
          <td style="font-size:.82rem"><?= clean($row->isbn) ?></td>
          <td><?= date('d M Y', strtotime($row->issue_date)) ?></td>
          <td><?= date('d M Y', strtotime($row->due_date)) ?></td>
          <td>
            <?php if ($overdue): ?>
              <span class="badge badge-danger"><?= abs($daysLeft) ?> days overdue</span>
            <?php elseif ($daysLeft <= 3): ?>
              <span class="badge badge-warning"><?= $daysLeft ?> days left</span>
            <?php else: ?>
              <span class="badge badge-success"><?= $daysLeft ?> days left</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($fine > 0): ?>
              <span class="fine-badge">₹<?= number_format($fine, 2) ?></span>
            <?php else: ?>
              <span style="color:var(--success)">₹0.00</span>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-warning">Issued</span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="alert alert-info mt-2">
    <i class="fa fa-circle-info"></i>
    To return a book, please visit the library desk. The librarian will update the return status.
  </div>
  <?php endif; ?>

  <?php else: ?>
  <!-- Return history -->
  <?php if (empty($history)): ?>
  <div class="card card-body text-center" style="padding:4rem">
    <i class="fa fa-clock-rotate-left" style="font-size:3rem;color:var(--border);display:block;margin-bottom:1rem"></i>
    <p style="color:var(--text-muted)">No return history found.</p>
  </div>
  <?php else: ?>
  <div class="card">
    <div class="card-header"><span class="card-title">Return History</span></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr><th>#</th><th>Book</th><th>Issued</th><th>Due Date</th><th>Returned</th><th>Fine Paid</th><th>Status</th></tr>
        </thead>
        <tbody>
        <?php $cnt = 1; foreach ($history as $row): ?>
        <tr>
          <td><?= $cnt++ ?></td>
          <td>
            <div style="font-weight:600"><?= clean($row->title) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted)"><?= clean($row->author_name ?? '') ?></div>
          </td>
          <td><?= date('d M Y', strtotime($row->issue_date)) ?></td>
          <td><?= date('d M Y', strtotime($row->due_date)) ?></td>
          <td><?= $row->return_date ? date('d M Y', strtotime($row->return_date)) : '-' ?></td>
          <td>
            <?php if ($row->fine_amount > 0): ?>
              <span class="fine-badge">₹<?= number_format($row->fine_amount, 2) ?></span>
            <?php else: ?>
              <span style="color:var(--success)">₹0.00</span>
            <?php endif; ?>
          </td>
          <td><span class="badge badge-success"><i class="fa fa-check"></i> Returned</span></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
