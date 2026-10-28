<?php
/**
 * Meta SEO & sosial. Dipanggil dari layout.
 *
 * @var string      $title       judul lengkap (sudah termasuk nama situs)
 * @var string      $description ≤ 160 karakter
 * @var string      $canonical   URL absolut
 * @var string|null $image       URL absolut gambar 1200×630
 * @var string      $type        website | article
 * @var bool        $noindex
 * @var list<array> $jsonLd      skema JSON-LD tambahan
 */
?>
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($description) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
<?php if ($noindex): ?>
    <meta name="robots" content="noindex, nofollow">
<?php endif ?>
    <meta property="og:locale" content="id_ID">
    <meta property="og:site_name" content="<?= e(setting('site_name', (string) config('app.name'))) ?>">
    <meta property="og:type" content="<?= e($type) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($description) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
<?php if ($image): ?>
    <meta property="og:image" content="<?= e($image) ?>">
    <meta name="twitter:card" content="summary_large_image">
<?php else: ?>
    <meta name="twitter:card" content="summary">
<?php endif ?>
<?php foreach ($jsonLd as $schema): ?>
    <script type="application/ld+json"><?= json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endforeach ?>
