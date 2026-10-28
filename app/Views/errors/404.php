<?php
/**
 * @var App\Core\View $this
 * @var string $title
 * @var string $message
 */
$this->layout('site', ['title' => $title]);
?>
<section class="mx-auto max-w-xl px-4 py-16 text-center lg:py-24">
    <p class="text-5xl font-bold text-primary">404</p>
    <h1 class="mt-4 text-2xl font-bold text-ink"><?= e($title) ?></h1>
    <p class="mt-2 text-muted"><?= e($message) ?></p>
    <a href="<?= e(url()) ?>" class="mt-8 inline-flex min-h-12 items-center rounded-full bg-primary px-6 font-bold text-white hover:bg-primary-dark">Kembali ke Beranda</a>
</section>
