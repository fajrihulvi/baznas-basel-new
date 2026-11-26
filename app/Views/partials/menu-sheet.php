<?php
/**
 * Panel menu HP (< lg): masuk dari kiri, hijau tua transparan. Home, Tentang Kami (/tentang), Layanan (/layanan),
 * Program (/program), Arsip (/arsip) = tautan langsung. Item 'dari' (grup <details> dengan sub-menu desktop) tetap
 * didukung bila ditambahkan lagi di config/menu.php. Menu yang sedang dibuka berlatar kuning.
 * Dibuka tombol [data-sheet-open] (bilah header & Bottom Navigation). Isi dari config/menu.php → 'sheet'.
 *
 * @var App\Core\View $this
 */

use App\Services\Navigation;

$path = current_path();
$aktif = static fn (string $url): bool => $url === $path || ($url !== '/' && !str_starts_with($url, '#') && str_starts_with($path, rtrim($url, '/') . '/'));
?>
<dialog id="menu-sheet" aria-labelledby="menu-sheet-judul"
        class="sheet on-dark fixed inset-y-0 left-0 m-0 h-dvh font-montserrat max-h-none w-[85vw] max-w-sm bg-[#0b2a10]/95 p-0 text-white shadow-2xl backdrop-blur-sm lg:hidden">
    <div class="flex h-full flex-col">
        <div class="flex shrink-0 items-center justify-between gap-3 px-5 pt-4 pb-3">
            <a href="<?= e(url()) ?>" class="rounded-xl bg-white px-2 py-1.5">
                <?= $this->insert('partials/logo', ['class' => 'h-11 w-auto', 'sizes' => '64px', 'lazy' => true]) ?>
            </a>
            <h2 id="menu-sheet-judul" class="sr-only">Menu</h2>
            <button type="button" data-sheet-close class="grid size-12 place-items-center rounded-full hover:bg-white/10" aria-label="Tutup menu">
                <?= icon('x', 'size-9 stroke-[1.5]') ?>
            </button>
        </div>

        <nav aria-label="Menu" class="flex-1 overflow-y-auto overscroll-contain pb-[calc(1.5rem+env(safe-area-inset-bottom))]">
            <ul>
                <?php foreach (Navigation::sheet() as $menu): ?>
                <li>
                    <?php if ($menu['url'] !== null): ?>
                    <a href="<?= e(url($menu['url'])) ?>"<?= $menu['aktif'] ? ' aria-current="page"' : '' ?>
                       class="flex min-h-16 items-center px-6 text-lg font-semibold <?= $menu['aktif'] ? 'bg-accent text-ink' : 'hover:bg-white/10' ?>"><?= e($menu['label']) ?></a>
                    <?php else: ?>
                    <details class="group"<?= $menu['aktif'] ? ' open' : '' ?>>
                        <summary class="flex min-h-16 cursor-pointer items-center justify-between gap-3 px-6 text-lg font-semibold <?= $menu['aktif'] ? 'bg-accent text-ink' : 'hover:bg-white/10' ?>">
                            <?= e($menu['label']) ?>
                            <?= icon('caret', 'size-5 shrink-0 transition-transform group-open:rotate-180') ?>
                        </summary>
                        <ul class="bg-black/25 py-1">
                            <?php foreach ($menu['anak'] as $item): ?>
                            <?php $ini = $aktif($item['url']); ?>
                            <li>
                                <a href="<?= e(url($item['url'])) ?>"
                                   class="flex min-h-12 items-center border-l-4 py-2 pr-6 pl-9 text-base <?= $ini ? 'border-accent font-semibold text-accent' : 'border-transparent text-white/90 hover:bg-white/10 hover:text-white' ?>"
                                   <?= $ini ? 'aria-current="page"' : '' ?>><?= e($item['label']) ?></a>
                            </li>
                            <?php endforeach ?>
                        </ul>
                    </details>
                    <?php endif ?>
                </li>
                <?php endforeach ?>
            </ul>
        </nav>
    </div>
</dialog>
