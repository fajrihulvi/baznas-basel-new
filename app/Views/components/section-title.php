<?php
/**
 * Judul section: garis aksen emas + judul (h2) + subjudul + link "Lihat semua" opsional.
 *
 * @var string      $judul
 * @var string|null $subjudul
 * @var string|null $id        id untuk aria-labelledby section induk
 * @var string|null $link      URL "Lihat semua"
 * @var string|null $linkLabel default "Lihat semua"
 * @var bool|null   $tengah    rata tengah
 */
$tengah = !empty($tengah);
?>
<div class="mb-6 flex gap-4 <?= $tengah ? 'flex-col items-center text-center' : 'items-end justify-between' ?>">
    <div>
        <span class="mb-2 block h-1 w-10 rounded-full bg-accent <?= $tengah ? 'mx-auto' : '' ?>" aria-hidden="true"></span>
        <h2<?= !empty($id) ? ' id="' . e($id) . '"' : '' ?> class="text-xl font-bold text-ink lg:text-3xl"><?= e($judul) ?></h2>
        <?php if (!empty($subjudul)): ?>
        <p class="mt-1 text-muted lg:text-lg"><?= e($subjudul) ?></p>
        <?php endif ?>
    </div>
    <?php if (!empty($link)): ?>
    <a href="<?= e(url($link)) ?>" class="inline-flex min-h-11 shrink-0 items-center gap-1 text-sm font-semibold text-primary hover:underline">
        <?= e($linkLabel ?? 'Lihat semua') ?> <?= icon('arrow-right', 'size-4') ?>
    </a>
    <?php endif ?>
</div>
