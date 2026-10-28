<?php
/**
 * @var App\Core\View $this
 * @var int $status
 * @var string $title
 * @var string $message
 * @var Throwable|null $exception hanya terisi bila APP_DEBUG=true
 */
$this->layout('site', ['title' => $title]);
?>
<section class="mx-auto max-w-xl px-4 py-16 text-center lg:py-24">
    <p class="text-5xl font-bold text-primary"><?= e($status) ?></p>
    <h1 class="mt-4 text-2xl font-bold text-ink"><?= e($title) ?></h1>
    <p class="mt-2 text-muted"><?= e($message) ?></p>
    <a href="<?= e(url()) ?>" class="mt-8 inline-flex min-h-12 items-center rounded-full bg-primary px-6 font-bold text-white hover:bg-primary-dark">Kembali ke Beranda</a>
</section>

<?php if ($exception !== null): ?>
<section class="mx-auto max-w-6xl px-4 pb-16">
    <div class="overflow-x-auto rounded-2xl bg-surface p-4 text-left text-sm">
        <p class="font-bold text-red-700"><?= e($exception::class) ?>: <?= e($exception->getMessage()) ?></p>
        <p class="mt-1 text-muted"><?= e($exception->getFile()) ?>:<?= e($exception->getLine()) ?></p>
        <pre class="mt-4 whitespace-pre-wrap text-xs"><?= e($exception->getTraceAsString()) ?></pre>
    </div>
</section>
<?php endif ?>
