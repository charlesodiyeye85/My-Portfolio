<?php
declare(strict_types=1);

require_once __DIR__ . '/config/functions.php';

/* ---------- Handle contact form submission ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'contact') {
    if (!verify_csrf()) {
        redirect('index.php#contact');
    }
    $name    = trim((string) ($_POST['name'] ?? ''));
    $email   = trim((string) ($_POST['email'] ?? ''));
    $subject = trim((string) ($_POST['subject'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    $valid = $name !== '' && mb_strlen($name) <= 100
        && filter_var($email, FILTER_VALIDATE_EMAIL) !== false
        && $message !== '' && mb_strlen($message) <= 2000;

    if ($valid) {
        try {
            insert('messages', [
                'name'    => $name,
                'email'   => $email,
                'subject' => $subject !== '' ? $subject : 'Portfolio enquiry',
                'body'    => $message,
            ]);
            redirect('index.php?sent=1#contact');
        } catch (Throwable $e) {
            redirect('index.php?failed=1#contact');
        }
    }
    redirect('index.php?failed=1#contact');
}

$works     = get_published_works();
$posts     = get_published_posts(3);
$contactOk = isset($_GET['sent']);
$contactErr = isset($_GET['failed']);

$stats = [
    ['value' => (int) scalar("SELECT COUNT(*) FROM works WHERE status='published'", [], 0), 'label' => 'Projects Delivered'],
    ['value' => (int) scalar("SELECT COUNT(*) FROM posts WHERE status='published'", [], 0), 'label' => 'Journal Entries'],
    ['value' => (int) scalar('SELECT COALESCE(SUM(views),0) FROM works', [], 0) + (int) scalar('SELECT COALESCE(SUM(views),0) FROM posts', [], 0), 'label' => 'Total Views'],
    ['value' => count(work_categories()), 'label' => 'Skill Domains'],
];

$socials = [
    'instagram' => ['bi-instagram', 'Instagram'],
    'tiktok'    => ['bi-tiktok', 'TikTok'],
    'twitter'   => ['bi-twitter-x', 'X'],
    'youtube'   => ['bi-youtube', 'YouTube'],
    'linkedin'  => ['bi-linkedin', 'LinkedIn'],
    'behance'   => ['bi-behance', 'Behance'],
];
$activeSocials = [];
foreach ($socials as $key => $meta) {
    $v = setting($key, '#');
    if ($v !== '' && $v !== '#') {
        $activeSocials[$key] = [$meta[0], $meta[1], $v];
    }
}

$services = [
    ['icon' => 'bi-megaphone-fill', 'title' => 'Social Media Management', 'text' => 'Content calendars, community management, reels strategy and monthly analytics for brands that want to grow honestly and consistently.'],
    ['icon' => 'bi-lightning-charge-fill', 'title' => 'Digital Content Creation', 'text' => 'Scroll-stopping digital content — edits, breakdowns and trending formats built for shares, saves and a loyal audience.'],
    ['icon' => 'bi-clipboard2-pulse-fill', 'title' => 'Radiography & Imaging', 'text' => 'Professional radiographic positioning, exposure and patient care — diagnostic image quality with a human touch.'],
    ['icon' => 'bi-film', 'title' => 'Video Editing', 'text' => 'Colour grading, sound design, motion titles and pacing for reels, brand films, documentaries and explainers.'],
    ['icon' => 'bi-globe2', 'title' => 'Geographic Design', 'text' => 'Thematic maps, data visualisation and spatial infographics in QGIS, ArcGIS and Illustrator that people actually read.'],
    ['icon' => 'bi-easel-fill', 'title' => 'Visual & Brand Design', 'text' => 'Thumbnails, carousels, posters and brand kits that keep every platform looking like one confident voice.'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(setting('site_title', 'Charles Odeye Damilola')) ?> — Portfolio</title>
<meta name="description" content="<?= e(excerpt_text(setting('hero_subtitle'), 155)) ?>">
<meta name="author" content="Charles Odeye Damilola">
<meta property="og:title" content="<?= e(setting('site_title')) ?> — Portfolio">
<meta property="og:description" content="<?= e(excerpt_text(setting('hero_subtitle'), 155)) ?>">
<meta property="og:image" content="<?= e(url(setting('hero_image'))) ?>">
<meta property="og:type" content="website">
<link rel="icon" href="<?= e(url('assets/img/favicon.svg')) ?>" type="image/svg+xml">
<link rel="stylesheet" href="<?= e(url('assets/vendor/bootstrap-icons/bootstrap-icons.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body data-base="<?= e(app_base()) ?>">

<!-- ===== NAVBAR ===== -->
<header class="navbar-x">
  <div class="nav-inner">
    <a href="#home" class="brand">
      <span class="brand-mark">CD</span>
      <span class="brand-name">Charles O. Damilola<small>Portfolio</small></span>
    </a>
    <nav>
      <ul class="nav-links">
        <li><a href="#home" class="active">Home</a></li>
        <li><a href="#about">About</a></li>
        <li><a href="#services">Services</a></li>
        <li><a href="#work">Work</a></li>
        <li><a href="#journal">Journal</a></li>
        <li><a href="#contact">Contact</a></li>
        <li><a href="#contact" class="nav-cta">Hire Me</a></li>
      </ul>
    </nav>
    <button class="burger" aria-label="Open menu"><i class="bi bi-list"></i></button>
  </div>
</header>

<div class="mobile-menu">
  <button class="mobile-close" aria-label="Close menu"><i class="bi bi-x-lg"></i></button>
  <a href="#home">Home</a>
  <a href="#about">About</a>
  <a href="#services">Services</a>
  <a href="#work">Work</a>
  <a href="#journal">Journal</a>
  <a href="#contact">Contact</a>
</div>

<!-- ===== HERO ===== -->
<section class="hero" id="home">
  <div class="hero-bg"></div>
  <div class="hero-grid-lines"></div>
  <div class="container-x hero-inner">
    <div>
      <span class="hero-badge"><span class="dot"></span> <?= e(setting('availability', 'Available for opportunities')) ?></span>
      <h1>Charles Odeye<br><span class="grad">Damilola</span></h1>
      <div class="hero-role">
        <span class="typed" data-typewriter
          data-words="Digital Content &amp; Social Media Executive|Radiographer|Video Editor|Geographic Designer"></span><span class="caret"></span>
      </div>
      <p class="lead"><?= e(setting('hero_subtitle')) ?></p>
      <div class="hero-actions">
        <a href="#work" class="btn-x btn-primary-x">View My Work <i class="bi bi-arrow-right"></i></a>
        <a href="#contact" class="btn-x btn-ghost-x"><i class="bi bi-send"></i> Let's Talk</a>
      </div>
      <?php if ($activeSocials): ?>
      <div class="hero-socials">
        <?php foreach ($activeSocials as $s): ?>
          <a href="<?= e($s[2]) ?>" target="_blank" rel="noopener" title="<?= e($s[1]) ?>" aria-label="<?= e($s[1]) ?>"><i class="bi <?= e($s[0]) ?>"></i></a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
    <div class="hero-photo">
      <div class="hero-photo-ring"></div>
      <div class="frame">
        <img src="<?= e(url(setting('profile_photo'))) ?>" alt="Charles Odeye Damilola">
      </div>
      <div class="hero-float f1"><i class="bi bi-camera-reels"></i><div>Video Editing<small>Reels · Films · Explainers</small></div></div>
      <div class="hero-float f2"><i class="bi bi-activity"></i><div>Radiographer<small>Diagnostic Imaging</small></div></div>
    </div>
  </div>
</section>

<!-- ===== STATS ===== -->
<div class="container-x">
  <div class="stats">
    <?php foreach ($stats as $s): ?>
    <div class="stat reveal"><b data-count="<?= (int) $s['value'] ?>">0</b><span><?= e($s['label']) ?></span></div>
    <?php endforeach; ?>
  </div>
</div>

<!-- ===== ABOUT ===== -->
<section class="section" id="about">
  <div class="container-x about-grid">
    <div class="about-photo reveal">
      <div class="photo-wrap"><img src="<?= e(url(setting('profile_photo'))) ?>" alt="About Charles"></div>
      <div class="about-exp"><b>4+</b><span>Years across media<br>&amp; healthcare</span></div>
    </div>
    <div class="about-text reveal">
      <span class="eyebrow">About Me</span>
      <h2 class="section-title">A creative mind with a <em>clinical eye</em></h2>
      <p><?= e(setting('bio')) ?></p>
      <div class="about-tags">
        <span class="tag">Content Strategy</span>
        <span class="tag">Social Media</span>
        <span class="tag">Radiography</span>
        <span class="tag">Video Editing</span>
        <span class="tag">GIS / Mapping</span>
        <span class="tag">Graphic Design</span>
      </div>
      <div class="skills">
        <?php
        $skills = [
            ['Content & Social Media', 95],
            ['Video Editing', 92],
            ['Radiography & Imaging', 90],
            ['Geographic Design', 85],
        ];
        foreach ($skills as $sk):
        ?>
        <div>
          <div class="skill-head"><span><?= e($sk[0]) ?></span><span><?= $sk[1] ?>%</span></div>
          <div class="skill-bar"><i data-level="<?= $sk[1] ?>"></i></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>

<!-- ===== SERVICES ===== -->
<section class="section section-alt" id="services">
  <div class="container-x">
    <span class="eyebrow reveal">What I Do</span>
    <h2 class="section-title reveal">Services built for <em>brands &amp; people</em></h2>
    <p class="section-lead reveal">Four disciplines, one standard: clear thinking, clean execution, results you can measure.</p>
    <div class="services-grid">
      <?php foreach ($services as $i => $s): ?>
      <article class="service-card reveal">
        <span class="service-num">0<?= $i + 1 ?></span>
        <div class="service-icon"><i class="bi <?= e($s['icon']) ?>"></i></div>
        <h3><?= e($s['title']) ?></h3>
        <p><?= e($s['text']) ?></p>
        <a href="#contact" class="read-more">Discuss a project <i class="bi bi-arrow-right"></i></a>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== WORK ===== -->
<section class="section" id="work">
  <div class="container-x">
    <span class="eyebrow reveal">Portfolio</span>
    <h2 class="section-title reveal">Selected <em>work &amp; showcase</em></h2>
    <p class="section-lead reveal">Boxes of work across content, radiography, video and cartography — tap any box to open it.</p>

    <div class="work-filters reveal">
      <button class="filter-btn active" data-filter="all">All Work</button>
      <?php foreach (work_categories() as $key => $label): ?>
      <button class="filter-btn" data-filter="<?= e($key) ?>"><?= e($label) ?></button>
      <?php endforeach; ?>
    </div>

    <div class="works-grid" id="worksGrid">
      <?php if (!$works): ?>
        <div class="empty-state">
          <i class="bi bi-images"></i>
          <p>No published work yet. Log in to the <a href="<?= e(url('admin/login.php')) ?>">upload section</a> to add your first piece.</p>
        </div>
      <?php endif; ?>
      <?php foreach ($works as $w): ?>
      <article class="work-card reveal"
        tabindex="0"
        data-id="<?= (int) $w['id'] ?>"
        data-category="<?= e($w['category']) ?>"
        data-title="<?= e($w['title']) ?>"
        data-image="<?= e(url($w['image'])) ?>"
        data-video="<?= e($w['video_url']) ?>"
        data-description="<?= e($w['description']) ?>"
        data-client="<?= e($w['client']) ?>"
        data-tools="<?= e($w['tools']) ?>"
        data-year="<?= e($w['year']) ?>"
        data-link="<?= e($w['external_url']) ?>">
        <?php if ((int) $w['is_featured'] === 1): ?><span class="work-featured">Featured</span><?php endif; ?>
        <div class="work-thumb"><img src="<?= e(url($w['image'])) ?>" alt="<?= e($w['title']) ?>" loading="lazy"></div>
        <div class="work-overlay">
          <span class="work-cat"><?= e(category_label($w['category'])) ?></span>
          <span class="work-open"><i class="bi bi-arrow-up-right"></i></span>
        </div>
        <div class="work-body">
          <h3><?= e($w['title']) ?></h3>
          <p><?= e(excerpt_text($w['description'], 90)) ?></p>
          <div class="work-meta">
            <span><i class="bi bi-building"></i><?= e($w['client'] ?: 'Personal') ?></span>
            <span><i class="bi bi-calendar3"></i><?= e($w['year'] ?: date('Y')) ?></span>
            <span><i class="bi bi-eye"></i><?= (int) $w['views'] ?></span>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== LIGHTBOX ===== -->
<div class="lightbox" id="workLightbox" role="dialog" aria-modal="true" aria-label="Work details">
  <button class="lightbox-close" aria-label="Close"><i class="bi bi-x-lg"></i></button>
  <button class="lightbox-nav prev" aria-label="Previous"><i class="bi bi-chevron-left"></i></button>
  <button class="lightbox-nav next" aria-label="Next"><i class="bi bi-chevron-right"></i></button>
  <div class="lightbox-panel">
    <div class="lightbox-media"></div>
    <div class="lightbox-info">
      <span class="work-cat lb-cat"></span>
      <h3 class="lb-title"></h3>
      <p class="lb-desc"></p>
      <dl>
        <dt>Client</dt><dd class="lb-client"></dd>
        <dt>Tools</dt><dd class="lb-tools"></dd>
        <dt>Year</dt><dd class="lb-year"></dd>
      </dl>
      <p style="margin-top:22px"><a class="btn-x btn-primary-x lb-link" target="_blank" rel="noopener" href="#">Visit Project <i class="bi bi-box-arrow-up-right"></i></a></p>
    </div>
  </div>
</div>

<!-- ===== JOURNAL ===== -->
<section class="section section-alt" id="journal">
  <div class="container-x">
    <span class="eyebrow reveal">Journal</span>
    <h2 class="section-title reveal">Notes from the <em>field</em></h2>
    <p class="section-lead reveal">Content systems, radiography practice, editing craft and mapping stories — written from real projects.</p>
    <div class="posts-grid">
      <?php if (!$posts): ?>
        <div class="empty-state"><i class="bi bi-journal-text"></i><p>No posts published yet. Add one from the CMS.</p></div>
      <?php endif; ?>
      <?php foreach ($posts as $p): ?>
      <article class="post-card reveal">
        <a class="post-cover" href="<?= e(url('post.php?slug=' . urlencode($p['slug']))) ?>">
          <img src="<?= e(url($p['cover_image'] ?: 'assets/img/placeholders/post-content.svg')) ?>" alt="<?= e($p['title']) ?>" loading="lazy">
        </a>
        <div class="post-content">
          <div class="post-meta">
            <span class="cat"><?= e($p['category']) ?></span>
            <span><i class="bi bi-calendar3"></i> <?= e(format_date($p['published_at'])) ?></span>
            <span><i class="bi bi-clock"></i> <?= max(1, (int) ceil(str_word_count(strip_tags($p['body'])) / 200)) ?> min read</span>
          </div>
          <h3><a href="<?= e(url('post.php?slug=' . urlencode($p['slug']))) ?>"><?= e($p['title']) ?></a></h3>
          <p><?= e($p['excerpt'] ?: excerpt_text($p['body'])) ?></p>
          <a class="read-more" href="<?= e(url('post.php?slug=' . urlencode($p['slug']))) ?>">Read article <i class="bi bi-arrow-right"></i></a>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <p style="text-align:center;margin-top:38px" class="reveal">
      <a href="<?= e(url('index.php')) ?>#journal" class="btn-x btn-ghost-x">View the journal <i class="bi bi-arrow-right"></i></a>
    </p>
  </div>
</section>

<!-- ===== CONTACT ===== -->
<section class="section" id="contact">
  <div class="container-x">
    <span class="eyebrow reveal">Contact</span>
    <h2 class="section-title reveal">Let's build something <em>worth watching</em></h2>
    <p class="section-lead reveal">Freelance projects, full-time roles, collaborations — send a message and I'll reply promptly.</p>

    <div class="contact-grid">
      <div class="contact-info reveal">
        <div class="contact-item">
          <i class="bi bi-envelope-fill"></i>
          <div><b>Email</b><span><?= e(setting('email')) ?></span></div>
        </div>
        <div class="contact-item">
          <i class="bi bi-telephone-fill"></i>
          <div><b>Phone / WhatsApp</b><span><?= e(setting('phone')) ?></span></div>
        </div>
        <div class="contact-item">
          <i class="bi bi-geo-alt-fill"></i>
          <div><b>Location</b><span><?= e(setting('location')) ?></span></div>
        </div>
        <div class="contact-item">
          <i class="bi bi-clock-history"></i>
          <div><b>Availability</b><span><?= e(setting('availability')) ?></span></div>
        </div>
        <?php if ($activeSocials): ?>
        <div class="hero-socials" style="margin-top:6px">
          <?php foreach ($activeSocials as $s): ?>
            <a href="<?= e($s[2]) ?>" target="_blank" rel="noopener" title="<?= e($s[1]) ?>"><i class="bi <?= e($s[0]) ?>"></i></a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <form class="contact-form reveal" id="contactForm" method="post" action="<?= e(url('index.php')) ?>#contact">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="contact">
        <?php if ($contactOk): ?>
          <div class="alert-x success" data-autohide><i class="bi bi-check-circle-fill"></i> Message sent! I'll get back to you shortly.</div>
        <?php endif; ?>
        <?php if ($contactErr): ?>
          <div class="alert-x error" data-autohide><i class="bi bi-exclamation-triangle-fill"></i> Something went wrong. Please try again.</div>
        <?php endif; ?>
        <div class="form-row">
          <div class="field">
            <label for="c-name">Your Name</label>
            <input id="c-name" type="text" name="name" placeholder="John Doe" required maxlength="100">
          </div>
          <div class="field">
            <label for="c-email">Email Address</label>
            <input id="c-email" type="email" name="email" placeholder="john@example.com" required maxlength="150">
          </div>
        </div>
        <div class="field">
          <label for="c-subject">Subject</label>
          <input id="c-subject" type="text" name="subject" placeholder="Project enquiry" maxlength="150">
        </div>
        <div class="field">
          <label for="c-message">Message</label>
          <textarea id="c-message" name="message" placeholder="Tell me about the project, timeline and budget..." required maxlength="2000"></textarea>
        </div>
        <button type="submit" class="btn-x btn-primary-x" style="border:none;width:100%;justify-content:center">
          Send Message <i class="bi bi-send-fill"></i>
        </button>
      </form>
    </div>
  </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
  <div class="container-x">
    <div class="footer-grid">
      <div>
        <a href="#home" class="brand" style="margin-bottom:16px">
          <span class="brand-mark">CD</span>
          <span class="brand-name">Charles O. Damilola<small>Portfolio</small></span>
        </a>
        <p><?= e(setting('tagline')) ?><br><?= e(excerpt_text(setting('bio'), 150)) ?></p>
      </div>
      <div>
        <h4>Explore</h4>
        <ul>
          <li><a href="#about">About</a></li>
          <li><a href="#services">Services</a></li>
          <li><a href="#work">Work Showcase</a></li>
          <li><a href="#journal">Journal</a></li>
          <li><a href="#contact">Contact</a></li>
        </ul>
      </div>
      <div>
        <h4>Reach Me</h4>
        <ul>
          <li><a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a></li>
          <li><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('phone'))) ?>"><?= e(setting('phone')) ?></a></li>
          <li><span style="color:var(--muted)"><?= e(setting('location')) ?></span></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span><?= e(setting('footer_note')) ?></span>
      <span>Designed &amp; managed with the Charles D. CMS</span>
    </div>
  </div>
</footer>

<button class="to-top" aria-label="Back to top"><i class="bi bi-arrow-up"></i></button>

<script src="<?= e(url('assets/js/main.js')) ?>"></script>
<?php if ($contactOk || $contactErr): ?>
<script>window.addEventListener('load',function(){var t=document.getElementById('contact');if(t)t.scrollIntoView();});</script>
<?php endif; ?>
</body>
</html>
<?php
/* ---------- Handle contact form submission ---------- */
