<?php
/** @var App\Core\View $this */

use App\Services\Navigation;

$menu = Navigation::utama();
?>
<header class="sticky top-0 z-40 border-b border-gray-200 bg-white">
    <div class="mx-auto flex h-14 max-w-6xl items-center gap-3 px-4 lg:h-[72px]">
        <a href="<?= e(url()) ?>" class="flex min-w-0 items-center gap-2.5 rounded-lg">
            <?php /* alt kosong: nama tautan beranda dari teks di sebelahnya (tidak dibaca dua kali) */ ?>
            <?= $this->insert('partials/logo', ['class' => 'h-11 w-auto shrink-0 lg:h-14', 'sizes' => '(min-width: 1024px) 81px, 64px', 'alt' => '']) ?>
            <?php /* Judul boleh 2 baris di layar sangat sempit (header muat 3 baris pendek); tagline 1 baris */ ?>
            <span class="min-w-0">
                <span class="block text-[13px] font-extrabold leading-tight text-primary sm:text-base">Baznas Kab. Bangka Selatan</span>
                <span class="mt-0.5 block truncate text-[11px] font-medium max-[359px]:text-[10px] leading-tight text-gray-600 sm:text-xs">Amanah, Professional, Transparan</span>
            </span>
        </a>

        <nav aria-label="Menu utama" class="ml-auto hidden shrink-0 lg:block">
            <ul class="flex items-center gap-1">
                <?php foreach ($menu as $item): ?>
                <li>
                    <details class="group relative" data-dropdown>
                        <summary class="flex h-10 cursor-pointer items-center gap-1 rounded-lg px-2 font-medium hover:bg-surface <?= $item['aktif'] ? 'text-primary' : 'text-ink' ?>">
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

        <div class="hidden shrink-0 items-center gap-2 lg:flex">
            <?php /* Konfirmasi baru tampil ≥ xl agar nama situs tidak terdesak; di bawahnya tetap ada di menu Layanan */ ?>
            <span class="hidden xl:block"><?= $this->insert('components/button', ['label' => 'Konfirmasi', 'href' => '/konfirmasi', 'variant' => 'outline', 'size' => 'sm']) ?></span>
            <?= $this->insert('components/button', ['label' => 'Bayar Zakat', 'href' => '/bayar-zakat', 'size' => 'sm', 'class' => 'whitespace-nowrap']) ?>
        </div>
    </div>
</header>
