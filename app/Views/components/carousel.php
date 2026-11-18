<?php
/**
 * Carousel CSS scroll-snap (tanpa library). Tanpa JS bisa digeser jari/scroll;
 * carousel.js menambah titik navigasi, tombol, dan autoplay.
 *
 * @var string       $label     nama carousel untuk pembaca layar, mis. "Kabar terbaru"
 * @var list<string> $slides    HTML tiap slide (sudah di-escape oleh komponen kartunya)
 * @var string|null  $itemClass lebar slide; default 1,2 kartu terlihat di HP, 2,2 di tablet, 4 di desktop
 * @var int|null     $autoplay  milidetik (0 = mati). Ada tombol jeda (WCAG 2.2.2) kecuali $tombolJeda = false
 * @var bool|null    $tombolJeda false = tanpa tombol jeda (autoplay tetap berhenti saat hover/fokus/sentuh & prefers-reduced-motion)
 * @var bool|null    $titik      false = tanpa titik penanda slide. Bila tanpa titik, jeda & panah bawah → baris kontrol tidak dirender
 * @var int|null     $kolomLg   bila diisi: di ≥ lg tampil sebagai grid N kolom tanpa geser & tanpa kontrol
 * @var string|null  $id
 * @var string|null  $kontrolClass class tambahan baris kontrol, mis. agar sejajar konten saat carousel selebar layar
 * @var bool|null    $panahSamping tombol sebelumnya/berikutnya di sisi kiri & kanan slide (bukan di bawah) dan berputar
 *                                 (panah kiri di slide pertama → slide terakhir) — untuk banner/hero satu slide per layar
 *
 * Track selalu bisa difokus (tabindex=0) agar bisa digeser dengan tombol panah keyboard,
 * termasuk bila slide tidak berisi link (mis. banner tanpa tautan) — WCAG 2.1.1.
 */
$kolomLg = (int) ($kolomLg ?? 0);
$gridLg = [2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3', 4 => 'lg:grid-cols-4', 5 => 'lg:grid-cols-5'][$kolomLg] ?? '';
$itemClass ??= $kolomLg > 0 ? 'w-[82%] sm:w-[45%] lg:w-auto' : 'w-[82%] sm:w-[45%] lg:w-[calc((100%-3*1rem)/4)]';
$trackLg = $kolomLg > 0 ? "lg:grid {$gridLg} lg:overflow-visible" : '';
$panahSamping = !empty($panahSamping) && count($slides) > 1;
$adaJeda = !empty($autoplay) && ($tombolJeda ?? true) !== false;
$adaTitik = ($titik ?? true) !== false;
$adaKontrol = count($slides) > 1 && ($adaTitik || $adaJeda || !$panahSamping);
// Panah di atas gambar: latar putih tembus + bayangan agar terlihat di atas foto terang maupun gelap
$kelasPanah = 'invisible absolute top-[calc(50%-0.25rem)] z-10 grid size-8 -translate-y-1/2 place-items-center rounded-full bg-white/85 text-ink shadow-lg backdrop-blur-sm transition hover:bg-white hover:text-primary sm:size-10 lg:size-12';
?>
<section class="relative"<?= !empty($id) ? ' id="' . e($id) . '"' : '' ?> aria-roledescription="carousel" aria-label="<?= e($label) ?>"
         data-carousel<?= !empty($autoplay) ? ' data-autoplay="' . (int) $autoplay . '"' : '' ?><?= $panahSamping ? ' data-carousel-putar' : '' ?>>
    <?php /* Wadah track: panah samping diposisikan terhadap gambar saja (bukan ikut baris titik di bawahnya) */ ?>
    <div class="relative">
    <?php if ($panahSamping): ?>
    <?php /* invisible sampai carousel.js memastikan slide bisa digeser (anti-CLS) */ ?>
    <button type="button" data-carousel-prev data-carousel-panah class="<?= $kelasPanah ?> left-6 lg:left-8" aria-label="Sebelumnya">
        <?= icon('chevron-left', 'size-4 sm:size-5 lg:size-6') ?>
    </button>
    <button type="button" data-carousel-next data-carousel-panah class="<?= $kelasPanah ?> right-6 lg:right-8" aria-label="Berikutnya">
        <?= icon('chevron-right', 'size-4 sm:size-5 lg:size-6') ?>
    </button>
    <?php endif ?>
    <ul class="-mx-4 flex snap-x snap-mandatory scroll-px-4 gap-4 overflow-x-auto overscroll-x-contain px-4 pb-2 scrollbar-none lg:mx-0 lg:scroll-px-0 lg:px-0 <?= $trackLg ?>"
        tabindex="0" aria-label="<?= e($label) ?> — geser untuk melihat lainnya" data-carousel-track>
        <?php foreach ($slides as $i => $slide): ?>
        <li class="shrink-0 snap-start <?= e($itemClass) ?>" aria-roledescription="slide" aria-label="<?= $i + 1 ?> dari <?= count($slides) ?>">
            <?= $slide ?>
        </li>
        <?php endforeach ?>
    </ul>
    </div>
    <?php if ($adaKontrol): ?>
    <div class="invisible mt-3 flex items-center justify-between gap-3<?= $kolomLg > 0 ? ' lg:hidden' : '' ?><?= !empty($kontrolClass) ? ' ' . e($kontrolClass) : '' ?>" data-carousel-controls>
        <?php if ($adaTitik): ?>
        <div class="flex flex-wrap items-center" data-carousel-dots></div>
        <?php endif ?>
        <?php if ($adaJeda || !$panahSamping): ?>
        <div class="flex shrink-0 gap-2">
            <?php if ($adaJeda): ?>
            <button type="button" data-carousel-pause aria-pressed="false" class="grid size-11 place-items-center rounded-full border border-gray-300 bg-white hover:border-primary hover:text-primary" aria-label="Jeda putar otomatis">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path data-ikon-jeda d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z"/><path data-ikon-putar hidden d="M8 5.5v13l10.5-6.5z"/></svg>
            </button>
            <?php endif ?>
            <?php if (!$panahSamping): ?>
            <button type="button" data-carousel-prev class="grid size-11 place-items-center rounded-full border border-gray-300 bg-white hover:border-primary hover:text-primary disabled:opacity-40" aria-label="Sebelumnya">
                <?= icon('chevron-left', 'size-5') ?>
            </button>
            <button type="button" data-carousel-next class="grid size-11 place-items-center rounded-full border border-gray-300 bg-white hover:border-primary hover:text-primary disabled:opacity-40" aria-label="Berikutnya">
                <?= icon('chevron-right', 'size-5') ?>
            </button>
            <?php endif ?>
        </div>
        <?php endif ?>
    </div>
    <?php endif ?>
</section>
