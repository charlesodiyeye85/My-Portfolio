<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$search    = trim((string) ($_GET['q'] ?? ''));
$statusFil = (string) ($_GET['status'] ?? 'all');
$statusFil = in_array($statusFil, ['all', 'published', 'draft'], true) ? $statusFil : 'all';

$sql    = 'SELECT * FROM posts WHERE 1=1';
$params = [];

if ($statusFil !== 'all') {
    $sql .= ' AND status = :s';
    $params[':s'] = $statusFil;
}
if ($search !== '') {
    $sql .= ' AND (title LIKE :q1 OR excerpt LIKE :q2 OR body LIKE :q3 OR category LIKE :q4)';
    $like = '%' . $search . '%';
    $params += [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like];
}
$sql .= ' ORDER BY published_at DESC';

$posts = rows($sql, $params);

$counts = [
    'all'       => (int) scalar('SELECT COUNT(*) FROM posts', [], 0),
    'published' => (int) scalar("SELECT COUNT(*) FROM posts WHERE status = 'published'", [], 0),
    'draft'     => (int) scalar("SELECT COUNT(*) FROM posts WHERE status = 'draft'", [], 0),
];

$cats = rows('SELECT DISTINCT category FROM posts ORDER BY category');
$postCategories = array_map(static fn(array $r): string => (string) $r['category'], $cats);

admin_header('Posts / Body Content', 'posts', 'Write, edit and publish long-form articles for the journal section');
?>

<div class="toolbar-row">
  <div>
    <h1>Posts &amp; Body Content</h1>
    <p><?= (int) count($posts) ?> shown · <?= $counts['published'] ?> live · <?= $counts['draft'] ?> drafts</p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <form method="get" action="<?= e(url('admin/posts.php')) ?>" style="display:flex;gap:8px">
      <?php if ($statusFil !== 'all'): ?>
        <input type="hidden" name="status" value="<?= e($statusFil) ?>">
      <?php endif; ?>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search title or content…"
             style="background:rgba(255,255,255,.045);border:1px solid var(--a-line);border-radius:999px;color:var(--a-text);padding:9px 16px;font-size:.88rem;outline:none;min-width:210px">
      <button class="btn-a ghost-icon" type="submit" title="Search"><i class="bi bi-search"></i></button>
      <?php if ($search !== ''): ?>
        <a class="btn-a ghost-icon" href="<?= e(url('admin/posts.php')) ?>" title="Clear"><i class="bi bi-x-lg"></i></a>
      <?php endif; ?>
    </form>
    <a class="btn-a" href="<?= e(url('admin/post-form.php')) ?>"><i class="bi bi-pencil-square"></i> Write a post</a>
  </div>
</div>

<div class="filter-pills" style="margin-bottom:22px">
  <a href="<?= e(url('admin/posts.php')) ?>" class="<?= $statusFil === 'all' ? 'on' : '' ?>">All <span style="opacity:.7">(<?= $counts['all'] ?>)</span></a>
  <a href="<?= e(url('admin/posts.php?status=published')) ?>" class="<?= $statusFil === 'published' ? 'on' : '' ?>"><i class="bi bi-globe2"></i> Published <span style="opacity:.7">(<?= $counts['published'] ?>)</span></a>
  <a href="<?= e(url('admin/posts.php?status=draft')) ?>" class="<?= $statusFil === 'draft' ? 'on' : '' ?>"><i class="bi bi-pencil"></i> Drafts <span style="opacity:.7">(<?= $counts['draft'] ?>)</span></a>
</div>

<div class="a-card">
  <?php if (!$posts): ?>
    <div class="empty-a">
      <i class="bi bi-journal-richtext"></i>
      <p><?= $search !== '' ? 'No post matches “' . e($search) . '”.' : 'No posts yet. Your journal is empty.' ?></p>
      <a class="btn-a" href="<?= e(url('admin/post-form.php')) ?>"><i class="bi bi-pencil-square"></i> Write your first post</a>
    </div>
  <?php else: ?>
  <div class="table-responsive-a">
    <table class="a-table">
      <thead>
        <tr>
          <th>Post</th>
          <th>Category</th>
          <th>Published</th>
          <th>Views</th>
          <th>Live</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($posts as $p): $pid = (int) $p['id']; ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:13px;min-width:260px">
              <img class="thumb-sm" src="<?= e(url($p['cover_image'])) ?>" alt="" loading="lazy">
              <div style="min-width:0">
                <b style="display:block"><?= e($p['title']) ?></b>
                <small style="color:var(--a-muted)"><?= e(excerpt_text($p['excerpt'] ?: $p['body'], 72)) ?></small>
              </div>
            </div>
          </td>
          <td><span class="badge-a cat"><?= e($p['category'] ?: 'General') ?></span></td>
          <td><?= e(format_date($p['published_at'])) ?></td>
          <td><i class="bi bi-eye" style="color:var(--a-muted)"></i> <?= (int) $p['views'] ?></td>
          <td>
            <label class="check-a" title="Show in the journal">
              <input type="checkbox" data-toggle-url="<?= e(url('actions/toggle.php?type=post&field=status&id=' . $pid)) ?>"
                     <?= $p['status'] === 'published' ? 'checked' : '' ?>>
            </label>
          </td>
          <td>
            <div style="display:flex;gap:8px;justify-content:flex-end">
              <a class="btn-a ghost-icon" href="<?= e(url('admin/post-form.php?id=' . $pid)) ?>" title="Edit"><i class="bi bi-pencil"></i></a>
              <?php if ($p['status'] === 'published'): ?>
                <a class="btn-a ghost-icon" href="<?= e(url('post.php?slug=' . urlencode($p['slug']))) ?>" target="_blank" title="View on site"><i class="bi bi-eye"></i></a>
              <?php endif; ?>
              <a class="btn-a ghost-icon" href="<?= e(url('admin/post-form.php?id=' . $pid)) ?>#preview" title="Preview"><i class="bi bi-play-circle"></i></a>
              <a class="btn-a ghost-icon del" href="#"
                 data-confirm-url="<?= e(url('actions/delete.php?type=post&id=' . $pid)) ?>"
                 data-confirm-text="Delete “<?= e($p['title']) ?>” permanently?"
                 title="Delete"><i class="bi bi-trash3"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php admin_footer(); ?>
