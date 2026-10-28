<?php
/**
 * Pagination: HP = "‹ Halaman 5 dari 12 ›", ≥ sm = nomor halaman lengkap.
 *
 * @var App\Core\Paginator $paginator
 */
$p = $paginator;
if (!$p->hasPages()) {
    return;
}
$kotak = 'grid h-11 min-w-11 place-items-center rounded-full px-3 text-sm font-semibold';
$panah = function (int $tujuan, bool $ada, string $ikon, string $label, string $rel) use ($p, $kotak): string {
    if (!$ada) {
        return '<span class="' . $kotak . ' border border-gray-200 text-gray-400" aria-hidden="true">' . icon($ikon, 'size-5') . '</span>';
    }

    return '<a href="' . e($p->url($tujuan)) . '" rel="' . $rel . '" aria-label="' . e($label) . '" class="' . $kotak
        . ' border border-gray-300 hover:border-primary hover:text-primary">' . icon($ikon, 'size-5') . '</a>';
};
?>
<nav aria-label="Halaman" class="mt-8 flex justify-center">
    <ul class="flex items-center gap-1">
        <li><?= $panah($p->page - 1, $p->page > 1, 'chevron-left', 'Halaman sebelumnya', 'prev') ?></li>
        <li class="px-3 text-sm text-muted sm:hidden" aria-current="page">Halaman <span class="font-bold text-ink"><?= $p->page ?></span> dari <?= $p->pages ?></li>
        <?php foreach ($p->window() as $n): ?>
        <li class="hidden sm:block">
            <?php if ($n === null): ?>
            <span class="<?= $kotak ?> text-muted" aria-hidden="true">…</span>
            <?php elseif ($n === $p->page): ?>
            <span class="<?= $kotak ?> bg-primary text-white" aria-current="page"><span class="sr-only">Halaman </span><?= $n ?></span>
            <?php else: ?>
            <a href="<?= e($p->url($n)) ?>" class="<?= $kotak ?> hover:bg-surface hover:text-primary"><span class="sr-only">Halaman </span><?= $n ?></a>
            <?php endif ?>
        </li>
        <?php endforeach ?>
        <li><?= $panah($p->page + 1, $p->page < $p->pages, 'chevron-right', 'Halaman berikutnya', 'next') ?></li>
    </ul>
</nav>
