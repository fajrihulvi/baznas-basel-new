<?php
/** @var App\Core\View $this */

use App\Services\Navigation;
?>
<nav aria-label="Navigasi bawah" class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-200 bg-white pb-[env(safe-area-inset-bottom)] lg:hidden">
    <ul class="mx-auto grid h-16 max-w-md grid-cols-5">
        <?php foreach (Navigation::bawah() as $item): ?>
        <li class="flex">
            <?php if ($item['url'] === null): ?>
            <button type="button" data-sheet-open aria-controls="menu-sheet" aria-haspopup="dialog" aria-expanded="false"
                    class="flex w-full flex-col items-center justify-center gap-0.5 text-[11px] font-medium text-muted hover:text-primary">
                <?= icon($item['ikon']) ?>
                <?= e($item['label']) ?>
            </button>
            <?php elseif ($item['tengah']): ?>
            <a href="<?= e(url($item['url'])) ?>" <?= $item['aktif'] ? 'aria-current="page"' : '' ?>
               class="flex w-full flex-col items-center justify-end gap-0.5 pb-1.5 text-[11px] font-bold text-primary">
                <span class="-mt-6 grid size-14 place-items-center rounded-full bg-primary text-white shadow-lg ring-4 ring-white">
                    <?= icon($item['ikon'], 'size-7') ?>
                </span>
                <?= e($item['label']) ?>
            </a>
            <?php else: ?>
            <a href="<?= e(url($item['url'])) ?>" <?= $item['aktif'] ? 'aria-current="page"' : '' ?>
               class="relative flex w-full flex-col items-center justify-center gap-0.5 text-[11px] <?= $item['aktif'] ? 'font-bold text-primary' : 'font-medium text-muted hover:text-primary' ?>">
                <?php if ($item['aktif']): ?><span class="absolute top-0 h-1 w-8 rounded-b-full bg-primary" aria-hidden="true"></span><?php endif ?>
                <?= icon($item['ikon']) ?>
                <?= e($item['label']) ?>
            </a>
            <?php endif ?>
        </li>
        <?php endforeach ?>
    </ul>
</nav>
