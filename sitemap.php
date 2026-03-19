<?php
// ============================================================
//  SL JetSpot — Dynamic Sitemap  /sitemap.php
//  Submit this URL to Google Search Console:
//  https://yourdomain.com/sitemap.php
// ============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/xml; charset=UTF-8');

$lastUpdate = DB::query(
    'SELECT MAX(updated_at) AS last FROM aircraft WHERE is_published = 1'
)->fetchColumn();

$lastmod = $lastUpdate ? date('Y-m-d', strtotime($lastUpdate)) : date('Y-m-d');

$aircraft = DB::query(
    'SELECT * FROM aircraft WHERE is_published = 1 ORDER BY sort_order ASC, created_at DESC'
)->fetchAll();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

    <!-- Homepage -->
    <url>
        <loc><?= SITE_URL ?>/</loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>1.0</priority>
        <?php foreach (array_slice($aircraft, 0, 5) as $ac):
            $src = $ac['image_path'] ? SITE_URL . '/' . htmlspecialchars($ac['image_path'], ENT_XML1) : '';
            if (!$src) continue;
            $title = htmlspecialchars($ac['airline'] . ' ' . ($ac['variant'] ?: $ac['aircraft_type']) . ' ' . $ac['registration'], ENT_XML1);
        ?>
        <image:image>
            <image:loc><?= $src ?></image:loc>
            <image:title><?= $title ?></image:title>
            <image:caption><?= htmlspecialchars('Planespotting photo of ' . $ac['airline'] . ' captured at ' . $ac['location'] . ', Sri Lanka by Swen Jayathunga.', ENT_XML1) ?></image:caption>
        </image:image>
        <?php endforeach; ?>
    </url>

    <?php foreach ($aircraft as $ac):
        $imgSrc = $ac['image_path'] ? SITE_URL . '/' . htmlspecialchars($ac['image_path'], ENT_XML1) : '';
        $title  = htmlspecialchars(
            $ac['airline'] . ' ' . ($ac['variant'] ?: $ac['aircraft_type'])
            . ' ' . $ac['registration'] . ' at ' . $ac['location'] . ' Sri Lanka',
            ENT_XML1
        );
        $caption = htmlspecialchars(
            'Planespotting photo: ' . $ac['airline'] . ' ' . ($ac['variant'] ?: $ac['aircraft_type'])
            . ' (Reg: ' . $ac['registration'] . ') spotted at ' . $ac['location']
            . ' on ' . date('j F Y', strtotime($ac['date_captured']))
            . '. Photography by Swen Jayathunga — Sri Lankan JetSpot.',
            ENT_XML1
        );
    ?>
    <url>
        <loc><?= SITE_URL ?>/?aircraft=<?= (int)$ac['id'] ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($ac['updated_at'] ?: $ac['created_at'])) ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.7</priority>
        <?php if ($imgSrc): ?>
        <image:image>
            <image:loc><?= $imgSrc ?></image:loc>
            <image:title><?= $title ?></image:title>
            <image:caption><?= $caption ?></image:caption>
        </image:image>
        <?php endif; ?>
    </url>
    <?php endforeach; ?>

</urlset>