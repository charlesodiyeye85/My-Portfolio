<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function admin_header(string $title, string $active, string $subtitle = ''): void
{
    $user = require_admin();
    $unread = (int) scalar('SELECT COUNT(*) FROM messages WHERE is_read = 0', [], 0);
    $page = basename($_SERVER['SCRIPT_NAME']);

    $navItems = [
        'dashboard' => ['index.php', 'bi-speedometer2', 'Dashboard'],
        'works'     => ['works.php', 'bi-images', 'Work Showcase'],
        'posts'     => ['posts.php', 'bi-journal-richtext', 'Posts / Body Content'],
        'media'     => ['media.php', 'bi-folder2-open', 'Media Library'],
        'messages'  => ['messages.php', 'bi-inbox', 'Messages'],
        'settings'  => ['settings.php', 'bi-gear', 'Settings'],
    ];
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($title) ?> — CMS</title>
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(url('assets/vendor/bootstrap-icons/bootstrap-icons.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">
<div class="admin-shell">

  <aside class="admin-side" id="adminSide">
    <div class="side-brand">
      <span class="mark">CD</span>
      <div>Charles CMS<small>Content Manager</small></div>
    </div>
    <ul class="side-nav">
      <?php foreach ($navItems as $key => $item): ?>
      <li>
        <a href="<?= e(url('admin/' . $item[0])) ?>" class="<?= $active === $key ? 'active' : '' ?>">
          <i class="bi <?= e($item[1]) ?>"></i> <?= e($item[2]) ?>
          <?php if ($key === 'messages' && $unread): ?>
            <span class="badge"><?= $unread ?></span>
          <?php endif; ?>
        </a>
      </li>
      <?php endforeach; ?>
      <li style="margin-top:14px"><a href="<?= e(url('index.php')) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> View Site</a></li>
      <li><a href="<?= e(url('admin/login.php?logout=1')) ?>"><i class="bi bi-box-arrow-in-right"></i> Log Out</a></li>
    </ul>
    <div class="side-foot">
      Signed in as <b style="color:var(--a-text)"><?= e($user['username']) ?></b><br>
      <?= DB_DRIVER === 'sqlite' ? 'SQLite local mode' : 'MySQL database' ?>
    </div>
  </aside>

  <div class="admin-main">
    <div class="admin-top">
      <div style="display:flex;align-items:center;gap:12px">
        <button class="btn-a ghost-icon side-toggle" id="sideToggle" aria-label="Menu"><i class="bi bi-list"></i></button>
        <div>
          <h2><?= e($title) ?></h2>
          <?php if ($subtitle): ?><small style="color:var(--a-muted)"><?= e($subtitle) ?></small><?php endif; ?>
        </div>
      </div>
      <div class="top-actions">
        <a class="btn-a outline sm" href="<?= e(url('admin/work-form.php')) ?>"><i class="bi bi-plus-lg"></i> Work</a>
        <a class="btn-a outline sm" href="<?= e(url('admin/post-form.php')) ?>"><i class="bi bi-pencil"></i> Post</a>
        <a class="btn-a sm" href="<?= e(url('admin/login.php?logout=1')) ?>"><i class="bi bi-power"></i></a>
      </div>
    </div>

    <main class="admin-content">
    <?php foreach (get_flashes() as $f): ?>
      <div class="a-alert <?= e($f['type']) ?>"><i class="bi bi-info-circle-fill"></i> <?= e($f['message']) ?></div>
    <?php endforeach; ?>
    <?php if (password_verify(DEFAULT_ADMIN_PASS, (string) scalar('SELECT password_hash FROM users WHERE username = :u', [':u' => DEFAULT_ADMIN_USER], ''))): ?>
      <div class="a-alert warning"><i class="bi bi-shield-exclamation"></i>
        You are still using the default login (<b><?= e(DEFAULT_ADMIN_USER) ?> / <?= e(DEFAULT_ADMIN_PASS) ?></b>).
        <a href="<?= e(url('admin/settings.php')) ?>#security" style="color:inherit;text-decoration:underline">Change it now</a>.
      </div>
    <?php endif; ?>
<?php
}

function admin_footer(): void
{
    ?>
    </main>
  </div>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" style="font-weight:800">Are you sure?</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p id="confirmText" style="margin:0;color:var(--a-muted)">This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-a outline" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn-a danger" id="confirmYes"><i class="bi bi-trash3"></i> Delete</button>
      </div>
    </div>
  </div>
</div>

<input type="hidden" id="csrfToken" value="<?= e(csrf_token()) ?>">
<input type="hidden" id="baseHref" value="<?= e(app_base()) ?>">
<script src="<?= e(url('assets/vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(url('assets/js/admin.js')) ?>"></script>
</body>
</html>
    <?php
}
