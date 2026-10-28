<?php
/**
 * Layout situs publik (mobile-first): header sticky, bottom nav + menu sheet (< lg), footer.
 *
 * Data dari template lewat $this->layout('site', [...]):
 * @var App\Core\View $this
 * @var string|null   $title       judul halaman (tanpa nama situs); kosong = beranda
 * @var string|null   $description ≤ 160 karakter; default tagline
 * @var string|null   $image       URL absolut gambar OG (1200×630)
 * @var string|null   $type        website | article
 * @var string|null   $canonical   default: URL path aktif tanpa query
 * @var list<array>|null $jsonLd
 * @var bool|null     $noindex
 */

use App\Core\Session;

$namaSitus = setting('site_name', (string) config('app.name'));
$judul     = !empty($title) ? $title . ' — BAZNAS Basel' : $namaSitus;
$flash     = Session::getFlash('alert');
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<?= $this->insert('partials/seo-meta', [
    'title'       => $judul,
    'description' => $description ?? setting('tagline', $namaSitus),
    'canonical'   => $canonical ?? url(current_path()),
    'image'       => $image ?? null,
    'type'        => $type ?? 'website',
    'noindex'     => (bool) ($noindex ?? false),
    'jsonLd'      => $jsonLd ?? [],
]) ?>
    <meta name="theme-color" content="#0b6b3a">
    <link rel="icon" href="<?= e(url('favicon.ico')) ?>" sizes="32x32">
    <link rel="apple-touch-icon" href="<?= e(url('apple-touch-icon.png')) ?>">
    <link rel="manifest" href="<?= e(url('manifest.webmanifest')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <?= $this->section('head') ?>
</head>
<body class="flex min-h-dvh flex-col bg-white text-ink antialiased pb-[calc(4rem+env(safe-area-inset-bottom))] lg:pb-0">
    <a href="#konten" class="sr-only z-50 rounded-lg bg-primary px-4 py-3 font-semibold text-white focus:not-sr-only focus:fixed focus:top-2 focus:left-2">Lewati ke konten</a>

    <?= $this->insert('partials/header') ?>

    <main id="konten" class="flex-1" tabindex="-1">
        <?php if (is_array($flash) && isset($flash['pesan'])): ?>
        <div class="mx-auto max-w-6xl px-4 pt-4">
            <?= $this->insert('components/alert', ['jenis' => $flash['jenis'] ?? 'info', 'pesan' => $flash['pesan']]) ?>
        </div>
        <?php endif ?>
        <?= $this->section('content') ?>
    </main>

    <?= $this->insert('partials/footer') ?>
    <?= $this->insert('partials/bottom-nav') ?>
    <?= $this->insert('partials/menu-sheet') ?>
    <div id="pengumuman" class="sr-only" aria-live="polite"></div>

    <script type="module" src="<?= e(asset('js/app.js')) ?>"></script>
    <?= $this->section('scripts') ?>
</body>
</html>
