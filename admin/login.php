<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (current_user()) {
    redirect('admin/index.php');
}

$error = '';
$throttle = (int) ($_SESSION['login_attempts'] ?? 0);
$lockedUntil = (int) ($_SESSION['login_locked_until'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Session expired. Please try again.';
    } elseif (time() < $lockedUntil) {
        $error = 'Too many attempts. Wait ' . max(1, $lockedUntil - time()) . ' seconds and try again.';
    } else {
        $username = trim((string) ($_POST['username'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $user = row('SELECT * FROM users WHERE username = :u OR email = :u2', [':u' => $username, ':u2' => $username]);

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int) $user['id'];
            $_SESSION['login_attempts'] = 0;
            $_SESSION['login_locked_until'] = 0;
            redirect('admin/index.php');
        }

        $_SESSION['login_attempts'] = ++$throttle;
        if ($throttle >= 5) {
            $_SESSION['login_locked_until'] = time() + 30;
            $_SESSION['login_attempts'] = 0;
        }
        $error = 'Invalid username or password.';
    }
}

$showDefaultHint = password_verify(DEFAULT_ADMIN_PASS, (string) scalar(
    'SELECT password_hash FROM users WHERE username = :u',
    [':u' => DEFAULT_ADMIN_USER],
    ''
));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Log in — Charles CMS</title>
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(url('assets/vendor/bootstrap-icons/bootstrap-icons.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/admin.css')) ?>">
</head>
<body class="admin-body">

<div class="login-wrap">
  <div class="login-card">
    <div class="login-logo">CD</div>
    <h1>Content Manager</h1>
    <p class="sub">Log in to manage works, posts, media and messages for <b>Charles Odeye Damilola</b>.</p>

    <?php if ($error): ?>
      <div class="a-alert error"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($error) ?></div>
    <?php endif; ?>
    <?php if ($showDefaultHint): ?>
      <div class="a-alert info"><i class="bi bi-key-fill"></i> Default login: <b><?= e(DEFAULT_ADMIN_USER) ?> / <?= e(DEFAULT_ADMIN_PASS) ?></b> — change it in Settings after you log in.</div>
    <?php endif; ?>

    <form class="form-a" method="post" action="<?= e(url('admin/login.php')) ?>">
      <?= csrf_field() ?>
      <div class="field" style="margin-bottom:16px">
        <label>Username or Email</label>
        <input type="text" name="username" required autofocus autocomplete="username" value="<?= e($_POST['username'] ?? '') ?>">
      </div>
      <div class="field" style="margin-bottom:22px">
        <label>Password</label>
        <input type="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-a" style="width:100%;justify-content:center;padding:13px">
        <i class="bi bi-box-arrow-in-right"></i> Log in
      </button>
    </form>

    <p style="margin:22px 0 0;text-align:center;font-size:.84rem;color:var(--a-muted)">
      <a href="<?= e(url('index.php')) ?>"><i class="bi bi-arrow-left"></i> Back to portfolio</a>
    </p>
  </div>
</div>

</body>
</html>
