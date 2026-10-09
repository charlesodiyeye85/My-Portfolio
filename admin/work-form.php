<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$id   = (int) ($_GET['id'] ?? 0);
$item = $id > 0 ? row('SELECT * FROM works WHERE id = :id', [':id' => $id]) : null;
if ($id > 0 && !$item) {
    flash('That work item no longer exists.', 'error');
    redirect('admin/works.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
    } else {
        $title  = trim((string) ($_POST['title'] ?? ''));
        $slugIn = trim((string) ($_POST['slug'] ?? ''));
        $slug   = slugify($slugIn !== '' ? $slugIn : $title);
        $cat    = (string) ($_POST['category'] ?? 'content-social');
        $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $keep   = (string) ($_POST['current_image'] ?? '');
        $image  = $keep;

        if ($title === '') {
            $errors[] = 'A title is required.';
        }
        if (!array_key_exists($cat, work_categories())) {
            $cat = 'content-social';
        }
        $slug = unique_slug('works', $slug, $id);

        try {
            $uploaded = save_upload($_FILES['image'] ?? [], 'works', UPLOAD_MAX_IMAGE_MB, ALLOWED_IMAGE_EXT);
            if ($uploaded) {
                if ($keep && $keep !== $uploaded) {
                    delete_media_file($keep);
                }
                $image = $uploaded;
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }

        if (!$errors) {
            $data = [
                'title'       => $title,
                'slug'        => $slug,
                'category'    => $cat,
                'client'      => trim((string) ($_POST['client'] ?? '')),
                'tools'       => trim((string) ($_POST['tools'] ?? '')),
                'year'        => trim((string) ($_POST['year'] ?? '')),
                'description' => trim((string) ($_POST['description'] ?? '')),
                'image'       => $image,
                'video_url'   => trim((string) ($_POST['video_url'] ?? '')),
                'external_url'=> trim((string) ($_POST['external_url'] ?? '')),
                'status'      => $status,
                'is_featured' => isset($_POST['is_featured']) ? 1 : 0,
            ];
            if ($id > 0) {
                update_by_id('works', $id, $data);
                flash('Work updated.');
            } else {
                $id = insert('works', $data);
                flash('Work uploaded and saved.');
            }
            redirect('admin/works.php');
        }
        $item = array_merge($item ?? [], $data ?? []);
    }
}

$cats = work_categories();
admin_header($id ? 'Edit Work' : 'Upload New Work', 'works', $id ? 'Update the details of this showcase box' : 'Add a box to your portfolio showcase');
?>

<div class="toolbar-row">
  <div>
    <h1><?= $id ? 'Edit Work' : 'Upload New Work' ?></h1>
    <p><?= $id ? e($item['title'] ?? '') : 'Fill in the details, drop an image, and publish when ready.' ?></p>
  </div>
  <a class="btn-a outline" href="<?= e(url('admin/works.php')) ?>"><i class="bi bi-arrow-left"></i> Back to showcase</a>
</div>

<?php foreach ($errors as $err): ?>
  <div class="a-alert error"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div>
<?php endforeach; ?>

<form class="form-a" method="post" enctype="multipart/form-data" action="<?= e(url('admin/work-form.php' . ($id ? '?id=' . $id : ''))) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="current_image" value="<?= e($item['image'] ?? '') ?>">

  <div class="a-card">
    <h3><i class="bi bi-card-image"></i> Showcase box</h3>

    <div class="form-row-a">
      <div class="field">
        <label>Title <span class="req">*</span></label>
        <input type="text" name="title" required maxlength="190" data-slug-source
               value="<?= e($item['title'] ?? '') ?>" placeholder="e.g. Digital Content Series">
      </div>
      <div class="field">
        <label>URL slug</label>
        <input type="text" name="slug" data-slug-target maxlength="190"
               value="<?= e($item['slug'] ?? '') ?>" placeholder="auto-generated-from-title">
        <p class="hint">Leave blank to generate from the title.</p>
      </div>
    </div>

    <div class="form-row-a three" style="margin-top:18px">
      <div class="field">
        <label>Category <span class="req">*</span></label>
        <select name="category">
          <?php foreach ($cats as $key => $label): ?>
            <option value="<?= e($key) ?>" <?= ($item['category'] ?? '') === $key ? 'selected' : '' ?>><?= e($label) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Client / Context</label>
        <input type="text" name="client" maxlength="190" value="<?= e($item['client'] ?? '') ?>" placeholder="Brand, hospital, personal…">
      </div>
      <div class="field">
        <label>Year</label>
        <input type="text" name="year" maxlength="10" value="<?= e($item['year'] ?? (string) date('Y')) ?>" placeholder="2026">
      </div>
    </div>

    <div class="field" style="margin-top:18px">
      <label>Tools used</label>
      <input type="text" name="tools" maxlength="190" value="<?= e($item['tools'] ?? '') ?>" placeholder="CapCut, Premiere Pro, QGIS…">
    </div>

    <div class="field" style="margin-top:18px">
      <label>Description <span class="req">*</span></label>
      <textarea name="description" class="tall" required placeholder="What this project is, the problem, the approach and the result."><?= e($item['description'] ?? '') ?></textarea>
    </div>

    <div class="form-row-a" style="margin-top:18px">
      <div class="field">
        <label>Video URL (YouTube / Vimeo)</label>
        <input type="url" name="video_url" value="<?= e($item['video_url'] ?? '') ?>" placeholder="https://youtube.com/watch?v=…">
        <p class="hint">Optional. Opens inside the portfolio lightbox.</p>
      </div>
      <div class="field">
        <label>External project link</label>
        <input type="url" name="external_url" value="<?= e($item['external_url'] ?? '') ?>" placeholder="https://…">
        <p class="hint">Optional. Shown as the “Visit Project” button.</p>
      </div>
    </div>
  </div>

  <div class="a-card">
    <h3><i class="bi bi-upload"></i> Cover image</h3>
    <div class="form-row-a">
      <div>
        <div class="drop-zone" data-drop>
          <i class="bi bi-cloud-arrow-up-fill"></i>
          <b>Drop an image here or click to browse</b>
          <small><?= !empty($item['image']) ? e((string) $item['image']) : 'JPG, PNG, WEBP, GIF or SVG · max ' . UPLOAD_MAX_IMAGE_MB . ' MB' ?></small>
          <input type="file" name="image" accept="<?= e(implode(',', array_map(static fn($x): string => '.' . $x, ALLOWED_IMAGE_EXT))) ?>" hidden>
        </div>
        <div class="preview-box"><img alt="Preview"></div>
        <p class="hint">Leave empty to keep the current image. Empty box uses the matching placeholder art.</p>
      </div>
      <div>
        <label>Current image</label>
        <?php if (!empty($item['image'])): ?>
          <img src="<?= e(url($item['image'])) ?>" alt="" style="width:100%;max-height:220px;object-fit:cover;border-radius:13px;border:1px solid var(--a-line)">
        <?php else: ?>
          <div class="empty-a" style="padding:26px"><i class="bi bi-image"></i><p>No image uploaded yet.</p></div>
        <?php endif; ?>
      </div>
    </div>

    <div style="display:flex;gap:26px;flex-wrap:wrap;margin-top:22px">
      <label class="check-a"><input type="checkbox" name="status" value="published" <?= ($item['status'] ?? 'draft') === 'published' ? 'checked' : '' ?>> Publish immediately</label>
      <label class="check-a"><input type="checkbox" name="is_featured" value="1" <?= (int) ($item['is_featured'] ?? 0) === 1 ? 'checked' : '' ?>> Feature at the top of the grid</label>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-a"><i class="bi bi-check2-circle"></i> <?= $id ? 'Save changes' : 'Upload work' ?></button>
      <a class="btn-a outline" href="<?= e(url('admin/works.php')) ?>">Cancel</a>
      <a class="btn-a outline" href="<?= e(url('index.php')) ?>#work" target="_blank"><i class="bi bi-eye"></i> Preview grid</a>
    </div>
  </div>
</form>

<?php admin_footer(); ?>
