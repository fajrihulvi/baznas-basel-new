<?php
/** @var App\Core\View $this */

use App\Services\Navigation;
?>
<dialog id="menu-sheet" aria-labelledby="menu-sheet-judul"
        class="sheet fixed inset-x-0 top-auto bottom-0 m-0 max-h-[85dvh] w-full max-w-none rounded-t-3xl bg-white p-0 text-ink shadow-2xl lg:hidden">
    <div class="flex max-h-[85dvh] flex-col">
        <div data-sheet-handle class="shrink-0 cursor-grab pt-3 pb-1">
            <div class="mx-auto h-1.5 w-10 rounded-full bg-gray-300"></div>
        </div>
        <div class="flex shrink-0 items-center justify-between px-5 pb-2">
            <h2 id="menu-sheet-judul" class="text-lg font-bold">Menu</h2>
            <button type="button" data-sheet-close class="grid size-12 place-items-center rounded-full hover:bg-surface" aria-label="Tutup menu">
                <?= icon('x') ?>
            </button>
        </div>

        <div class="overflow-y-auto overscroll-contain px-5 pb-[calc(1.5rem+env(safe-area-inset-bottom))]">
            <?php foreach (Navigation::sheet() as $grup): ?>
            <section class="mt-3 first:mt-0">
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted"><?= e($grup['judul']) ?></h3>
                <ul class="grid grid-cols-4 gap-1">
                    <?php foreach ($grup['item'] as $item): ?>
                    <li>
                        <a href="<?= e(url($item['url'])) ?>"
                           class="flex min-h-20 flex-col items-center gap-1.5 rounded-2xl p-1.5 text-center text-xs font-medium leading-tight hover:bg-surface"
                           <?= current_path() === $item['url'] ? 'aria-current="page"' : '' ?>>
                            <span class="grid size-11 place-items-center rounded-xl bg-primary/10 text-primary"><?= icon($item['ikon']) ?></span>
                            <?= e($item['label']) ?>
                        </a>
                    </li>
                    <?php endforeach ?>
                </ul>
            </section>
            <?php endforeach ?>
        </div>
    </div>
</dialog>
