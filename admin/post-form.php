<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/layout.php';

$id   = (int) ($_GET['id'] ?? 0);
$item = $id > 0 ? row('SELECT * FROM posts WHERE id = :id', [':id' => $id]) : null;
if ($id > 0 && !$item) {
    flash('That post no longer exists.', 'error');
    redirect('admin/posts.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $errors[] = 'Session expired. Please try again.';
    } else {
        $title  = trim((string) ($_POST['title'] ?? ''));
        $slugIn = trim((string) ($_POST['slug'] ?? ''));
        $body   = sanitize_html((string) ($_POST['body'] ?? ''));
        $excerpt= trim((string) ($_POST['excerpt'] ?? ''));
        $status = ($_POST['status'] ?? 'draft') === 'published' ? 'published' : 'draft';
        $keep   = (string) ($_POST['current_cover'] ?? '');
        $cover  = $keep;

        if ($title === '') {
            $errors[] = 'A title is required.';
        }
        if (trim(strip_tags($body)) === '' && trim($body) === '') {
            $errors[] = 'The post body cannot be empty.';
        }
        if ($excerpt === '') {
            $excerpt = excerpt_text($body, 180);
        }

        $slug = unique_slug('posts', slugify($slugIn !== '' ? $slugIn : $title), $id);

        try {
            $uploaded = save_upload($_FILES['cover_image'] ?? [], 'posts', UPLOAD_MAX_IMAGE_MB, ALLOWED_IMAGE_EXT);
            if ($uploaded) {
                if ($keep && $keep !== $uploaded) {
                    delete_media_file($keep);
                }
                $cover = $uploaded;
            }
        } catch (Throwable $e) {
            $errors[] = $e->getMessage();
        }

        if (!$errors) {
            $user = current_user();
            $data = [
                'title'       => $title,
                'slug'        => $slug,
                'excerpt'     => $excerpt,
                'body'        => $body,
                'cover_image' => $cover,
                'category'    => trim((string) ($_POST['category'] ?? 'General')) ?: 'General',
                'status'      => $status,
                'author_id'   => (int) ($user['id'] ?? 1),
            ];
            if ($status === 'published') {
                $data['published_at'] = ($_POST['published_at'] ?? '') !== ''
                    ? (string) $_POST['published_at']
                    : (($item['published_at'] ?? '') ?: date('Y-m-d H:i:s'));
            }
            if ($id > 0) {
                update_by_id('posts', $id, $data);
                flash('Post updated.');
            } else {
                $id = insert('posts', $data);
                flash($status === 'published' ? 'Post published.' : 'Draft saved.');
            }
            redirect('admin/posts.php');
        }
        $item = array_merge($item ?? [], $data ?? []);
    }
}

$related = $id > 0
    ? rows('SELECT id, title FROM posts WHERE id <> :id AND status = :s ORDER BY published_at DESC LIMIT 6', [':id' => $id, ':s' => 'published'])
    : [];

admin_header($id ? 'Edit Post' : 'Write a Post', 'posts', $id ? 'Update the body content of this article' : 'Long-form journal entry for your audience');
?>

<div class="toolbar-row">
  <div>
    <h1><?= $id ? 'Edit Post' : 'Write a Post' ?></h1>
    <p><?= $id ? e($item['title'] ?? '') : 'Draft it in the editor below, then publish when it is ready.' ?></p>
  </div>
  <a class="btn-a outline" href="<?= e(url('admin/posts.php')) ?>"><i class="bi bi-arrow-left"></i> Back to posts</a>
</div>

<?php foreach ($errors as $err): ?>
  <div class="a-alert error"><i class="bi bi-exclamation-triangle-fill"></i> <?= e($err) ?></div>
<?php endforeach; ?>

<form class="form-a" method="post" enctype="multipart/form-data" action="<?= e(url('admin/post-form.php' . ($id ? '?id=' . $id : ''))) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="current_cover" value="<?= e($item['cover_image'] ?? '') ?>">

  <div class="a-card">
    <h3><i class="bi bi-journal-richtext"></i> Article</h3>

    <div class="form-row-a">
      <div class="field">
        <label>Title <span class="req">*</span></label>
        <input type="text" name="title" required maxlength="190" data-slug-source
               value="<?= e($item['title'] ?? '') ?>" placeholder="e.g. 5 Editing Transitions That Actually Work">
      </div>
      <div class="field">
        <label>URL slug</label>
        <input type="text" name="slug" data-slug-target maxlength="190" value="<?= e($item['slug'] ?? '') ?>">
        <p class="hint">Leave blank to generate from the title.</p>
      </div>
    </div>

    <div class="form-row-a three" style="margin-top:18px">
      <div class="field">
        <label>Category</label>
        <input type="text" name="category" maxlength="80" list="catlist" value="<?= e($item['category'] ?? 'General') ?>">
        <datalist id="catlist">
          <option>Content &amp; Social Media</option>
          <option>Social Media</option>
          <option>Jujutsu</option>
          <option>Radiography</option>
          <option>Video Editing</option>
          <option>Geographic Design</option>
          <option>Career</option>
          <option>General</option>
        </datalist>
      </div>
      <div class="field">
        <label>Status</label>
        <select name="status">
          <option value="draft"     <?= ($item['status'] ?? 'draft') === 'draft' ? 'selected' : '' ?>>Draft</option>
          <option value="published" <?= ($item['status'] ?? '') === 'published' ? 'selected' : '' ?>>Published</option>
        </select>
      </div>
      <div class="field">
        <label>Publish date</label>
        <input type="datetime-local" name="published_at"
               value="<?= e(!empty($item['published_at']) ? date('Y-m-d\TH:i', strtotime((string) $item['published_at'])) : date('Y-m-d\TH:i')) ?>">
        <p class="hint">Only applied once published.</p>
      </div>
    </div>

    <div class="field" style="margin-top:18px">
      <label>Excerpt</label>
      <textarea name="excerpt" maxlength="400" style="min-height:90px" placeholder="One or two lines shown on the journal cards. Leave blank to auto-generate from the body."><?= e($item['excerpt'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="a-card" id="preview">
    <h3><i class="bi bi-pencil-fill"></i> Body content</h3>

    <div class="rte-toolbar" data-rte-toolbar>
      <button type="button" data-cmd="bold" title="Bold"><i class="bi bi-type-bold"></i></button>
      <button type="button" data-cmd="italic" title="Italic"><i class="bi bi-type-italic"></i></button>
      <button type="button" data-cmd="underline" title="Underline"><i class="bi bi-type-underline"></i></button>
      <span class="sep"></span>
      <button type="button" data-cmd="formatBlock" data-val="h2" title="Heading 2"><i class="bi bi-type-h2"></i></button>
      <button type="button" data-cmd="formatBlock" data-val="h3" title="Heading 3"><i class="bi bi-type-h3"></i></button>
      <button type="button" data-cmd="formatBlock" data-val="p" title="Paragraph"><i class="bi bi-paragraph"></i></button>
      <span class="sep"></span>
      <button type="button" data-cmd="insertUnorderedList" title="Bulleted list"><i class="bi bi-list-ul"></i></button>
      <button type="button" data-cmd="insertOrderedList" title="Numbered list"><i class="bi bi-list-ol"></i></button>
      <button type="button" data-cmd="formatBlock" data-val="blockquote" title="Quote"><i class="bi bi-quote"></i></button>
      <span class="sep"></span>
      <button type="button" data-cmd="createLink" title="Insert link"><i class="bi bi-link-45deg"></i></button>
      <button type="button" data-cmd="removeFormat" title="Clear formatting"><i class="bi bi-eraser"></i></button>
      <span class="sep"></span>
      <button type="button" data-cmd="undo" title="Undo"><i class="bi bi-arrow-counterclockwise"></i></button>
      <button type="button" data-cmd="redo" title="Redo"><i class="bi bi-arrow-clockwise"></i></button>
    </div>
    <div class="rte-area" contenteditable="true" data-rte
         data-placeholder="Write the article here… headings, lists, quotes and links all work."></div>
    <textarea name="body" data-rte-value hidden><?= e($item['body'] ?? '') ?></textarea>
    <p class="hint">The editor keeps your formatting clean — pasted text is inserted as plain text.</p>
  </div>

  <div class="a-card">
    <h3><i class="bi bi-card-image"></i> Cover image</h3>
    <div class="form-row-a">
      <div>
        <div class="drop-zone" data-drop>
          <i class="bi bi-cloud-arrow-up-fill"></i>
          <b>Drop a cover image or click to browse</b>
          <small><?= !empty($item['cover_image']) ? e((string) $item['cover_image']) : 'JPG, PNG, WEBP, GIF or SVG · max ' . UPLOAD_MAX_IMAGE_MB . ' MB' ?></small>
          <input type="file" name="cover_image" accept="<?= e(implode(',', array_map(static fn($x): string => '.' . $x, ALLOWED_IMAGE_EXT))) ?>" hidden>
        </div>
        <div class="preview-box"><img alt="Preview"></div>
      </div>
      <div>
        <label>Current cover</label>
        <?php if (!empty($item['cover_image'])): ?>
          <img src="<?= e(url($item['cover_image'])) ?>" alt="" style="width:100%;max-height:200px;object-fit:cover;border-radius:13px;border:1px solid var(--a-line)">
        <?php else: ?>
          <div class="empty-a" style="padding:24px"><i class="bi bi-image"></i><p>No cover yet.</p></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn-a"><i class="bi bi-check2-circle"></i> <?= $id ? 'Save changes' : 'Save post' ?></button>
      <a class="btn-a outline" href="<?= e(url('admin/posts.php')) ?>">Cancel</a>
      <?php if ($id && ($item['status'] ?? '') === 'published'): ?>
        <a class="btn-a outline" href="<?= e(url('post.php?slug=' . urlencode((string) $item['slug']))) ?>" target="_blank"><i class="bi bi-eye"></i> View live</a>
      <?php endif; ?>
    </div>
  </div>
</form>

<?php if ($related): ?>
<div class="a-card">
  <h3><i class="bi bi-link-45deg"></i> Recent published posts</h3>
  <div class="list-rows">
    <?php foreach ($related as $r): ?>
      <div class="list-row">
        <div class="grow"><b><?= e($r['title']) ?></b></div>
        <a class="btn-a ghost-icon" href="<?= e(url('admin/post-form.php?id=' . (int) $r['id'])) ?>" title="Edit"><i class="bi bi-pencil"></i></a>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php admin_footer(); ?>
