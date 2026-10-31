<?php
/**
 * Carousel CSS scroll-snap (tanpa library). Tanpa JS bisa digeser jari/scroll;
 * carousel.js menambah titik navigasi, tombol, dan autoplay.
 *
 * @var string       $label     nama carousel untuk pembaca layar, mis. "Kabar terbaru"
 * @var list<string> $slides    HTML tiap slide (sudah di-escape oleh komponen kartunya)
 * @var string|null  $itemClass lebar slide; default 1,2 kartu terlihat di HP, 2,2 di tablet, 4 di desktop
 * @var int|null     $autoplay  milidetik (0 = mati). Ada tombol jeda (WCAG 2.2.2)
 * @var int|null     $kolomLg   bila diisi: di ≥ lg tampil sebagai grid N kolom tanpa geser & tanpa kontrol
 * @var string|null  $id
 * @var string|null  $kontrolClass class tambahan baris kontrol, mis. agar sejajar konten saat carousel selebar layar
 *
 * Track selalu bisa difokus (tabindex=0) agar bisa digeser dengan tombol panah keyboard,
 * termasuk bila slide tidak berisi link (mis. banner tanpa tautan) — WCAG 2.1.1.
 */
$kolomLg = (int) ($kolomLg ?? 0);
$gridLg = [2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4', 5 => 'lg:grid-cols-5'][$kolomLg] ?? '';
$itemClass ??= $kolomLg > 0 ? 'w-[82%] sm:w-[45%] lg:w-auto' : 'w-[82%] sm:w-[45%] lg:w-[calc((100%-3*1rem)/4)]';
$trackLg = $kolomLg > 0 ? "lg:grid {$gridLg} lg:overflow-visible" : '';
?>
<section class="relative"<?= !empty($id) ? ' id="' . e($id) . '"' : '' ?> aria-roledescription="carousel" aria-label="<?= e($label) ?>"
         data-carousel<?= !empty($autoplay) ? ' data-autoplay="' . (int) $autoplay . '"' : '' ?>>
    <ul class="-mx-4 flex snap-x snap-mandatory scroll-px-4 gap-4 overflow-x-auto overscroll-x-contain px-4 pb-2 scrollbar-none lg:mx-0 lg:scroll-px-0 lg:px-0 <?= $trackLg ?>"
        tabindex="0" aria-label="<?= e($label) ?> — geser untuk melihat lainnya" data-carousel-track>
        <?php foreach ($slides as $i => $slide): ?>
        <li class="shrink-0 snap-start <?= e($itemClass) ?>" aria-roledescription="slide" aria-label="<?= $i + 1 ?> dari <?= count($slides) ?>">
            <?= $slide ?>
        </li>
        <?php endforeach ?>
    </ul>
    <?php if (count($slides) > 1): ?>
    <div class="invisible mt-3 flex items-center justify-between gap-3<?= $kolomLg > 0 ? ' lg:hidden' : '' ?><?= !empty($kontrolClass) ? ' ' . e($kontrolClass) : '' ?>" data-carousel-controls>
        <div class="flex flex-wrap items-center" data-carousel-dots></div>
        <div class="flex shrink-0 gap-2">
            <?php if (!empty($autoplay)): ?>
            <button type="button" data-carousel-pause aria-pressed="false" class="grid size-11 place-items-center rounded-full border border-gray-300 bg-white hover:border-primary hover:text-primary" aria-label="Jeda putar otomatis">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path data-ikon-jeda d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z"/><path data-ikon-putar hidden d="M8 5.5v13l10.5-6.5z"/></svg>
            </button>
            <?php endif ?>
            <button type="button" data-carousel-prev class="grid size-11 place-items-center rounded-full border border-gray-300 bg-white hover:border-primary hover:text-primary disabled:opacity-40" aria-label="Sebelumnya">
                <?= icon('chevron-left', 'size-5') ?>
            </button>
            <button type="button" data-carousel-next class="grid size-11 place-items-center rounded-full border border-gray-300 bg-white hover:border-primary hover:text-primary disabled:opacity-40" aria-label="Berikutnya">
                <?= icon('chevron-right', 'size-5') ?>
            </button>
        </div>
    </div>
    <?php endif ?>
</section>
