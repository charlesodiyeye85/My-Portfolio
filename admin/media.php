<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
    } else {
        try {
            $saved = save_upload($_FILES['file'] ?? [], 'works', UPLOAD_MAX_IMAGE_MB, ALLOWED_IMAGE_EXT);
            if ($saved) {
                flash('File uploaded to the media library.');
                redirect('admin/media.php');
            }
            $errors[] = 'No file selected.';
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }
    }
}

$items   = rows('SELECT * FROM media ORDER BY uploaded_at DESC, id DESC');
$used    = [];
foreach (rows('SELECT image AS p FROM works') as $r) { $used[$r['p']] = 1; }
foreach (rows('SELECT cover_image AS p FROM posts') as $r) { $used[$r['p']] = 1; }
foreach (rows("SELECT value AS p FROM settings WHERE name IN ('profile_photo','hero_image')") as $r) { $used[$r['p']] = 1; }

$stats = [
    'files' => count($items),
    'kb'    => (int) array_sum(array_map(static fn(array $m): int => (int) $m['size_kb'], $items)),
];

admin_header('Media Library', 'media', 'Every image you have uploaded — reuse, copy paths or delete');
?>

<div class="toolbar-row">
  <div>
    <h1>Media Library</h1>
    <p><?= $stats['files'] ?> file<?= $stats['files'] === 1 ? '' : 's' ?> · <?= number_format($stats['kb'] / 1024, 1) ?> MB used</p>
  </div>
  <a class="btn-a outline" href="<?= e(url('admin/work-form.php')) ?>"><i class="bi bi-images"></i> Go to showcase upload</a>
</div>

<?php foreach ($errors as $err): ?>
  <div class="a-alert error"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div>
<?php endforeach; ?>

<div class="a-card">
  <h3><i class="bi bi-cloud-arrow-up-fill"></i> Upload a file</h3>
  <form class="form-a" method="post" enctype="multipart/form-data" action="<?= e(url('admin/media.php')) ?>">
    <?= csrf_field() ?>
    <div class="form-row-a">
      <div>
        <div class="drop-zone" data-drop>
          <i class="bi bi-upload"></i>
          <b>Drop an image here or click to browse</b>
          <small>JPG, PNG, WEBP, GIF or SVG · max <?= UPLOAD_MAX_IMAGE_MB ?> MB</small>
          <input type="file" name="file" accept="<?= e(implode(',', array_map(static fn($x): string => '.' . $x, ALLOWED_IMAGE_EXT))) ?>" hidden>
        </div>
        <div class="preview-box"><img alt="Preview"></div>
      </div>
      <div>
        <p class="hint" style="margin-top:0">
          Files land in <code>uploads/works/</code>. After uploading, copy the path from any card below and
          paste it into a work, post or setting field. Files already used by your content are marked
          <span class="badge-a on">in use</span> and will not be deleted with their record.
        </p>
        <button type="submit" class="btn-a" style="margin-top:14px"><i class="bi bi-upload"></i> Upload file</button>
      </div>
    </div>
  </form>
</div>

<div class="a-card">
  <h3><i class="bi bi-folder2-open"></i> All files</h3>
  <?php if (!$items): ?>
    <div class="empty-a">
      <i class="bi bi-folder2-open"></i>
      <p>No uploads yet. Anything you add through the work or post forms appears here automatically.</p>
    </div>
  <?php else: ?>
  <div class="media-grid">
    <?php foreach ($items as $m):
        $mid   = (int) $m['id'];
        $isImg = (bool) preg_match('/\.(jpe?g|png|webp|gif|svg)$/i', (string) $m['file_path']);
        $inUse = isset($used[$m['file_path']]);
    ?>
      <div class="media-item">
        <div class="ph">
          <?php if ($isImg): ?>
            <img src="<?= e(url($m['file_path'])) ?>" alt="<?= e($m['file_name']) ?>" loading="lazy">
          <?php else: ?>
            <i class="bi bi-file-earmark-play"></i>
          <?php endif; ?>
        </div>
        <div class="media-meta">
          <b title="<?= e($m['file_name']) ?>"><?= e($m['file_name']) ?></b>
          <span><?= e(number_format((int) $m['size_kb'])) ?> KB · <?= e(format_date($m['uploaded_at'])) ?>
            <?php if ($inUse): ?> · <span class="badge-a on">in use</span><?php endif; ?>
          </span>
        </div>
        <div class="media-actions">
          <button class="btn-a ghost-icon" type="button" data-copy="<?= e(url($m['file_path'])) ?>" title="Copy path"><i class="bi bi-copy"></i></button>
          <a class="btn-a ghost-icon" href="<?= e(url($m['file_path'])) ?>" target="_blank" title="Open"><i class="bi bi-box-arrow-up-right"></i></a>
          <a class="btn-a ghost-icon del" href="#" style="margin-left:auto"
             data-confirm-url="<?= e(url('actions/delete.php?type=media&id=' . $mid)) ?>"
             data-confirm-text="Delete <?= e($m['file_name']) ?><?= $inUse ? ' — it is currently used by your content' : '' ?>?"
             title="Delete"><i class="bi bi-trash3"></i></a>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php admin_footer(); ?>
