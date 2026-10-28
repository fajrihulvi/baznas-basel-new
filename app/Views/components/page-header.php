<?php
/**
 * Kepala halaman dalam: breadcrumb + h1 + subjudul, latar surface.
 *
 * @var App\Core\View $this
 * @var string      $judul
 * @var string|null $subjudul
 * @var list<array{label: string, url?: ?string}> $breadcrumb  tanpa "Beranda" (ditambah otomatis)
 */
?>
<div class="border-b border-gray-200 bg-surface">
    <div class="mx-auto max-w-6xl px-4 py-6 lg:py-10">
        <?= $this->insert('components/breadcrumb', ['items' => $breadcrumb]) ?>
        <h1 class="mt-3 text-2xl font-bold text-ink lg:text-4xl"><?= e($judul) ?></h1>
        <?php if (!empty($subjudul)): ?>
        <p class="mt-2 max-w-2xl text-muted lg:text-lg"><?= e($subjudul) ?></p>
        <?php endif ?>
    </div>
</div>
