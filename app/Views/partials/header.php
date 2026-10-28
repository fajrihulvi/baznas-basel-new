<?php
/** @var App\Core\View $this */

use App\Services\Navigation;

$menu = Navigation::utama();
?>
<header class="sticky top-0 z-40 border-b border-gray-200 bg-white">
    <div class="mx-auto flex h-14 max-w-6xl items-center gap-3 px-4 lg:h-[72px]">
        <a href="<?= e(url()) ?>" class="flex min-w-0 items-center gap-2.5 rounded-lg">
            <picture>
                <source srcset="<?= e(asset('img/logo-garuda.webp')) ?>" type="image/webp">
                <img src="<?= e(asset('img/logo-garuda.png')) ?>" alt="" width="81" height="96" class="h-9 w-auto lg:h-12">
            </picture>
            <span class="leading-tight">
                <span class="block font-extrabold tracking-tight text-primary lg:text-lg">BAZNAS<span class="lg:hidden"> Basel</span></span>
                <span class="hidden text-xs font-medium text-muted lg:block">Kabupaten Bangka Selatan</span>
            </span>
        </a>

        <nav aria-label="Menu utama" class="ml-auto hidden lg:block">
            <ul class="flex items-center gap-1">
                <?php foreach ($menu as $item): ?>
                <li>
                    <details class="group relative" data-dropdown>
                        <summary class="flex h-10 cursor-pointer items-center gap-1 rounded-lg px-3 font-medium hover:bg-surface <?= $item['aktif'] ? 'text-primary' : 'text-ink' ?>">
                            <?= e($item['label']) ?>
                            <?= icon('chevron-down', 'size-4 transition-transform group-open:rotate-180') ?>
                        </summary>
                        <ul class="absolute left-0 top-full z-50 mt-2 w-60 rounded-2xl border border-gray-200 bg-white p-2 shadow-lg">
                            <?php foreach ($item['anak'] as $anak): ?>
                            <li>
                                <a href="<?= e(url($anak['url'])) ?>"
                                   class="block rounded-lg px-3 py-2 text-sm hover:bg-surface hover:text-primary<?= current_path() === $anak['url'] ? ' bg-surface font-semibold text-primary' : '' ?>"
                                   <?= current_path() === $anak['url'] ? 'aria-current="page"' : '' ?>><?= e($anak['label']) ?></a>
                            </li>
                            <?php endforeach ?>
                        </ul>
                    </details>
                </li>
                <?php endforeach ?>
            </ul>
        </nav>

        <a href="<?= e(url('cari')) ?>" class="ml-auto grid size-12 shrink-0 place-items-center rounded-full text-ink hover:bg-surface lg:ml-0 lg:size-10" aria-label="Cari">
            <?= icon('search') ?>
        </a>

        <div class="hidden items-center gap-2 lg:flex">
            <?= $this->insert('components/button', ['label' => 'Konfirmasi', 'href' => '/konfirmasi', 'variant' => 'outline', 'size' => 'sm']) ?>
            <?= $this->insert('components/button', ['label' => 'Bayar Zakat', 'href' => '/bayar-zakat', 'size' => 'sm']) ?>
        </div>
    </div>
</header>
