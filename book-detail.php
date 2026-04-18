<?php
// book-detail.php — Public book detail page
require_once 'includes/config.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: listed-books.php'); exit; }

$stmt = $pdo->prepare("
    SELECT b.*, a.name AS author_name, c.name AS cat_name
    FROM tblbooks b
    LEFT JOIN tblauthors a ON a.id=b.author_id
    LEFT JOIN tblcategory c ON c.id=b.cat_id
    WHERE b.id=?
");
$stmt->execute([$id]); $book = $stmt->fetch();
if (!$book) { set_flash('danger','Book not found.'); header('Location: listed-books.php'); exit; }

$page_title = $book->title;

// Related books (same category)
$related = [];
if ($book->cat_id) {
    $rel = $pdo->prepare("SELECT b.*,a.name AS author_name FROM tblbooks b LEFT JOIN tblauthors a ON a.id=b.author_id WHERE b.cat_id=? AND b.id!=? LIMIT 4");
    $rel->execute([$book->cat_id,$id]); $related = $rel->fetchAll();
}

include 'includes/header.php';
?>

<div class="page-wrap">
  <!-- Breadcrumb -->
  <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:1.5rem">
    <a href="/">Home</a> › <a href="listed-books.php">Books</a> › <?= clean($book->title) ?>
  </div>

  <div style="display:grid;grid-template-columns:220px 1fr;gap:2rem;align-items:start;margin-bottom:2.5rem">
    <!-- Cover -->
    <div>
      <?php if ($book->cover && $book->cover !== 'no-cover.jpg'): ?>
      <img src="admin/bookimg/<?= clean($book->cover) ?>" alt="<?= clean($book->title) ?>"
           style="width:100%;border-radius:var(--radius-lg);box-shadow:var(--shadow-lg);object-fit:cover"
           onerror="this.style.display='none'">
      <?php else: ?>
      <div style="width:100%;height:300px;background:linear-gradient(135deg,var(--navy),#1e3a5f);border-radius:var(--radius-lg);display:flex;align-items:center;justify-content:center;font-size:4rem;color:rgba(255,255,255,.3)">
        <i class="fa fa-book"></i>
      </div>
      <?php endif; ?>

      <!-- Availability badge -->
      <div style="margin-top:1rem;text-align:center">
        <?php if ($book->available > 0): ?>
        <span class="badge badge-success" style="font-size:.9rem;padding:.4rem 1rem">
          <i class="fa fa-check"></i> Available (<?= $book->available ?> copies)
        </span>
        <?php else: ?>
        <span class="badge badge-danger" style="font-size:.9rem;padding:.4rem 1rem">
          <i class="fa fa-xmark"></i> Not Available
        </span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Details -->
    <div>
      <h1 style="font-family:var(--font-head);font-size:2rem;color:var(--navy);margin-bottom:.5rem"><?= clean($book->title) ?></h1>

      <?php if ($book->author_name): ?>
      <p style="font-size:1rem;color:var(--text-muted);margin-bottom:1rem">by <strong><?= clean($book->author_name) ?></strong></p>
      <?php endif; ?>

      <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:1.5rem">
        <?php if ($book->cat_name): ?><span class="badge badge-gold"><?= clean($book->cat_name) ?></span><?php endif; ?>
        <?php if ($book->edition): ?><span class="badge badge-info"><?= clean($book->edition) ?> Edition</span><?php endif; ?>
      </div>

      <!-- Book metadata -->
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:1.5rem">
        <div style="background:var(--cream);border-radius:var(--radius);padding:.85rem">
          <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem">ISBN</div>
          <div style="font-weight:600"><?= clean($book->isbn) ?></div>
        </div>
        <div style="background:var(--cream);border-radius:var(--radius);padding:.85rem">
          <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem">Price</div>
          <div style="font-weight:600;color:var(--gold)">₹<?= number_format($book->price,2) ?></div>
        </div>
        <?php if ($book->publisher): ?>
        <div style="background:var(--cream);border-radius:var(--radius);padding:.85rem">
          <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem">Publisher</div>
          <div style="font-weight:600"><?= clean($book->publisher) ?></div>
        </div>
        <?php endif; ?>
        <div style="background:var(--cream);border-radius:var(--radius);padding:.85rem">
          <div style="font-size:.72rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:.25rem">Total Copies</div>
          <div style="font-weight:600"><?= $book->qty ?></div>
        </div>
      </div>

      <?php if ($book->description): ?>
      <div style="margin-bottom:1.5rem">
        <h3 style="font-family:var(--font-head);font-size:1rem;margin-bottom:.5rem">About this Book</h3>
        <p style="color:var(--text-muted);line-height:1.75"><?= clean($book->description) ?></p>
      </div>
      <?php endif; ?>

      <!-- CTA -->
      <?php if (!empty($_SESSION['student_id'])): ?>
      <div style="padding:1rem;background:rgba(201,168,76,.08);border:1px solid rgba(201,168,76,.25);border-radius:var(--radius)">
        <p style="font-size:.88rem;color:var(--text-muted);margin-bottom:.5rem">
          <i class="fa fa-circle-info" style="color:var(--gold)"></i>
          To borrow this book, visit the library desk and provide your Student ID: <strong><?= clean($_SESSION['student_id']) ?></strong>
        </p>
      </div>
      <?php else: ?>
      <div style="display:flex;gap:.75rem">
        <a href="index.php" class="btn btn-primary btn-lg"><i class="fa fa-right-to-bracket"></i> Login to Borrow</a>
        <a href="signup.php" class="btn btn-outline btn-lg">Register</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Related books -->
  <?php if (!empty($related)): ?>
  <div>
    <h2 style="font-family:var(--font-head);font-size:1.4rem;color:var(--navy);margin-bottom:1rem">More in <?= clean($book->cat_name) ?></h2>
    <div class="book-grid">
      <?php foreach ($related as $rb): ?>
      <div class="book-card" onclick="window.location='book-detail.php?id=<?= $rb->id ?>'">
        <?php if ($rb->cover && $rb->cover !== 'no-cover.jpg'): ?>
        <img class="book-cover" src="admin/bookimg/<?= clean($rb->cover) ?>" alt="<?= clean($rb->title) ?>"
             onerror="this.style.display='none'">
        <?php else: ?>
        <div class="book-cover-placeholder"><i class="fa fa-book"></i></div>
        <?php endif; ?>
        <div class="book-card-body">
          <div class="book-title"><?= clean($rb->title) ?></div>
          <div class="book-author"><?= clean($rb->author_name ?? '') ?></div>
          <div class="book-meta">
            <span style="font-size:.8rem;color:var(--gold)">₹<?= number_format($rb->price,0) ?></span>
            <span class="badge <?= $rb->available>0?'badge-success':'badge-danger' ?>">
              <?= $rb->available>0?'Available':'Unavail.' ?>
            </span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
