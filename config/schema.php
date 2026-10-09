<?php
declare(strict_types=1);

/** Create all tables (idempotent) and seed demo content on first run. */
function ensure_schema(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    foreach (schema_statements() as $sql) {
        db()->exec($sql);
    }

    seed_data();
}

function schema_statements(): array
{
    if (DB_DRIVER === 'sqlite') {
        return [
            "CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL DEFAULT '',
                password_hash TEXT NOT NULL,
                full_name TEXT NOT NULL DEFAULT '',
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
            "CREATE TABLE IF NOT EXISTS works (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL,
                category TEXT NOT NULL DEFAULT 'content-social',
                client TEXT NOT NULL DEFAULT '',
                tools TEXT NOT NULL DEFAULT '',
                year TEXT NOT NULL DEFAULT '',
                description TEXT NOT NULL DEFAULT '',
                image TEXT NOT NULL DEFAULT '',
                video_url TEXT NOT NULL DEFAULT '',
                external_url TEXT NOT NULL DEFAULT '',
                status TEXT NOT NULL DEFAULT 'published',
                is_featured INTEGER NOT NULL DEFAULT 0,
                views INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
            "CREATE TABLE IF NOT EXISTS posts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                title TEXT NOT NULL,
                slug TEXT NOT NULL UNIQUE,
                excerpt TEXT NOT NULL DEFAULT '',
                body TEXT NOT NULL DEFAULT '',
                cover_image TEXT NOT NULL DEFAULT '',
                category TEXT NOT NULL DEFAULT 'General',
                status TEXT NOT NULL DEFAULT 'published',
                views INTEGER NOT NULL DEFAULT 0,
                author_id INTEGER NOT NULL DEFAULT 1,
                published_at TEXT NOT NULL DEFAULT (datetime('now')),
                created_at TEXT NOT NULL DEFAULT (datetime('now')),
                updated_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
            "CREATE TABLE IF NOT EXISTS messages (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                email TEXT NOT NULL,
                subject TEXT NOT NULL DEFAULT '',
                body TEXT NOT NULL,
                is_read INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
            "CREATE TABLE IF NOT EXISTS settings (
                name TEXT PRIMARY KEY,
                value TEXT NOT NULL DEFAULT ''
            )",
            "CREATE TABLE IF NOT EXISTS media (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                file_path TEXT NOT NULL,
                file_name TEXT NOT NULL,
                mime TEXT NOT NULL DEFAULT '',
                size_kb INTEGER NOT NULL DEFAULT 0,
                uploaded_at TEXT NOT NULL DEFAULT (datetime('now'))
            )",
        ];
    }

    return [
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(60) NOT NULL UNIQUE,
            email VARCHAR(190) NOT NULL DEFAULT '',
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(120) NOT NULL DEFAULT '',
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS works (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL,
            category VARCHAR(60) NOT NULL DEFAULT 'content-social',
            client VARCHAR(190) NOT NULL DEFAULT '',
            tools VARCHAR(190) NOT NULL DEFAULT '',
            year VARCHAR(10) NOT NULL DEFAULT '',
            description TEXT NOT NULL,
            image VARCHAR(255) NOT NULL DEFAULT '',
            video_url VARCHAR(255) NOT NULL DEFAULT '',
            external_url VARCHAR(255) NOT NULL DEFAULT '',
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            is_featured TINYINT(1) NOT NULL DEFAULT 0,
            views INT UNSIGNED NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS posts (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(190) NOT NULL,
            slug VARCHAR(190) NOT NULL UNIQUE,
            excerpt VARCHAR(400) NOT NULL DEFAULT '',
            body MEDIUMTEXT NOT NULL,
            cover_image VARCHAR(255) NOT NULL DEFAULT '',
            category VARCHAR(80) NOT NULL DEFAULT 'General',
            status VARCHAR(20) NOT NULL DEFAULT 'published',
            views INT UNSIGNED NOT NULL DEFAULT 0,
            author_id INT UNSIGNED NOT NULL DEFAULT 1,
            published_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS messages (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            email VARCHAR(190) NOT NULL,
            subject VARCHAR(190) NOT NULL DEFAULT '',
            body TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS settings (
            name VARCHAR(60) PRIMARY KEY,
            value TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS media (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            file_path VARCHAR(255) NOT NULL,
            file_name VARCHAR(190) NOT NULL,
            mime VARCHAR(100) NOT NULL DEFAULT '',
            size_kb INT UNSIGNED NOT NULL DEFAULT 0,
            uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];
}

function seed_data(): void
{
    if ((int) scalar('SELECT COUNT(*) FROM users', [], 0) === 0) {
        q(
            'INSERT INTO users (username, email, password_hash, full_name) VALUES (:u, :e, :p, :n)',
            [
                ':u' => DEFAULT_ADMIN_USER,
                ':e' => 'hello@charlesdamilola.com',
                ':p' => password_hash(DEFAULT_ADMIN_PASS, PASSWORD_DEFAULT),
                ':n' => 'Charles Odeye Damilola',
            ]
        );
    }

    if ((int) scalar('SELECT COUNT(*) FROM settings', [], 0) === 0) {
        foreach (default_settings() as $k => $v) {
            q('INSERT INTO settings (name, value) VALUES (:n, :v)', [':n' => $k, ':v' => $v]);
        }
    }

    if ((int) scalar('SELECT COUNT(*) FROM works', [], 0) === 0) {
        foreach (demo_works() as $w) {
            insert('works', $w);
        }
    }

    if ((int) scalar('SELECT COUNT(*) FROM posts', [], 0) === 0) {
        foreach (demo_posts() as $p) {
            insert('posts', $p);
        }
    }
}

function default_settings(): array
{
    return [
        'site_title'       => 'Charles Odeye Damilola',
        'tagline'          => 'Content • Radiography • Video • Maps',
        'hero_subtitle'    => 'I build audiences with scroll-stopping content, capture the human body with precision as a radiographer, edit video that tells stories, and design maps that make data beautiful.',
        'bio'              => 'I am Charles Odeye Damilola — a multi-disciplinary creative and healthcare professional based in Nigeria. As a jujutsu content and social media executive I plan, shoot and grow communities online; as a radiographer I produce diagnostic images that help doctors save lives; as a video editor I turn raw footage into stories; and as a geographic designer I turn data into maps people actually enjoy reading.',
        'phone'            => '+234 800 000 0000',
        'email'            => 'hello@charlesdamilola.com',
        'location'         => 'Lagos, Nigeria',
        'whatsapp'         => '2348000000000',
        'instagram'        => '#',
        'tiktok'           => '#',
        'twitter'          => '#',
        'youtube'          => '#',
        'linkedin'         => '#',
        'behance'          => '#',
        'github'           => '#',
        'cv_url'           => '',
        'profile_photo'    => 'assets/img/avatar.svg',
        'hero_image'       => 'assets/img/hero.svg',
        'footer_note'      => '© ' . date('Y') . ' Charles Odeye Damilola. All rights reserved.',
        'availability'     => 'Available for freelance & full-time opportunities',
    ];
}

function demo_works(): array
{
    return [
        [
            'title' => 'Jujutsu Kaisen Fan Edit Series', 'slug' => 'jujutsu-kaisen-fan-edit-series',
            'category' => 'jujutsu', 'client' => 'Personal / Anime Community', 'tools' => 'CapCut, Premiere Pro, After Effects',
            'year' => '2026', 'description' => 'A viral series of jujutsu-themed short edits built around cursed-energy transitions, timing-sync fight cuts and manga panel reveals. Grew the page from 0 to a loyal anime audience with consistent weekly drops and engagement-driven captions.',
            'image' => 'assets/img/placeholders/work-jujutsu.svg', 'video_url' => '', 'external_url' => '',
            'status' => 'published', 'is_featured' => 1, 'views' => 128,
        ],
        [
            'title' => 'Social Media Growth Campaign', 'slug' => 'social-media-growth-campaign',
            'category' => 'content-social', 'client' => 'Lifestyle Brand', 'tools' => 'Meta Business Suite, Canva, CapCut, Notion',
            'year' => '2025', 'description' => 'End-to-end social media management: content calendar, reels production, community management and monthly analytics reporting. Delivered a consistent 3-post-per-day rhythm and a measurable lift in reach and saves.',
            'image' => 'assets/img/placeholders/work-social.svg', 'video_url' => '', 'external_url' => '',
            'status' => 'published', 'is_featured' => 1, 'views' => 96,
        ],
        [
            'title' => 'Chest Radiograph Positioning Guide', 'slug' => 'chest-radiograph-positioning-guide',
            'category' => 'radiography', 'client' => 'Teaching Hospital Rotation', 'tools' => 'Radiography Suite, DICOM, PowerPoint',
            'year' => '2025', 'description' => 'A visual positioning and exposure guide for radiography students covering PA, lateral and AP axial views, artefact avoidance and radiation protection principles for both patients and staff.',
            'image' => 'assets/img/placeholders/work-radiography.svg', 'video_url' => '', 'external_url' => '',
            'status' => 'published', 'is_featured' => 1, 'views' => 74,
        ],
        [
            'title' => 'Documentary Style Brand Film', 'slug' => 'documentary-style-brand-film',
            'category' => 'video', 'client' => 'Healthcare Startup', 'tools' => 'DaVinci Resolve, Premiere Pro, Artlist',
            'year' => '2025', 'description' => 'A two-minute documentary style brand film with colour grading, sound design, lower-thirds and motion titles — cut for YouTube, LinkedIn and a conference keynote.',
            'image' => 'assets/img/placeholders/work-video.svg', 'video_url' => '', 'external_url' => '',
            'status' => 'published', 'is_featured' => 0, 'views' => 61,
        ],
        [
            'title' => 'Lagos Accessibility Map', 'slug' => 'lagos-accessibility-map',
            'category' => 'geographic', 'client' => 'Civic Data Project', 'tools' => 'QGIS, ArcGIS, Illustrator, Figma',
            'year' => '2024', 'description' => 'An interactive-style thematic map visualising transport accessibility across Lagos neighbourhoods, combining open street data with field surveys into a clean editorial infographic.',
            'image' => 'assets/img/placeholders/work-geo.svg', 'video_url' => '', 'external_url' => '',
            'status' => 'published', 'is_featured' => 0, 'views' => 52,
        ],
        [
            'title' => 'Anatomy Explainer Reel Series', 'slug' => 'anatomy-explainer-reel-series',
            'category' => 'video', 'client' => 'Medical Education Page', 'tools' => 'After Effects, Photoshop, CapCut',
            'year' => '2024', 'description' => 'Short-form educational reels breaking down anatomy and imaging concepts into 45-second explainers with clean motion graphics and captions for sound-off viewing.',
            'image' => 'assets/img/placeholders/work-reels.svg', 'video_url' => '', 'external_url' => '',
            'status' => 'published', 'is_featured' => 0, 'views' => 88,
        ],
    ];
}

function demo_posts(): array
{
    return [
        [
            'title' => 'How I Plan a Week of Content in 90 Minutes',
            'slug' => 'how-i-plan-a-week-of-content-in-90-minutes',
            'excerpt' => 'The exact batching system I use as a social media executive: research, hooks, shot list, captions and scheduling — in one sitting.',
            'body' => '<p>Planning content is where most pages fail. Not on the shoot day — on the Monday morning when there is no idea left.</p><h2>The 90-minute block</h2><p>I block one focused session a week and move through four steps: <strong>research</strong> (what performed and what the audience asked for), <strong>hooks</strong> (ten written first-lines for each theme), <strong>shot list</strong> (every clip and still I need), and <strong>scheduling</strong> (loaded straight into the scheduler with captions and hashtags).</p><h2>Why batching beats inspiration</h2><p>Inspiration is unreliable on a hospital rotation or a shoot week. A batch guarantees the calendar never goes blank, and it lets one idea become a reel, a carousel and a thread instead of three separate struggles.</p><ul><li>One theme becomes three formats</li><li>Captions are written once, in the same voice</li><li>Scheduling frees the actual posting days for engagement</li></ul><p>Consistency is a system, not a personality trait.</p>',
            'cover_image' => 'assets/img/placeholders/post-content.svg', 'category' => 'Social Media', 'status' => 'published',
            'views' => 142,
        ],
        [
            'title' => 'Radiography and Creativity: Two Sides of the Same Career',
            'slug' => 'radiography-and-creativity-two-sides',
            'excerpt' => 'People ask how a radiographer also edits video and designs maps. The answer is that both careers are about seeing clearly.',
            'body' => '<p>On the surface, radiography and creative work look like opposite worlds. One is protocol, exposure factors and radiation protection. The other is colour, timing and audience emotion.</p><h2>Image quality is a creative skill</h2><p>A well-positioned chest radiograph has the same requirements as a well-composed frame: centring, contrast, no artefacts, and the diagnostic story visible at a glance. Training my eye on the light box made me stricter about every cut in the edit suite.</p><h2>Patient care is storytelling</h2><p>Explaining a procedure so a nervous patient understands is the same skill as writing a caption that makes someone stop scrolling — clarity, empathy and the right words in the right order.</p><p>Both careers taught me that precision and creativity are not opposites. They are the same discipline applied to different audiences.</p>',
            'cover_image' => 'assets/img/placeholders/post-radiography.svg', 'category' => 'Radiography', 'status' => 'published',
            'views' => 97,
        ],
        [
            'title' => '5 Editing Transitions That Actually Work on Reels',
            'slug' => '5-editing-transitions-that-work-on-reels',
            'excerpt' => 'Skip the flashy preset. These are the transitions that keep retention high — and exactly when to use each one.',
            'body' => '<p>Transitions are not decoration. Used well they hide cuts, carry motion and keep the viewer\'s thumb still. Used badly they cost you retention in the first three seconds.</p><h2>1. The match cut</h2><p>Match a shape or movement between shots. It reads as intentional and keeps the eye travelling forward.</p><h2>2. The whip pan</h2><p>Perfect for changing location fast. Shoot the whip in camera, then cut on the blur.</p><h2>3. The mask wipe</h2><p>Use a subject passing the lens as a natural wipe. Nothing feels forced.</p><h2>4. Speed ramp</h2><p>Slow on the detail, fast on the travel. It controls pacing without a single word of narration.</p><h2>5. Hard cut on the beat</h2><p>Still undefeated. When in doubt, cut on the beat and let the music do the work.</p><p>The rule: transitions serve the story, never the other way round.</p>',
            'cover_image' => 'assets/img/placeholders/post-video.svg', 'category' => 'Video Editing', 'status' => 'published',
            'views' => 118,
        ],
    ];
}
