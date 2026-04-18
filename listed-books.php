<?php
// listed-books.php — Browse all books with search + filter
require_once 'includes/config.php';

$page_title = 'Browse Books';

// Search & filter params
$search   = trim($_GET['q'] ?? '');
$cat_id   = (int)($_GET['cat'] ?? 0);
$author_id= (int)($_GET['author'] ?? 0);
$sort     = $_GET['sort'] ?? 'newest';
$page     = max(1, (int)($_GET['page'] ?? 1));
$per_page = 12;
$offset   = ($page - 1) * $per_page;

// Build query
$where = ['1=1'];
$params = [];
if ($search) {
    $where[] = "(b.title LIKE ? OR b.isbn LIKE ? OR a.name LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
}
if ($cat_id)    { $where[] = "b.cat_id = ?";    $params[] = $cat_id; }
if ($author_id) { $where[] = "b.author_id = ?"; $params[] = $author_id; }

$orderMap = [
    'newest' => 'b.created_at DESC',
    'oldest' => 'b.created_at ASC',
    'title'  => 'b.title ASC',
    'price'  => 'b.price ASC',
];
$order = $orderMap[$sort] ?? 'b.created_at DESC';
$whereStr = implode(' AND ', $where);

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM tblbooks b LEFT JOIN tblauthors a ON a.id=b.author_id WHERE $whereStr");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();
$pages = (int)ceil($total / $per_page);

// Fetch books
$stmt = $pdo->prepare("
    SELECT b.*, a.name AS author_name, c.name AS cat_name
    FROM tblbooks b
    LEFT JOIN tblauthors a ON a.id = b.author_id
    LEFT JOIN tblcategory c ON c.id = b.cat_id
    WHERE $whereStr
    ORDER BY $order
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$books = $stmt->fetchAll();

// Load categories & authors for filters
$categories = $pdo->query("SELECT * FROM tblcategory WHERE status=1 ORDER BY name")->fetchAll();
$authors    = $pdo->query("SELECT * FROM tblauthors ORDER BY name")->fetchAll();

include 'includes/header.php';
?>

<div class="page-wrap">
  <div class="page-hero">
    <h1>Browse Our Collection</h1>
    <p>Explore <?= $total ?> books across all categories</p>
  </div>

  <!-- Search & filters -->
  <div class="card card-body mb-3">
    <form method="GET" action="">
      <div style="display:grid;grid-template-columns:2fr 1fr 1fr 1fr auto;gap:.75rem;align-items:end;flex-wrap:wrap">
        <div>
          <label class="form-label">Search</label>
          <input type="text" name="q" class="form-control" placeholder="Title, ISBN, author..."
                 value="<?= clean($search) ?>">
        </div>
        <div>
          <label class="form-label">Category</label>
          <select name="cat" class="form-control">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
            <option value="<?= $c->id ?>" <?= $cat_id==$c->id ? 'selected' : '' ?>><?= clean($c->name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label">Author</label>
          <select name="author" class="form-control">
            <option value="">All Authors</option>
            <?php foreach ($authors as $au): ?>
            <option value="<?= $au->id ?>" <?= $author_id==$au->id ? 'selected' : '' ?>><?= clean($au->name) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="form-label">Sort By</label>
          <select name="sort" class="form-control">
            <option value="newest" <?= $sort==='newest' ? 'selected' : '' ?>>Newest First</option>
            <option value="oldest" <?= $sort==='oldest' ? 'selected' : '' ?>>Oldest First</option>
            <option value="title"  <?= $sort==='title'  ? 'selected' : '' ?>>Title A–Z</option>
            <option value="price"  <?= $sort==='price'  ? 'selected' : '' ?>>Price (Low–High)</option>
          </select>
        </div>
        <div style="display:flex;gap:.5rem;padding-bottom:1px">
          <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i></button>
          <?php if ($search || $cat_id || $author_id): ?>
          <a href="listed-books.php" class="btn btn-outline"><i class="fa fa-xmark"></i></a>
          <?php endif; ?>
        </div>
      </div>
    </form>
  </div>

  <!-- Results info -->
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem">
    <p style="color:var(--text-muted);font-size:.88rem">
      Showing <?= count($books) ?> of <?= $total ?> books
      <?php if ($search): ?> for "<strong><?= clean($search) ?></strong>"<?php endif; ?>
    </p>
  </div>

  <!-- Book grid -->
  <?php if (empty($books)): ?>
  <div class="card card-body text-center" style="padding:4rem">
    <i class="fa fa-magnifying-glass" style="font-size:3rem;color:var(--border);margin-bottom:1rem;display:block"></i>
    <h3 style="font-family:var(--font-head);color:var(--navy)">No books found</h3>
    <p style="color:var(--text-muted)">Try adjusting your search or filters</p>
    <a href="listed-books.php" class="btn btn-gold mt-2">Clear Filters</a>
  </div>
  <?php else: ?>
  <div class="book-grid">
    <?php foreach ($books as $book): ?>
    <div class="book-card" onclick="window.location='book-detail.php?id=<?= $book->id ?>'">
      <?php if ($book->cover && $book->cover !== 'no-cover.jpg'): ?>
      <img class="book-cover" src="admin/bookimg/<?= clean($book->cover) ?>" alt="<?= clean($book->title) ?>"
           onerror="this.parentElement.querySelector('.book-cover-placeholder').style.display='flex';this.style.display='none'">
      <div class="book-cover-placeholder" style="display:none">
        <i class="fa fa-book"></i>
      </div>
      <?php else: ?>
      <div class="book-cover-placeholder">
        <i class="fa fa-book"></i>
        <span style="font-size:.65rem;margin-top:.5rem;text-align:center;padding:0 .5rem"><?= clean($book->title) ?></span>
      </div>
      <?php endif; ?>

      <div class="book-card-body">
        <div class="book-title"><?= clean($book->title) ?></div>
        <div class="book-author"><?= clean($book->author_name ?? 'Unknown Author') ?></div>
        <div class="book-meta">
          <span class="badge badge-gold"><?= clean($book->cat_name ?? '') ?></span>
          <?php if ($book->available > 0): ?>
          <span class="badge badge-success"><i class="fa fa-check"></i> Available (<?= $book->available ?>)</span>
          <?php else: ?>
          <span class="badge badge-danger">Not Available</span>
          <?php endif; ?>
        </div>
        <div style="margin-top:.6rem;font-size:.82rem;color:var(--text-muted)">
          ₹<?= number_format($book->price, 2) ?> &nbsp;|&nbsp; ISBN: <?= clean($book->isbn) ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Pagination -->
  <?php if ($pages > 1): ?>
  <div style="display:flex;justify-content:center;margin-top:2rem">
    <div class="pagination">
      <?php if ($page > 1): ?>
      <a href="?q=<?= urlencode($search) ?>&cat=<?= $cat_id ?>&author=<?= $author_id ?>&sort=<?= $sort ?>&page=<?= $page-1 ?>">
        <i class="fa fa-chevron-left"></i>
      </a>
      <?php endif; ?>
      <?php for ($p=1; $p<=$pages; $p++): ?>
      <a href="?q=<?= urlencode($search) ?>&cat=<?= $cat_id ?>&author=<?= $author_id ?>&sort=<?= $sort ?>&page=<?= $p ?>"
         class="<?= $p===$page ? 'active' : '' ?>"><?= $p ?></a>
      <?php endfor; ?>
      <?php if ($page < $pages): ?>
      <a href="?q=<?= urlencode($search) ?>&cat=<?= $cat_id ?>&author=<?= $author_id ?>&sort=<?= $sort ?>&page=<?= $page+1 ?>">
        <i class="fa fa-chevron-right"></i>
      </a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
