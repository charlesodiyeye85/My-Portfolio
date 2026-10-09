<?php
declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';

$slug = (string) ($_GET['slug'] ?? '');
$post = $slug ? get_post_by_slug($slug) : null;

if (!$post) {
    http_response_code(404);
}

$related = $post ? get_published_posts(3) : [];
$related = array_values(array_filter($related, fn($p) => $p['id'] !== $post['id']));

$socials = [];
foreach (['instagram' => 'bi-instagram', 'tiktok' => 'bi-tiktok', 'twitter' => 'bi-twitter-x', 'youtube' => 'bi-youtube', 'linkedin' => 'bi-linkedin'] as $k => $icon) {
    $v = setting($k, '#');
    if ($v !== '' && $v !== '#') {
        $socials[] = [$icon, $k, $v];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= $post ? e($post['title']) . ' — ' : '' ?><?= e(setting('site_title')) ?> Journal</title>
<meta name="description" content="<?= e($post ? $post['excerpt'] : 'Articles by Charles Odeye Damilola') ?>">
<meta property="og:title" content="<?= $post ? e($post['title']) : 'Journal' ?>">
<meta property="og:description" content="<?= e($post ? $post['excerpt'] : '') ?>">
<meta property="og:image" content="<?= $post ? e(url($post['cover_image'])) : e(url(setting('hero_image'))) ?>">
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(url('assets/vendor/bootstrap-icons/bootstrap-icons.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body data-base="<?= e(app_base()) ?>">

<header class="navbar-x">
  <div class="nav-inner">
    <a href="<?= e(url('index.php')) ?>" class="brand">
      <span class="brand-mark">CD</span>
      <span class="brand-name">Charles O. Damilola<small>Portfolio</small></span>
    </a>
    <nav>
      <ul class="nav-links">
        <li><a href="<?= e(url('index.php')) ?>#home">Home</a></li>
        <li><a href="<?= e(url('index.php')) ?>#about">About</a></li>
        <li><a href="<?= e(url('index.php')) ?>#services">Services</a></li>
        <li><a href="<?= e(url('index.php')) ?>#work">Work</a></li>
        <li><a href="<?= e(url('index.php')) ?>#journal" class="active">Journal</a></li>
        <li><a href="<?= e(url('index.php')) ?>#contact" class="nav-cta">Hire Me</a></li>
      </ul>
    </nav>
    <button class="burger" aria-label="Open menu"><i class="bi bi-list"></i></button>
  </div>
</header>

<div class="mobile-menu">
  <button class="mobile-close" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
  <a href="<?= e(url('index.php')) ?>#home">Home</a>
  <a href="<?= e(url('index.php')) ?>#about">About</a>
  <a href="<?= e(url('index.php')) ?>#services">Services</a>
  <a href="<?= e(url('index.php')) ?>#work">Work</a>
  <a href="<?= e(url('index.php')) ?>#journal">Journal</a>
  <a href="<?= e(url('index.php')) ?>#contact">Contact</a>
</div>

<?php if ($post): ?>
<article class="article-hero" data-article-id="<?= (int) $post['id'] ?>">
  <div class="container-x">
    <a class="back-link" href="<?= e(url('index.php')) ?>#journal"><i class="bi bi-arrow-left"></i> Back to journal</a>
    <div class="post-meta" style="margin-top:26px">
      <span class="cat"><?= e($post['category']) ?></span>
      <span><i class="bi bi-calendar3"></i> <?= e(format_date($post['published_at'])) ?></span>
      <span><i class="bi bi-person"></i> Charles Odeye Damilola</span>
      <span><i class="bi bi-eye"></i> <?= (int) $post['views'] ?> views</span>
    </div>
    <h1 class="section-title" style="max-width:900px"><?= e($post['title']) ?></h1>
    <?php if ($post['cover_image']): ?>
    <div class="article-cover"><img src="<?= e(url($post['cover_image'])) ?>" alt="<?= e($post['title']) ?>"></div>
    <?php endif; ?>

    <div class="article-body" style="margin-top:46px">
      <?= sanitize_html($post['body']) ?>
    </div>

    <div class="article-nav">
      <a class="back-link" href="<?= e(url('index.php')) ?>#journal"><i class="bi bi-arrow-left"></i> All articles</a>
      <a class="back-link" href="<?= e(url('index.php')) ?>#contact">Work with me <i class="bi bi-arrow-right"></i></a>
    </div>

    <?php if ($related): ?>
    <section class="section" style="padding-bottom:40px">
      <span class="eyebrow">Keep reading</span>
      <h2 class="section-title">Related <em>articles</em></h2>
      <div class="posts-grid" style="margin-top:30px">
        <?php foreach ($related as $r): ?>
        <article class="post-card">
          <a class="post-cover" href="<?= e(url('post.php?slug=' . urlencode($r['slug']))) ?>">
            <img src="<?= e(url($r['cover_image'] ?: 'assets/img/placeholders/post-content.svg')) ?>" alt="<?= e($r['title']) ?>" loading="lazy">
          </a>
          <div class="post-content">
            <div class="post-meta">
              <span class="cat"><?= e($r['category']) ?></span>
              <span><i class="bi bi-calendar3"></i> <?= e(format_date($r['published_at'])) ?></span>
            </div>
            <h3><a href="<?= e(url('post.php?slug=' . urlencode($r['slug']))) ?>"><?= e($r['title']) ?></a></h3>
            <p><?= e($r['excerpt'] ?: excerpt_text($r['body'])) ?></p>
            <a class="read-more" href="<?= e(url('post.php?slug=' . urlencode($r['slug']))) ?>">Read article <i class="bi bi-arrow-right"></i></a>
          </div>
        </article>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>
  </div>
</article>
<?php else: ?>
<section class="section" style="padding-top:calc(var(--nav-h) + 80px);min-height:60vh">
  <div class="container-x" style="text-align:center">
    <span class="eyebrow">404</span>
    <h1 class="section-title">Post not <em>found</em></h1>
    <p class="section-lead" style="margin-inline:auto">This article may have been unpublished or the link is wrong.</p>
    <a href="<?= e(url('index.php')) ?>#journal" class="btn-x btn-primary-x">Back to journal</a>
  </div>
</section>
<?php endif; ?>

<footer class="footer">
  <div class="container-x">
    <div class="footer-bottom" style="border-top:none">
      <span><?= e(setting('footer_note')) ?></span>
      <span><?= e(setting('location')) ?> · <?= e(setting('email')) ?></span>
    </div>
  </div>
</footer>

<button class="to-top" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>
<script src="<?= e(url('assets/js/main.js')) ?>"></script>
</body>
</html>
