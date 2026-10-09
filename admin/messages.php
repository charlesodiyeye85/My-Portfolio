<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$openId = (int) ($_GET['open'] ?? 0);
$open   = $openId > 0 ? row('SELECT * FROM messages WHERE id = :id', [':id' => $openId]) : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $open && verify_csrf()) {
    $act = (string) ($_POST['act'] ?? '');
    if ($act === 'read') {
        update_by_id('messages', (int) $open['id'], ['is_read' => 1]);
        flash('Marked as read.');
    } elseif ($act === 'unread') {
        update_by_id('messages', (int) $open['id'], ['is_read' => 0]);
        flash('Marked as unread.');
    } elseif ($act === 'delete') {
        q('DELETE FROM messages WHERE id = :id', [':id' => (int) $open['id']]);
        flash('Message deleted.');
    }
    redirect('admin/messages.php');
}

$filter = (string) ($_GET['status'] ?? 'all');
$filter = in_array($filter, ['all', 'unread'], true) ? $filter : 'all';
$sql    = 'SELECT * FROM messages' . ($filter === 'unread' ? ' WHERE is_read = 0' : '') . ' ORDER BY created_at DESC';
$msgs   = rows($sql);

$counts = [
    'all'    => (int) scalar('SELECT COUNT(*) FROM messages', [], 0),
    'unread' => (int) scalar('SELECT COUNT(*) FROM messages WHERE is_read = 0', [], 0),
];

admin_header('Messages', 'messages', 'Enquiries sent from your portfolio contact form');
?>

<?php if ($open): ?>
<div class="toolbar-row">
  <div>
    <h1><?= e($open['subject'] ?: 'Enquiry') ?></h1>
    <p>From <b style="color:var(--a-text)"><?= e($open['name']) ?></b> · <?= e($open['email']) ?> · <?= e(format_date($open['created_at'], 'M j, Y · H:i')) ?></p>
  </div>
  <a class="btn-a outline" href="<?= e(url('admin/messages.php')) ?>"><i class="bi bi-arrow-left"></i> All messages</a>
</div>

<?php if (!(int) $open['is_read']): ?>
  <div class="a-alert info"><i class="bi bi-envelope-fill"></i> This message is unread.</div>
<?php endif; ?>

<div class="a-card">
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
    <form method="post" action="<?= e(url('admin/messages.php?open=' . (int) $open['id'])) ?>">
      <?= csrf_field() ?>
      <button class="btn-a outline sm" name="act" value="<?= (int) $open['is_read'] === 1 ? 'unread' : 'read' ?>">
        <i class="bi bi-envelope<?= (int) $open['is_read'] === 1 ? '' : '-open' ?>"></i>
        Mark as <?= (int) $open['is_read'] === 1 ? 'unread' : 'read' ?>
      </button>
    </form>
    <a class="btn-a outline sm" href="mailto:<?= e($open['email']) ?>?subject=Re:%20<?= e(rawurlencode((string) $open['subject'])) ?>">
      <i class="bi bi-reply"></i> Reply by email
    </a>
    <button class="btn-a outline sm" type="button" data-copy="<?= e($open['email']) ?>"><i class="bi bi-copy"></i> Copy email</button>
    <a class="btn-a danger sm" href="#"
       data-confirm-url="<?= e(url('actions/delete.php?type=message&id=' . (int) $open['id'])) ?>"
       data-confirm-text="Delete this message from <?= e($open['name']) ?>?">
      <i class="bi bi-trash3"></i> Delete
    </a>
  </div>

  <div style="background:var(--a-card-2);border:1px solid var(--a-line);border-radius:14px;padding:22px;line-height:1.8;white-space:pre-wrap"><?= e($open['body']) ?></div>

  <dl class="kv" style="margin-top:22px">
    <dt>Name</dt><dd><?= e($open['name']) ?></dd>
    <dt>Email</dt><dd><?= e($open['email']) ?></dd>
    <dt>Subject</dt><dd><?= e($open['subject'] ?: 'Portfolio enquiry') ?></dd>
    <dt>Received</dt><dd><?= e(format_date($open['created_at'], 'l, F j Y · H:i')) ?></dd>
  </dl>
</div>

<?php else: ?>

<div class="toolbar-row">
  <div>
    <h1>Messages</h1>
    <p><?= $counts['all'] ?> total · <?= $counts['unread'] ?> unread</p>
  </div>
</div>

<div class="filter-pills" style="margin-bottom:22px">
  <a href="<?= e(url('admin/messages.php')) ?>" class="<?= $filter === 'all' ? 'on' : '' ?>">All <span style="opacity:.7">(<?= $counts['all'] ?>)</span></a>
  <a href="<?= e(url('admin/messages.php?status=unread')) ?>" class="<?= $filter === 'unread' ? 'on' : '' ?>"><i class="bi bi-envelope"></i> Unread <span style="opacity:.7">(<?= $counts['unread'] ?>)</span></a>
</div>

<div class="a-card">
  <?php if (!$msgs): ?>
    <div class="empty-a">
      <i class="bi bi-inbox"></i>
      <p>No messages yet. Share your contact page to start receiving enquiries.</p>
      <a class="btn-a outline" href="<?= e(url('index.php')) ?>#contact" target="_blank"><i class="bi bi-eye"></i> Open contact page</a>
    </div>
  <?php else: ?>
  <div class="table-responsive-a">
    <table class="a-table">
      <thead>
        <tr>
          <th>From</th>
          <th>Subject</th>
          <th>Received</th>
          <th>Status</th>
          <th style="text-align:right">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($msgs as $m): $mid = (int) $m['id']; $unread = !(int) $m['is_read']; ?>
        <tr class="msg-row <?= $unread ? 'unread' : '' ?>">
          <td>
            <div style="display:flex;align-items:center;gap:12px;min-width:180px">
              <span style="width:38px;height:38px;border-radius:11px;display:grid;place-items:center;background:linear-gradient(135deg,#8a5cff,#5b8cff);font-weight:800;flex:none">
                <?= e(strtoupper(mb_substr((string) $m['name'], 0, 1))) ?>
              </span>
              <div style="min-width:0">
                <b style="display:block"><?= e($m['name']) ?></b>
                <small style="color:var(--a-muted)"><?= e($m['email']) ?></small>
              </div>
            </div>
          </td>
          <td>
            <a href="<?= e(url('admin/messages.php?open=' . $mid)) ?>" style="color:var(--a-text);font-weight:700"><?= e($m['subject'] ?: 'Portfolio enquiry') ?></a>
            <div class="msg-body-line"><?= e($m['body']) ?></div>
          </td>
          <td><?= e(format_date($m['created_at'], 'M j, Y · H:i')) ?></td>
          <td>
            <?php if ($unread): ?>
              <span class="badge-a cat">new</span>
            <?php else: ?>
              <span class="badge-a on">read</span>
            <?php endif; ?>
          </td>
          <td>
            <div style="display:flex;gap:8px;justify-content:flex-end">
              <a class="btn-a ghost-icon" href="<?= e(url('admin/messages.php?open=' . $mid)) ?>" title="Read"><i class="bi bi-envelope-open"></i></a>
              <a class="btn-a ghost-icon" href="mailto:<?= e($m['email']) ?>?subject=Re:%20<?= e(rawurlencode((string) $m['subject'])) ?>" title="Reply"><i class="bi bi-reply"></i></a>
              <a class="btn-a ghost-icon del" href="#"
                 data-confirm-url="<?= e(url('actions/delete.php?type=message&id=' . $mid)) ?>"
                 data-confirm-text="Delete this message from <?= e($m['name']) ?>?"
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

<?php endif; ?>

<?php admin_footer(); ?>
