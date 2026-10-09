<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$user = require_admin();
$errors = [];
$tab = (string) ($_POST['tab'] ?? $_GET['tab'] ?? 'profile');
$tab = in_array($tab, ['profile', 'social', 'images', 'security'], true) ? $tab : 'profile';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
    } elseif ($tab === 'security') {
        $newUser = trim((string) ($_POST['username'] ?? ''));
        $pass1   = (string) ($_POST['password'] ?? '');
        $pass2   = (string) ($_POST['password2'] ?? '');
        $current = (string) ($_POST['current_password'] ?? '');

        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Your current password is incorrect.';
        }
        if ($newUser !== '' && $newUser !== $user['username']) {
            if ((int) scalar('SELECT COUNT(*) FROM users WHERE username = :u AND id <> :id', [':u' => $newUser, ':id' => (int) $user['id']], 0) > 0) {
                $errors[] = 'That username is already taken.';
            }
        }
        if ($pass1 !== '' || $pass2 !== '') {
            if (strlen($pass1) < 8) {
                $errors[] = 'New password must be at least 8 characters.';
            } elseif ($pass1 !== $pass2) {
                $errors[] = 'The new passwords do not match.';
            }
        }
        if (!$errors) {
            $data = [];
            if ($newUser !== '' && $newUser !== $user['username']) {
                $data['username'] = $newUser;
            }
            if ($pass1 !== '') {
                $data['password_hash'] = password_hash($pass1, PASSWORD_DEFAULT);
            }
            if ($data) {
                update_by_id('users', (int) $user['id'], $data);
                flash('Security settings updated.');
            } else {
                flash('Nothing to change.', 'info');
            }
            redirect('admin/settings.php?tab=security');
        }
    } else {
        $fields = match ($tab) {
            'profile' => ['site_title', 'tagline', 'hero_subtitle', 'bio', 'phone', 'email', 'location', 'whatsapp', 'availability', 'footer_note', 'cv_url'],
            'social'  => ['instagram', 'tiktok', 'twitter', 'youtube', 'linkedin', 'behance', 'github'],
            default   => [],
        };
        foreach ($fields as $f) {
            if (array_key_exists($f, $_POST) && is_string($_POST[$f])) {
                save_setting($f, trim($_POST[$f]));
            }
        }

        if ($tab === 'images') {
            try {
                $photo = save_upload($_FILES['profile_photo'] ?? [], 'profile', UPLOAD_MAX_IMAGE_MB, ALLOWED_IMAGE_EXT);
                if ($photo) {
                    $old = setting('profile_photo', '');
                    if (str_starts_with($old, 'uploads/')) {
                        delete_media_file($old);
                    }
                    save_setting('profile_photo', $photo);
                }
                $hero = save_upload($_FILES['hero_image'] ?? [], 'profile', UPLOAD_MAX_IMAGE_MB, ALLOWED_IMAGE_EXT);
                if ($hero) {
                    $old = setting('hero_image', '');
                    if (str_starts_with($old, 'uploads/')) {
                        delete_media_file($old);
                    }
                    save_setting('hero_image', $hero);
                }
            } catch (Throwable $e) {
                $errors[] = $e->getMessage();
            }
            $reset = (string) ($_POST['reset_images'] ?? '');
            if ($reset === '1') {
                save_setting('profile_photo', 'assets/img/avatar.svg');
                save_setting('hero_image', 'assets/img/hero.svg');
            }
        }

        if (!$errors) {
            flash('Settings saved.');
            redirect('admin/settings.php?tab=' . $tab);
        }
    }
}

$tabs = [
    'profile' => ['bi-person-fill', 'Profile & Hero'],
    'social'  => ['bi-share-fill', 'Social Links'],
    'images'  => ['bi-image-fill', 'Images'],
    'security'=> ['bi-shield-lock-fill', 'Security'],
];

admin_header('Settings', 'settings', 'Everything shown on the public portfolio, plus your login');
?>

<div class="toolbar-row">
  <div>
    <h1>Settings</h1>
    <p>Edit the text, links and pictures your visitors see.</p>
  </div>
  <a class="btn-a outline" href="<?= e(url('index.php')) ?>" target="_blank"><i class="bi bi-eye"></i> Preview site</a>
</div>

<?php foreach ($errors as $err): ?>
  <div class="a-alert error"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div>
<?php endforeach; ?>

<div class="filter-pills" style="margin-bottom:22px">
  <?php foreach ($tabs as $key => $t): ?>
    <a href="<?= e(url('admin/settings.php?tab=' . $key)) ?>" class="<?= $tab === $key ? 'on' : '' ?>" id="<?= $key === 'security' ? 'security' : '' ?>">
      <i class="bi <?= e($t[0]) ?>"></i> <?= e($t[1]) ?>
    </a>
  <?php endforeach; ?>
</div>

<form class="form-a" method="post" enctype="multipart/form-data" action="<?= e(url('admin/settings.php?tab=' . $tab)) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="tab" value="<?= e($tab) ?>">

  <?php if ($tab === 'profile'): ?>
  <div class="a-card">
    <h3><i class="bi bi-person-badge-fill"></i> Identity</h3>
    <div class="form-row-a">
      <div class="field">
        <label>Site title / Name</label>
        <input type="text" name="site_title" maxlength="120" value="<?= e(setting('site_title', 'Charles Odeye Damilola')) ?>">
      </div>
      <div class="field">
        <label>Tagline</label>
        <input type="text" name="tagline" maxlength="160" value="<?= e(setting('tagline')) ?>">
      </div>
    </div>
    <div class="field" style="margin-top:18px">
      <label>Hero subtitle</label>
      <textarea name="hero_subtitle" maxlength="400" style="min-height:100px"><?= e(setting('hero_subtitle')) ?></textarea>
      <p class="hint">The paragraph under your name at the top of the page.</p>
    </div>
    <div class="field" style="margin-top:18px">
      <label>About / Bio</label>
      <textarea name="bio" class="tall"><?= e(setting('bio')) ?></textarea>
    </div>
    <div class="field" style="margin-top:18px">
      <label>Availability line</label>
      <input type="text" name="availability" maxlength="160" value="<?= e(setting('availability')) ?>">
    </div>
  </div>

  <div class="a-card">
    <h3><i class="bi bi-telephone-fill"></i> Contact details</h3>
    <div class="form-row-a three">
      <div class="field">
        <label>Phone</label>
        <input type="text" name="phone" maxlength="40" value="<?= e(setting('phone')) ?>">
      </div>
      <div class="field">
        <label>Email</label>
        <input type="email" name="email" maxlength="190" value="<?= e(setting('email')) ?>">
      </div>
      <div class="field">
        <label>Location</label>
        <input type="text" name="location" maxlength="120" value="<?= e(setting('location')) ?>">
      </div>
    </div>
    <div class="form-row-a" style="margin-top:18px">
      <div class="field">
        <label>WhatsApp number (digits only, with country code)</label>
        <input type="text" name="whatsapp" maxlength="25" value="<?= e(setting('whatsapp')) ?>" placeholder="2348012345678">
      </div>
      <div class="field">
        <label>CV / Résumé URL</label>
        <input type="url" name="cv_url" value="<?= e(setting('cv_url')) ?>" placeholder="https://…">
      </div>
    </div>
    <div class="field" style="margin-top:18px">
      <label>Footer note</label>
      <input type="text" name="footer_note" maxlength="200" value="<?= e(setting('footer_note')) ?>">
    </div>
  </div>

  <?php elseif ($tab === 'social'): ?>
  <div class="a-card">
    <h3><i class="bi bi-share-fill"></i> Social profiles</h3>
    <p class="hint" style="margin-top:-8px;margin-bottom:18px">Leave a field blank (or set it to #) to hide that icon from the site.</p>
    <div class="form-row-a three">
      <?php
      $socials = [
          'instagram' => 'Instagram URL', 'tiktok' => 'TikTok URL', 'twitter' => 'X (Twitter) URL',
          'youtube' => 'YouTube URL', 'linkedin' => 'LinkedIn URL', 'behance' => 'Behance URL',
          'github' => 'GitHub URL',
      ];
      foreach ($socials as $key => $label): ?>
        <div class="field">
          <label><?= e($label) ?></label>
          <input type="url" name="<?= e($key) ?>" value="<?= e(setting($key)) ?>" placeholder="https://…">
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php elseif ($tab === 'images'): ?>
  <div class="a-card">
    <h3><i class="bi bi-image-fill"></i> Profile &amp; hero images</h3>
    <div class="form-row-a">
      <div>
        <label>Profile photo</label>
        <div class="drop-zone" data-drop>
          <i class="bi bi-person-bounding-box"></i>
          <b>Drop a new profile photo</b>
          <small>Square images work best · max <?= UPLOAD_MAX_IMAGE_MB ?> MB</small>
          <input type="file" name="profile_photo" accept="<?= e(implode(',', array_map(static fn($x): string => '.' . $x, ALLOWED_IMAGE_EXT))) ?>" hidden>
        </div>
        <div class="preview-box"><img alt="Preview"></div>
        <img src="<?= e(url(setting('profile_photo', 'assets/img/avatar.svg'))) ?>" alt="Current profile photo"
             style="margin-top:14px;width:120px;height:120px;object-fit:cover;border-radius:16px;border:1px solid var(--a-line)">
      </div>
      <div>
        <label>Hero / social share image</label>
        <div class="drop-zone" data-drop>
          <i class="bi bi-image"></i>
          <b>Drop a new hero image</b>
          <small>Wide (1200×630) works best for sharing · max <?= UPLOAD_MAX_IMAGE_MB ?> MB</small>
          <input type="file" name="hero_image" accept="<?= e(implode(',', array_map(static fn($x): string => '.' . $x, ALLOWED_IMAGE_EXT))) ?>" hidden>
        </div>
        <div class="preview-box"><img alt="Preview"></div>
        <img src="<?= e(url(setting('hero_image', 'assets/img/hero.svg'))) ?>" alt="Current hero image"
             style="margin-top:14px;width:100%;max-height:140px;object-fit:cover;border-radius:13px;border:1px solid var(--a-line)">
      </div>
    </div>
    <label class="check-a" style="margin-top:20px">
      <input type="checkbox" name="reset_images" value="1"> Reset both images to the built-in artwork
    </label>
  </div>

  <?php else: ?>
  <div class="a-card" id="security">
    <h3><i class="bi bi-shield-lock-fill"></i> Login &amp; security</h3>
    <div class="a-alert warning"><i class="bi bi-key-fill"></i>
      Enter your current password for every change on this tab.
    </div>

    <div class="form-row-a three">
      <div class="field">
        <label>Username</label>
        <input type="text" name="username" maxlength="60" value="<?= e($user['username']) ?>">
      </div>
      <div class="field">
        <label>New password</label>
        <input type="password" name="password" autocomplete="new-password" placeholder="At least 8 characters">
      </div>
      <div class="field">
        <label>Confirm new password</label>
        <input type="password" name="password2" autocomplete="new-password" placeholder="Repeat it">
      </div>
    </div>

    <div class="field" style="margin-top:18px;max-width:420px">
      <label>Current password <span class="req">*</span></label>
      <input type="password" name="current_password" required autocomplete="current-password">
    </div>

    <p class="hint">Leave the password fields blank to only change the username.</p>
  </div>
  <?php endif; ?>

  <div class="form-actions" style="max-width:none">
    <button type="submit" class="btn-a"><i class="bi bi-check2-circle"></i> Save settings</button>
    <a class="btn-a outline" href="<?= e(url('index.php')) ?>" target="_blank"><i class="bi bi-eye"></i> Preview</a>
  </div>
</form>

<?php admin_footer(); ?>
