<?php
/**
 * Layout panel admin. Halaman masuk memakai 'polos' => true (tanpa navigasi).
 *
 * @var App\Core\View $this
 * @var string|null   $title
 * @var bool|null     $polos
 */

use App\Core\Auth;
use App\Core\Session;
use App\Models\KonfirmasiPembayaran;

$user  = Auth::user();
$flash = Session::getFlash('alert');
$menu  = (array) config('admin.menu', []);
$path  = current_path();
$aktif = static fn (string $kunci): bool => $path === '/admin/' . $kunci || str_starts_with($path, '/admin/' . $kunci . '/');
$antrean = !empty($polos) ? 0 : KonfirmasiPembayaran::hitungBaru();
?>
<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e(($title ?? 'Admin') . ' — Admin BAZNAS Basel') ?></title>
    <link rel="icon" href="<?= e(url('favicon.ico')) ?>" sizes="32x32">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="min-h-dvh bg-surface text-ink antialiased">
    <a href="#konten" class="sr-only z-50 rounded-lg bg-primary px-4 py-3 font-semibold text-white focus:not-sr-only focus:fixed focus:top-2 focus:left-2">Lewati ke konten</a>

    <?php if (empty($polos)): ?>
    <header class="sticky top-0 z-30 border-b border-gray-200 bg-white">
        <div class="flex h-14 items-center gap-3 px-4">
            <a href="<?= e(url('admin')) ?>" class="flex items-center gap-2 font-bold text-primary">
                <img src="<?= e(asset('img/logo-garuda.webp')) ?>" alt="" width="81" height="96" class="h-8 w-auto">
                Admin BAZNAS Basel
            </a>
            <a href="<?= e(url()) ?>" target="_blank" rel="noopener" class="ml-auto hidden text-sm font-semibold text-muted hover:text-primary sm:inline">Lihat situs ↗</a>
            <span class="hidden text-sm text-muted md:inline"><?= e($user['nama'] ?? '') ?></span>
            <form method="post" action="<?= e(url('admin/keluar')) ?>" class="ml-auto sm:ml-0">
                <?= csrf_field() ?>
                <button type="submit" class="inline-flex h-10 items-center rounded-full border border-gray-300 px-4 text-sm font-semibold hover:border-primary hover:text-primary">Keluar</button>
            </form>
        </div>
    </header>

    <div class="lg:flex">
        <nav aria-label="Menu admin" class="border-b border-gray-200 bg-white lg:sticky lg:top-14 lg:h-[calc(100dvh-3.5rem)] lg:w-64 lg:shrink-0 lg:overflow-y-auto lg:border-r lg:border-b-0">
            <details class="group lg:hidden">
                <summary class="flex h-12 cursor-pointer items-center gap-2 px-4 font-semibold"><?= icon('menu', 'size-5') ?>Menu<?= icon('chevron-down', 'ml-auto size-4 transition-transform group-open:rotate-180') ?></summary>
                <?= $this->insert('admin/menu', ['menu' => $menu, 'aktif' => $aktif, 'antrean' => $antrean, 'path' => $path]) ?>
            </details>
            <div class="hidden lg:block">
                <?= $this->insert('admin/menu', ['menu' => $menu, 'aktif' => $aktif, 'antrean' => $antrean, 'path' => $path]) ?>
            </div>
        </nav>

        <main id="konten" class="min-w-0 flex-1 px-4 py-6 lg:px-8 lg:py-8" tabindex="-1">
            <?php if (is_array($flash) && isset($flash['pesan'])): ?>
            <div class="mb-6"><?= $this->insert('components/alert', ['jenis' => $flash['jenis'] ?? 'info', 'pesan' => $flash['pesan']]) ?></div>
            <?php endif ?>
            <?= $this->section('content') ?>
        </main>
    </div>
    <?php else: ?>
    <main id="konten" class="grid min-h-dvh place-items-center px-4 py-10" tabindex="-1">
        <div class="w-full max-w-sm">
            <?php if (is_array($flash) && isset($flash['pesan'])): ?>
            <div class="mb-4"><?= $this->insert('components/alert', ['jenis' => $flash['jenis'] ?? 'info', 'pesan' => $flash['pesan']]) ?></div>
            <?php endif ?>
            <?= $this->section('content') ?>
        </div>
    </main>
    <?php endif ?>

    <div id="pengumuman" class="sr-only" aria-live="polite"></div>
    <script type="module" src="<?= e(asset('js/admin.js')) ?>"></script>
</body>
</html>
