<?php
/**
 * Kartu pilar program (tanpa efek flip): ikon + label + deskripsi singkat.
 *
 * @var array{label: string, nama: string, slug: string, deskripsi: ?string, ikon: ?string} $pilar
 * @var string|null $heading tag judul (default h3)
 */
$ikonPilar = [
    'basel-peduli' => 'hand-heart',
    'basel-cerdas' => 'graduation',
    'basel-sehat'  => 'stethoscope',
    'basel-taqwa'  => 'mosque',
    'basel-makmur' => 'store',
];
$h = $heading ?? 'h3';
?>
<article class="group relative flex h-full flex-col items-center rounded-2xl border border-gray-200 bg-white p-5 text-center shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
    <span class="grid size-14 place-items-center rounded-2xl bg-primary text-white">
        <?php if (!empty($pilar['ikon'])): ?>
        <img src="<?= e(url($pilar['ikon'])) ?>" alt="" width="28" height="28" class="size-7" loading="lazy">
        <?php else: ?>
        <?= icon($ikonPilar[$pilar['slug']] ?? 'grid', 'size-7') ?>
        <?php endif ?>
    </span>
    <<?= $h ?> class="mt-3 font-bold text-ink group-hover:text-primary">
        <a href="<?= e(url('program/' . $pilar['slug'])) ?>" class="after:absolute after:inset-0"><?= e($pilar['label']) ?></a>
    </<?= $h ?>>
    <p class="text-xs font-semibold uppercase tracking-wide text-primary"><?= e($pilar['nama']) ?></p>
    <?php if (!empty($pilar['deskripsi'])): ?>
    <p class="mt-2 text-sm text-muted"><?= e($pilar['deskripsi']) ?></p>
    <?php endif ?>
</article>
