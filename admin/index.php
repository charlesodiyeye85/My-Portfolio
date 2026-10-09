<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$stats = [
    'works'   => (int) scalar('SELECT COUNT(*) FROM works', [], 0),
    'posts'   => (int) scalar('SELECT COUNT(*) FROM posts', [], 0),
    'unread'  => (int) scalar('SELECT COUNT(*) FROM messages WHERE is_read = 0', [], 0),
    'views'   => (int) scalar('SELECT COALESCE(SUM(views),0) FROM works', [], 0) + (int) scalar('SELECT COALESCE(SUM(views),0) FROM posts', [], 0),
];

$recentWorks = rows('SELECT * FROM works ORDER BY created_at DESC LIMIT 5');
$recentPosts = rows('SELECT * FROM posts ORDER BY created_at DESC LIMIT 5');
$recentMsgs  = rows('SELECT * FROM messages ORDER BY created_at DESC LIMIT 5');
$unreadMsgs  = (int) scalar('SELECT COUNT(*) FROM messages WHERE is_read = 0', [], 0);

admin_header('Dashboard', 'dashboard', 'Everything happening on your portfolio at a glance');
?>

<div class="stat-cards">
  <div class="stat-card"><span class="ic red"><i class="bi bi-images"></i></span><div><b><?= $stats['works'] ?></b><span>Work items</span></div></div>
  <div class="stat-card"><span class="ic viol"><i class="bi bi-journal-richtext"></i></span><div><b><?= $stats['posts'] ?></b><span>Posts written</span></div></div>
  <div class="stat-card"><span class="ic teal"><i class="bi bi-inbox"></i></span><div><b><?= $stats['unread'] ?></b><span>Unread messages</span></div></div>
  <div class="stat-card"><span class="ic gold"><i class="bi bi-eye"></i></span><div><b><?= $stats['views'] ?></b><span>Total views</span></div></div>
</div>

<div class="grid-2">
  <div class="a-card">
    <h3><i class="bi bi-clock-history"></i> Recent work uploads</h3>
    <div class="list-rows">
      <?php if (!$recentWorks): ?>
        <div class="empty-a"><i class="bi bi-images"></i><p>No work uploaded yet.</p>
          <a class="btn-a sm" href="<?= e(url('admin/work-form.php')) ?>"><i class="bi bi-plus-lg"></i> Add first work</a></div>
      <?php endif; ?>
      <?php foreach ($recentWorks as $w): ?>
      <div class="list-row">
        <img class="thumb-sm" src="<?= e(url($w['image'])) ?>" alt="">
        <div class="grow">
          <b><?= e($w['title']) ?></b>
          <small><?= e(category_label($w['category'])) ?> · <?= e(format_date($w['created_at'])) ?></small>
        </div>
        <span class="badge-a <?= $w['status'] === 'published' ? 'on' : 'off' ?>"><?= e($w['status']) ?></span>
        <a class="btn-a ghost-icon" href="<?= e(url('admin/work-form.php?id=' . (int) $w['id'])) ?>" title="Edit"><i class="bi bi-pencil"></i></a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="a-card">
    <h3><i class="bi bi-inbox"></i> Latest messages <?= $unreadMsgs ? '<span class="badge-a cat">' . $unreadMsgs . ' new</span>' : '' ?></h3>
    <div class="list-rows">
      <?php if (!$recentMsgs): ?>
        <div class="empty-a"><i class="bi bi-inbox"></i><p>No messages yet. Share your contact page to start receiving enquiries.</p></div>
      <?php endif; ?>
      <?php foreach ($recentMsgs as $m): ?>
      <div class="list-row">
        <span class="ic" style="width:42px;height:42px;border-radius:12px;display:grid;place-items:center;background:linear-gradient(135deg,#8a5cff,#5b8cff);font-weight:800">
          <?= e(strtoupper(mb_substr($m['name'], 0, 1))) ?>
        </span>
        <div class="grow">
          <b><?= e($m['name']) ?> <?= !$m['is_read'] ? '<span class="badge-a cat" style="margin-left:6px">new</span>' : '' ?></b>
          <small><?= e($m['subject']) ?> · <?= e(format_date($m['created_at'], 'M j, H:i')) ?></small>
        </div>
        <a class="btn-a ghost-icon" href="<?= e(url('admin/messages.php?open=' . (int) $m['id'])) ?>" title="Read"><i class="bi bi-envelope-open"></i></a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<div class="a-card" style="margin-top:22px">
  <h3><i class="bi bi-lightning-charge-fill"></i> Quick actions</h3>
  <div style="display:flex;gap:12px;flex-wrap:wrap">
    <a class="btn-a" href="<?= e(url('admin/work-form.php')) ?>"><i class="bi bi-plus-lg"></i> Upload new work</a>
    <a class="btn-a outline" href="<?= e(url('admin/post-form.php')) ?>"><i class="bi bi-pencil-square"></i> Write a post</a>
    <a class="btn-a outline" href="<?= e(url('admin/media.php')) ?>"><i class="bi bi-upload"></i> Media library</a>
    <a class="btn-a outline" href="<?= e(url('index.php')) ?>" target="_blank"><i class="bi bi-eye"></i> Preview site</a>
  </div>
</div>

<?php admin_footer(); ?>
