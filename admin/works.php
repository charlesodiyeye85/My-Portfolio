<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$cat     = (string) ($_GET['cat'] ?? 'all');
$search  = trim((string) ($_GET['q'] ?? ''));
$filter  = in_array($cat, array_merge(['all'], array_keys(work_categories())), true) ? $cat : 'all';

$sql    = 'SELECT * FROM works WHERE 1=1';
$params = [];

if ($filter !== 'all') {
    $sql .= ' AND category = :c';
    $params[':c'] = $filter;
}
if ($search !== '') {
    $sql .= ' AND (title LIKE :q1 OR client LIKE :q2 OR tools LIKE :q3 OR description LIKE :q4)';
    $like = '%' . $search . '%';
    $params += [':q1' => $like, ':q2' => $like, ':q3' => $like, ':q4' => $like];
}
$sql .= ' ORDER BY is_featured DESC, created_at DESC';

$works = rows($sql, $params);

$counts = [
    'all' => (int) scalar('SELECT COUNT(*) FROM works', [], 0),
];
foreach (array_keys(work_categories()) as $key) {
    $counts[$key] = (int) scalar('SELECT COUNT(*) FROM works WHERE category = :c', [':c' => $key], 0);
}

admin_header('Work Showcase', 'works', 'Upload, organise and publish the boxes shown in your portfolio');
?>

<div class="toolbar-row">
  <div>
    <h1>Work Showcase</h1>
    <p><?= (int) count($works) ?> item<?= count($works) === 1 ? '' : 's' ?> shown · <?= $counts['all'] ?> total</p>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <form method="get" action="<?= e(url('admin/works.php')) ?>" style="display:flex;gap:8px">
      <?php if ($filter !== 'all'): ?>
        <input type="hidden" name="cat" value="<?= e($filter) ?>">
      <?php endif; ?>
      <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search title, client, tools…"
             style="background:rgba(255,255,255,.045);border:1px solid var(--a-line);border-radius:999px;color:var(--a-text);padding:9px 16px;font-size:.88rem;outline:none;min-width:210px">
      <button class="btn-a ghost-icon" type="submit" title="Search"><i class="bi bi-search"></i></button>
      <?php if ($search !== ''): ?>
        <a class="btn-a ghost-icon" href="<?= e(url('admin/works.php' . ($filter !== 'all' ? '?cat=' . e($filter) : ''))) ?>" title="Clear"><i class="bi bi-x-lg"></i></a>
      <?php endif; ?>
    </form>
    <a class="btn-a" href="<?= e(url('admin/work-form.php')) ?>"><i class="bi bi-plus-lg"></i> New work</a>
  </div>
</div>

<div class="filter-pills" style="margin-bottom:22px">
  <a href="<?= e(url('admin/works.php')) ?>" class="<?= $filter === 'all' ? 'on' : '' ?>">All <span style="opacity:.7">(<?= $counts['all'] ?>)</span></a>
  <?php foreach (work_categories() as $key => $label): ?>
    <a href="<?= e(url('admin/works.php?cat=' . urlencode($key))) ?>" class="<?= $filter === $key ? 'on' : '' ?>">
      <i class="bi <?= e(category_icon($key)) ?>"></i> <?= e($label) ?> <span style="opacity:.7">(<?= $counts[$key] ?>)</span>
    </a>
  <?php endforeach; ?>
</div>

<div class="a-card">
  <?php if (!$works): ?>
    <div class="empty-a">
      <i class="bi bi-images"></i>
      <p><?= $search !== '' ? 'No work matches “' . e($search) . '”.' : 'Nothing here yet.' ?></p>
      <a class="btn-a" href="<?= e(url('admin/work-form.php')) ?>"><i class="bi bi-plus-lg"></i> Upload your first piece of work</a>
    </div>
  <?php else: ?>
  <div class="table-responsive-a">
    <table class="a-table">
      <thead>
        <tr>
          <th>Work</th>
          <th>Category</th>
          <th>Year</th>
          <th>Views</th>
          <th>Live</th>
          <th>Featured</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($works as $w): $wid = (int) $w['id']; ?>
        <tr>
          <td>
            <div style="display:flex;align-items:center;gap:13px;min-width:230px">
              <img class="thumb-sm" src="<?= e(url($w['image'])) ?>" alt="" loading="lazy">
              <div style="min-width:0">
                <b style="display:block"><?= e($w['title']) ?></b>
                <small style="color:var(--a-muted)"><?= e($w['client'] ?: 'Personal project') ?><?= $w['tools'] ? ' · ' . e(excerpt_text($w['tools'], 40)) : '' ?></small>
              </div>
            </div>
          </td>
          <td><span class="badge-a cat"><i class="bi <?= e(category_icon($w['category'])) ?>"></i> <?= e(category_label($w['category'])) ?></span></td>
          <td><?= e($w['year'] ?: '—') ?></td>
          <td><i class="bi bi-eye" style="color:var(--a-muted)"></i> <?= (int) $w['views'] ?></td>
          <td>
            <label class="check-a" title="Show on the public portfolio">
              <input type="checkbox" data-toggle-url="<?= e(url('actions/toggle.php?type=work&field=status&id=' . $wid)) ?>"
                     <?= $w['status'] === 'published' ? 'checked' : '' ?>>
            </label>
          </td>
          <td>
            <label class="check-a" title="Pin to the front of the grid">
              <input type="checkbox" data-toggle-url="<?= e(url('actions/toggle.php?type=work&field=is_featured&id=' . $wid)) ?>"
                     <?= (int) $w['is_featured'] === 1 ? 'checked' : '' ?>>
            </label>
          </td>
          <td>
            <div style="display:flex;gap:8px;justify-content:flex-end">
              <a class="btn-a ghost-icon" href="<?= e(url('admin/work-form.php?id=' . $wid)) ?>" title="Edit"><i class="bi bi-pencil"></i></a>
              <a class="btn-a ghost-icon" href="<?= e(url('index.php')) ?>#work" target="_blank" title="View on site"><i class="bi bi-eye"></i></a>
              <?php if ($w['external_url']): ?>
                <a class="btn-a ghost-icon" href="<?= e($w['external_url']) ?>" target="_blank" rel="noopener" title="Open project link"><i class="bi bi-box-arrow-up-right"></i></a>
              <?php endif; ?>
              <a class="btn-a ghost-icon del" href="#"
                 data-confirm-url="<?= e(url('actions/delete.php?type=work&id=' . $wid)) ?>"
                 data-confirm-text="Delete “<?= e($w['title']) ?>”? The uploaded image is removed too."
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
