<?php
// ============================================================
//  SL JetSpot — Public Homepage
// ============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// --- Log visitor ---
try {
    DB::query('INSERT INTO visitors (ip, page) VALUES (?, ?)', [
        $_SERVER['REMOTE_ADDR'] ?? '', '/'
    ]);
} catch (Exception $e) { /* silent */ }

// --- Fetch aircraft ---
$aircraft = DB::query(
    'SELECT * FROM aircraft WHERE is_published = 1 ORDER BY sort_order ASC, created_at DESC'
)->fetchAll();

// Load More only kicks in when you have more than 12 photos
define('PER_PAGE', 12);

// --- Build dynamic category counts ---
$categoryCounts = [];
foreach ($aircraft as $a) {
    $cat = $a['category'];
    $categoryCounts[$cat] = ($categoryCounts[$cat] ?? 0) + 1;
}
// Airbus/Boeing always first, then remaining categories alphabetically
$priorityOrder = ['airbus', 'boeing'];
$otherCats     = array_diff(array_keys($categoryCounts), $priorityOrder);
sort($otherCats);
$orderedCats   = array_merge(
    array_filter($priorityOrder, fn($c) => isset($categoryCounts[$c])),
    $otherCats
);

// --- Stats ---
$stats = DB::query(
    'SELECT
        COUNT(*) AS total,
        COUNT(DISTINCT airline) AS airlines,
        COUNT(DISTINCT location) AS airports
     FROM aircraft WHERE is_published = 1'
)->fetch();

// --- Unread messages count (for admin badge) ---
$unread = 0;
if (Auth::check()) {
    $unread = (int) DB::query('SELECT COUNT(*) FROM messages WHERE is_read = 0')->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- ===== PRIMARY SEO ===== -->
    <title>Sri Lankan JetSpot — Planespotting &amp; Aviation Photography at VCBI &amp; VCCC</title>
    <meta name="description" content="Sri Lankan JetSpot is a planespotting and aviation photography site by Swen Jayathunga, featuring Airbus, Boeing and rare aircraft spotted at Bandaranaike International Airport (VCBI) and Ratmalana Airport (VCCC), Sri Lanka.">
    <meta name="keywords" content="Sri Lankan JetSpot, SL JetSpot, planespotting Sri Lanka, aviation photography Sri Lanka, VCBI planespotting, VCCC spotting, Bandaranaike airport spotting, Swen Jayathunga, aircraft spotting Colombo, SriLankan Airlines spotting">
    <meta name="author" content="Swen Jayathunga">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?= SITE_URL ?>/">

    <!-- ===== OPEN GRAPH ===== -->
    <meta property="og:type"         content="website">
    <meta property="og:site_name"    content="Sri Lankan JetSpot">
    <meta property="og:title"        content="Sri Lankan JetSpot — Planespotting &amp; Aviation Photography">
    <meta property="og:description"  content="Aviation photography from VCBI &amp; VCCC airports in Sri Lanka. Airbus, Boeing, military and special livery aircraft spotted by Swen Jayathunga.">
    <meta property="og:url"          content="<?= SITE_URL ?>/">
    <meta property="og:image"        content="<?= SITE_URL ?>/assets/img/preview.jpg">
    <meta property="og:image:width"  content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale"       content="en_US">

    <!-- ===== TWITTER / X CARD ===== -->
    <meta name="twitter:card"        content="summary_large_image">
    <meta name="twitter:title"       content="Sri Lankan JetSpot — Planespotting Photography">
    <meta name="twitter:description" content="Aviation photography from VCBI &amp; VCCC airports in Sri Lanka.">
    <meta name="twitter:image"       content="<?= SITE_URL ?>/assets/img/preview.jpg">

    <!-- ===== SCHEMA.ORG ===== -->
    <script type="application/ld+json">
    [
      {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Sri Lankan JetSpot",
        "alternateName": "SL JetSpot",
        "url": "<?= SITE_URL ?>/",
        "description": "Planespotting and aviation photography at VCBI and VCCC airports in Sri Lanka by Swen Jayathunga.",
        "inLanguage": "en",
        "author": {
          "@type": "Person",
          "name": "Swen Jayathunga",
          "url": "<?= SITE_URL ?>/",
          "sameAs": [
            "https://www.instagram.com/srilankan_jetspot/",
            "https://www.instagram.com/swen_av_spotter/",
            "https://www.instagram.com/swen.pvt/",
            "https://www.tiktok.com/@srilankan_jetspot",
            "https://www.tiktok.com/@swen_jayathunga",
            "https://web.facebook.com/profile.php?id=100091482715439"
          ]
        },
        "potentialAction": {
          "@type": "SearchAction",
          "target": "<?= SITE_URL ?>/#gallery",
          "query-input": "required name=search_term_string"
        }
      },
      {
        "@context": "https://schema.org",
        "@type": "ImageGallery",
        "name": "Sri Lankan JetSpot Aviation Photography Gallery",
        "description": "A curated collection of planespotting photos taken at Bandaranaike International Airport (VCBI) and Ratmalana Airport (VCCC), Sri Lanka.",
        "url": "<?= SITE_URL ?>/#gallery",
        "author": { "@type": "Person", "name": "Swen Jayathunga" },
        "locationCreated": {
          "@type": "Place",
          "name": "Bandaranaike International Airport (VCBI)",
          "address": {
            "@type": "PostalAddress",
            "addressCountry": "LK",
            "addressLocality": "Colombo",
            "addressRegion": "Western Province"
          }
        },
        "numberOfItems": <?= count($aircraft) ?>
      },
      {
        "@context": "https://schema.org",
        "@type": "BreadcrumbList",
        "itemListElement": [
          { "@type": "ListItem", "position": 1, "name": "Home",    "item": "<?= SITE_URL ?>/" },
          { "@type": "ListItem", "position": 2, "name": "Gallery", "item": "<?= SITE_URL ?>/#gallery" },
          { "@type": "ListItem", "position": 3, "name": "Gear",    "item": "<?= SITE_URL ?>/#gear" }
        ]
      }
    ]
    </script>

    <!-- ===== FAVICONS ===== -->
    <link rel="icon"          type="image/png" href="assets/img/favicon.png">
    <link rel="apple-touch-icon"               href="assets/img/favicon.png">

    <!-- ===== FONTS & STYLES ===== -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;0,400;0,600;0,700;1,300;1,400;1,600&family=DM+Sans:wght@300;400;500;600&family=Cinzel:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- HEADER -->
<header id="header">
    <div class="header-inner">
        <a href="/" class="logo">
            <span class="logo-icon"><i class="fas fa-plane-departure"></i></span>
            <span class="logo-text">SL <strong>JetSpot</strong></span>
        </a>
        <nav>
            <ul class="nav-links" id="navLinks">
                <li><a href="#hero"    class="nav-link active">Home</a></li>
                <li><a href="#gallery" class="nav-link">Gallery</a></li>
                <li><a href="#gear"    class="nav-link">Gear</a></li>
                <li><a href="#contact" class="nav-link">Contact</a></li>
                <?php if (Auth::check()): ?>
                <li>
                    <a href="<?= BASE_URL ?>/admin/" class="nav-link admin-link">
                        <i class="fas fa-cog"></i> Admin
                        <?php if ($unread > 0): ?>
                        <span class="badge"><?= $unread ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <button class="hamburger" id="hamburger" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
        </nav>
    </div>
</header>

<!-- HERO -->
<section class="hero" id="hero">
    <div class="hero-bg"><div class="hero-runway"></div></div>
    <div class="hero-content">
        <p class="hero-label">Sri Lanka · VCBI · VCCC</p>
        <h1 class="hero-title">Capturing<br><em>The Skies</em></h1>
        <p class="hero-sub">Professional aviation photography documenting the beauty of flight over the Pearl of the Indian Ocean.</p>
        <div class="hero-cta">
            <a href="#gallery" class="btn btn-primary">View Gallery</a>
            <a href="#gear"    class="btn btn-outline">Our Gear</a>
        </div>
        <div class="hero-stats">
            <div class="stat">
                <span class="stat-num"><?= $stats['total'] ?>+</span>
                <span class="stat-label">Aircraft Spotted</span>
            </div>
            <div class="stat">
                <span class="stat-num"><?= $stats['airports'] ?></span>
                <span class="stat-label">Airports</span>
            </div>
            <div class="stat">
                <span class="stat-num"><?= $stats['airlines'] ?>+</span>
                <span class="stat-label">Airlines</span>
            </div>
        </div>
    </div>
    <div class="hero-scroll"><span>Scroll</span><div class="scroll-line"></div></div>
</section>

<!-- CAROUSEL — first 5 published images -->
<section class="carousel-section">
    <div class="carousel-wrapper">
        <div class="carousel" id="carousel">
            <?php foreach (array_slice($aircraft, 0, 5) as $i => $ac): ?>
            <div class="carousel-slide <?= $i === 0 ? 'active' : '' ?>">
                <img src="<?= e($ac['image_path']) ?>"
                     alt="<?= e($ac['airline']) ?>"
                     loading="<?= $i === 0 ? 'eager' : 'lazy' ?>"
                     draggable="false" oncontextmenu="return false;">
            </div>
            <?php endforeach; ?>
        </div>
        <button class="carousel-btn prev" id="carouselPrev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
        <button class="carousel-btn next" id="carouselNext" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
        <div class="carousel-dots" id="carouselDots"></div>
    </div>
</section>

<!-- GALLERY -->
<section class="gallery-section" id="gallery">
    <div class="section-header">
        <p class="section-label">Photography</p>
        <h2 class="section-title">Gallery</h2>
        <p class="section-desc">A curated collection of aircraft spotted at Bandaranaike International (VCBI) and Ratmalana Airport (VCCC).</p>
    </div>

    <!-- Dynamic filter bar — shows all categories actually in the database -->
    <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">All (<?= count($aircraft) ?>)</button>
        <?php foreach ($orderedCats as $cat): ?>
        <button class="filter-btn" data-filter="<?= e($cat) ?>">
            <?= e(ucfirst($cat)) ?> (<?= $categoryCounts[$cat] ?>)
        </button>
        <?php endforeach; ?>
    </div>

    <div class="gallery-grid" id="galleryGrid">
        <?php foreach ($aircraft as $i => $ac): ?>
        <div class="gallery-card <?= $i >= PER_PAGE ? 'load-more-hidden' : '' ?>"
             data-type="<?= e($ac['category']) ?>"
             data-index="<?= $i ?>">
            <div class="card-img-wrap">
                <img src="<?= e($ac['thumb_path'] ?: $ac['image_path']) ?>"
                     data-full="<?= e($ac['image_path']) ?>"
                     alt="<?= e($ac['airline'] . ' ' . $ac['aircraft_type']) ?>"
                     loading="lazy"
                     draggable="false" oncontextmenu="return false;">
                <div class="card-overlay">
                    <button class="zoom-btn" data-index="<?= $i ?>" aria-label="View full size">
                        <i class="fas fa-expand"></i>
                    </button>
                </div>
            </div>
            <div class="card-info">
                <div class="card-header">
                    <span class="airline-badge <?= in_array($ac['registration'], ['4R-ALM','4R-ABT','4R-ALO','SUH 522','SCL-859']) ? 'airline-home' : '' ?>">
                        <?= e($ac['airline']) ?>
                    </span>
                    <span class="aircraft-type"><?= e($ac['variant'] ?: $ac['aircraft_type']) ?></span>
                </div>
                <table class="spec-table">
                    <tr><td>Registration</td><td><?= e($ac['registration']) ?></td></tr>
                    <tr><td>Country</td><td><?= e($ac['country_flag'] . ' ' . $ac['country']) ?></td></tr>
                    <tr><td>Date</td><td><?= date('j M Y', strtotime($ac['date_captured'])) ?></td></tr>
                    <tr><td>Location</td><td><?= e($ac['location']) ?></td></tr>
                    <tr><td>Camera</td><td><?= e($ac['camera']) ?><?= $ac['lens'] ? ' · ' . e($ac['lens']) : '' ?></td></tr>
                    <?php if ($ac['notes']): ?>
                    <tr><td>Notes</td><td><?= e($ac['notes']) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (count($aircraft) > PER_PAGE): ?>
    <div class="load-more-wrap" id="loadMoreWrap">
        <button class="load-more-btn" id="loadMoreBtn"
                data-loaded="<?= PER_PAGE ?>"
                data-per-page="<?= PER_PAGE ?>"
                data-total="<?= count($aircraft) ?>">
            <i class="fas fa-chevron-down"></i>
            Load More
            <span class="load-more-count">Showing <?= PER_PAGE ?> of <?= count($aircraft) ?></span>
        </button>
    </div>
    <?php endif; ?>
</section>

<!-- LIGHTBOX -->
<div class="lightbox" id="lightbox">
    <div class="lightbox-backdrop" id="lightboxBackdrop"></div>
    <button class="lightbox-close" id="lightboxClose" aria-label="Close"><i class="fas fa-times"></i></button>
    <button class="lightbox-nav prev" id="lightboxPrev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>
    <button class="lightbox-nav next" id="lightboxNext" aria-label="Next"><i class="fas fa-chevron-right"></i></button>
    <div class="lightbox-content">
        <img src="" alt="" id="lightboxImg">
        <div class="lightbox-caption" id="lightboxCaption"></div>
    </div>
</div>

<!-- GEAR -->
<section class="gear-section" id="gear">
    <div class="section-header">
        <p class="section-label">Equipment</p>
        <h2 class="section-title">Camera Gear</h2>
        <p class="section-desc">The tools behind every shot — precision equipment for aviation photography.</p>
    </div>
    <div class="gear-grid">
        <div class="gear-card">
            <div class="gear-img-wrap"><img src="assets/img/canon.jpg" alt="Canon EOS 4000D" loading="lazy"></div>
            <div class="gear-info">
                <div class="gear-icon"><i class="fas fa-camera"></i></div>
                <h3>Canon EOS 4000D</h3>
                <p>Our primary workhorse. Paired with a 250mm telephoto lens it delivers sharp, detailed shots even at long distances. Every livery detail captured with clarity.</p>
                <div class="gear-specs"><span>18MP Sensor</span><span>250mm Lens</span><span>5184×3456px</span></div>
            </div>
        </div>
        <div class="gear-card">
            <div class="gear-img-wrap"><img src="assets/img/iphone15.jpg" alt="iPhone 15 Pro Max" loading="lazy"></div>
            <div class="gear-info">
                <div class="gear-icon"><i class="fas fa-mobile-alt"></i></div>
                <h3>iPhone 15 Pro Max</h3>
                <p>Our secondary camera for flexibility and cinematic video. 48MP main sensor, ProRAW, 4K 60fps with 3× optical zoom and Cinematic mode.</p>
                <div class="gear-specs"><span>48MP Main</span><span>3× Optical Zoom</span><span>4K 60fps</span></div>
            </div>
        </div>
        <div class="gear-card">
            <div class="gear-img-wrap"><img src="assets/img/iphonexs.jpg" alt="iPhone XS Max" loading="lazy"></div>
            <div class="gear-info">
                <div class="gear-icon"><i class="fas fa-mobile-alt"></i></div>
                <h3>iPhone XS Max</h3>
                <p>Backup mobile device for quick captures. Dual 12MP system with Portrait mode and Smart HDR. Reliable for spontaneous shots when primary gear isn't available.</p>
                <div class="gear-specs"><span>12MP Dual</span><span>2× Optical Zoom</span><span>4K 60fps</span></div>
            </div>
        </div>
    </div>
</section>

<!-- CONTACT -->
<section class="contact-section" id="contact">
    <div class="section-header">
        <p class="section-label">Get In Touch</p>
        <h2 class="section-title">Contact</h2>
        <p class="section-desc">Questions, collaborations, or just love talking aviation? Drop a message below.</p>
    </div>
    <div class="contact-wrap">
        <div class="contact-info">
            <div class="contact-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h4>Based at</h4>
                    <p>VCBI — Bandaranaike International Airport<br>VCCC — Ratmalana Airport</p>
                </div>
            </div>
            <div class="contact-item">
                <i class="fab fa-instagram"></i>
                <div>
                    <h4>Instagram</h4>
                    <p><a href="https://www.instagram.com/srilankan_jetspot/" target="_blank" rel="noopener">@srilankan_jetspot</a></p>
                    <p><a href="https://www.instagram.com/swen_av_spotter/" target="_blank" rel="noopener">@swen_av_spotter</a></p>
                    <p><a href="https://www.instagram.com/swen.pvt/" target="_blank" rel="noopener">@swen.pvt</a></p>
                </div>
            </div>
            <div class="contact-item">
                <i class="fab fa-tiktok"></i>
                <div>
                    <h4>TikTok</h4>
                    <p><a href="https://www.tiktok.com/@srilankan_jetspot" target="_blank" rel="noopener">@srilankan_jetspot</a></p>
                    <p><a href="https://www.tiktok.com/@swen_jayathunga" target="_blank" rel="noopener">@swen_jayathunga</a></p>
                </div>
            </div>
        </div>

        <form class="contact-form" id="contactForm" novalidate>
            <?= csrf_field() ?>
            <div id="formMessage" class="form-message" style="display:none"></div>
            <div class="form-row">
                <div class="form-group">
                    <label for="cf_name">Your Name</label>
                    <input type="text" id="cf_name" name="name" placeholder="Swen Jayathunga" required maxlength="120">
                </div>
                <div class="form-group">
                    <label for="cf_email">Email Address</label>
                    <input type="email" id="cf_email" name="email" placeholder="you@example.com" required maxlength="180">
                </div>
            </div>
            <div class="form-group">
                <label for="cf_subject">Subject</label>
                <input type="text" id="cf_subject" name="subject" placeholder="Collaboration / Question / Spotting tip..." required maxlength="200">
            </div>
            <div class="form-group">
                <label for="cf_message">Message</label>
                <textarea id="cf_message" name="message" placeholder="Write your message here..." rows="6" required maxlength="3000"></textarea>
            </div>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <i class="fas fa-paper-plane"></i> Send Message
            </button>
        </form>
    </div>
</section>

<!-- FOOTER -->
<footer>
    <div class="footer-inner">
        <div class="footer-brand">
            <div class="logo">
                <span class="logo-icon"><i class="fas fa-plane-departure"></i></span>
                <span class="logo-text">SL <strong>JetSpot</strong></span>
            </div>
            <p>Aviation photography from the Pearl of the Indian Ocean. Based at VCBI &amp; VCCC, Sri Lanka.</p>
        </div>
        <div class="footer-links">
            <h4>Navigation</h4>
            <ul>
                <li><a href="#hero">Home</a></li>
                <li><a href="#gallery">Gallery</a></li>
                <li><a href="#gear">Gear</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </div>
        <div class="footer-social">
            <h4>Reach Me</h4>
            <div class="social-links-list">
                <a href="https://www.instagram.com/swen.pvt/" target="_blank" rel="noopener"><i class="fab fa-instagram"></i> @swen.pvt</a>
            </div>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> Sri Lankan JetSpot &mdash; Made with <i class="fas fa-heart"></i> by <strong>Swen Jayathunga</strong></p>
        <a href="#hero" class="back-top" aria-label="Back to top"><i class="fas fa-arrow-up"></i></a>
    </div>
</footer>

<script src="assets/js/script.js"></script>
</body>
</html>