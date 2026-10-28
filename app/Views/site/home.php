<?php
/**
 * Beranda (§7). Data dari HomeService::get() (cache 10 menit) + pilar dari SiteService.
 *
 * @var App\Core\View $this
 * @var list<array> $banner     id, judul, foto, foto_mobile, link, dim, dim_mobile
 * @var list<array> $layanan
 * @var list<array> $pilar
 * @var list<array> $ticker     judul, slug
 * @var array<string, list<array>> $kabar  per tab HomeService::TAB_KABAR
 * @var list<array> $video
 * @var list<array> $zakat      judul, slug
 * @var list<array> $testimoni
 * @var list<array> $mitra      id, nama, logo, dim
 */

use App\Services\HomeService;

$namaSitus = setting('site_name', (string) config('app.name'));
$blok      = 'mx-auto max-w-6xl px-4 py-10 lg:py-16';

// --- Hero: rasio mengikuti banner yang ada (±16:7). Rasio 4:3 di HP hanya bila SEMUA banner punya foto_mobile.
$semuaPunyaMobile = $banner !== [] && count(array_filter($banner, static fn (array $b): bool => !empty($b['foto_mobile']))) === count($banner);
$rasioHero  = $semuaPunyaMobile ? 'aspect-[4/3] sm:aspect-[16/7]' : 'aspect-[16/7]';
$sizesHero  = '(min-width: 1184px) 1152px, calc(100vw - 2rem)';
$pertama    = $banner[0] ?? null;

// Preload gambar LCP (slide pertama) agar mulai diunduh sebelum CSS selesai
if ($pertama !== null) {
    $this->start('head');
    $srcsetDesktop = img_srcset($pertama['foto'], 'banner', $pertama['dim'][0] ?? 1600);
    if ($semuaPunyaMobile) {
        $srcsetMobile = img_srcset($pertama['foto_mobile'], 'banner', $pertama['dim_mobile'][0] ?? 1200);
        echo '<link rel="preload" as="image" fetchpriority="high" media="(max-width: 639px)" imagesrcset="' . e((string) $srcsetMobile) . '" imagesizes="' . e($sizesHero) . '">' . "\n";
        echo '<link rel="preload" as="image" fetchpriority="high" media="(min-width: 640px)" imagesrcset="' . e((string) $srcsetDesktop) . '" imagesizes="' . e($sizesHero) . '">' . "\n";
    } elseif ($srcsetDesktop !== null) {
        echo '<link rel="preload" as="image" fetchpriority="high" imagesrcset="' . e($srcsetDesktop) . '" imagesizes="' . e($sizesHero) . '">' . "\n";
    }
    $this->stop();
}

$slideHero = [];
foreach ($banner as $i => $b) {
    [$w, $h] = $b['dim'] ?? [1600, 700];
    $gambar = img($b['foto'], 'banner', $b['judul'], [
        'width' => $w, 'height' => $h, 'sizes' => $sizesHero, 'eager' => $i === 0, 'rendah' => $i > 0,
        'class' => 'size-full object-cover',
    ]);
    if ($semuaPunyaMobile) {
        [$mw, $mh] = $b['dim_mobile'] ?? [1200, 900];
        $gambar = '<picture><source media="(max-width: 639px)" srcset="' . e((string) img_srcset($b['foto_mobile'], 'banner', $mw))
            . '" sizes="' . e($sizesHero) . '" width="' . $mw . '" height="' . $mh . '">' . $gambar . '</picture>';
    }
    $isi = '<div class="' . $rasioHero . ' overflow-hidden rounded-2xl bg-surface">' . $gambar . '</div>';
    $slideHero[] = !empty($b['link'])
        ? '<a href="' . e(url($b['link'])) . '" class="block rounded-2xl">' . $isi . '</a>'
        : $isi;
}

// --- Organisasi (JSON-LD)
$organisasi = array_filter([
    '@context'  => 'https://schema.org',
    '@type'     => 'NGO',
    'name'      => $namaSitus,
    'alternateName' => 'BAZNAS Basel',
    'url'       => url(),
    'logo'      => url('assets/img/icon-512.png'),
    'email'     => setting('email'),
    'telephone' => setting('telepon'),
    'address'   => setting('alamat') ? ['@type' => 'PostalAddress', 'streetAddress' => setting('alamat'), 'addressLocality' => 'Toboali', 'addressRegion' => 'Kepulauan Bangka Belitung', 'addressCountry' => 'ID'] : null,
    'sameAs'    => array_values(array_filter([setting('instagram'), setting('facebook'), setting('youtube'), setting('tiktok'), setting('twitter')])),
]);

$this->layout('site', [
    'description' => 'Tunaikan zakat, infaq, dan sedekah melalui BAZNAS Kabupaten Bangka Selatan: bayar zakat online, kalkulator zakat, program, dan kabar terbaru.',
    'image'       => $pertama ? url('uploads/banner/' . rawurlencode($pertama['foto'])) : null,
    'jsonLd'      => [$organisasi],
]);

$aksiCepat = [
    ['Bayar Zakat', '/bayar-zakat', 'heart'],
    ['Kalkulator', '/kalkulator-zakat', 'calculator'],
    ['Konfirmasi', '/konfirmasi', 'upload'],
    ['Rekening', '/rekening', 'bank'],
];
?>
<h1 class="sr-only"><?= e($namaSitus) ?> — <?= e(setting('tagline', 'Badan Amil Zakat Nasional')) ?></h1>

<?php /* 1. Hero banner */ ?>
<?php if ($slideHero !== []): ?>
<div class="mx-auto max-w-6xl px-4 pt-4 lg:pt-6">
    <?= $this->insert('components/carousel', [
        'label'     => 'Banner utama',
        'slides'    => $slideHero,
        'itemClass' => 'w-full',
        'autoplay'  => 6000,
    ]) ?>
</div>
<?php endif ?>

<?php /* 2. Aksi cepat (HP) */ ?>
<nav aria-label="Aksi cepat" class="mx-auto max-w-6xl px-4 pt-2 lg:hidden">
    <ul class="grid grid-cols-4 gap-2">
        <?php foreach ($aksiCepat as [$label, $link, $ikon]): ?>
        <li>
            <a href="<?= e(url($link)) ?>" class="flex flex-col items-center gap-1.5 rounded-2xl p-2 text-center text-xs font-semibold text-ink hover:bg-surface">
                <span class="grid size-12 place-items-center rounded-2xl bg-primary/10 text-primary"><?= icon($ikon) ?></span>
                <?= e($label) ?>
            </a>
        </li>
        <?php endforeach ?>
    </ul>
</nav>

<?php /* 3. Tunaikan Zakat (layanan) */ ?>
<?php if ($layanan !== []): ?>
<section class="<?= $blok ?>" aria-labelledby="judul-layanan">
    <?= $this->insert('components/section-title', [
        'judul' => 'Tunaikan Zakat', 'id' => 'judul-layanan',
        'subjudul' => 'Pilih cara yang paling mudah untuk Anda.',
    ]) ?>
    <?= $this->insert('components/carousel', [
        'label'   => 'Layanan zakat',
        'kolomLg' => min(4, count($layanan)),
        'slides'  => array_map(fn (array $l): string => $this->insert('components/card-layanan', ['layanan' => $l]), $layanan),
    ]) ?>
</section>
<?php endif ?>

<?php /* 4. Program Kami — 5 pilar */ ?>
<section class="cv-auto bg-surface" aria-labelledby="judul-program">
    <div class="<?= $blok ?>">
        <?= $this->insert('components/section-title', [
            'judul' => 'Program Kami', 'id' => 'judul-program', 'tengah' => true,
            'subjudul' => 'Lima pilar penyaluran zakat untuk masyarakat Bangka Selatan.',
        ]) ?>
        <ul class="grid grid-cols-2 gap-3 lg:grid-cols-5 lg:gap-4">
            <?php foreach ($pilar as $i => $p): ?>
            <li class="<?= $i === count($pilar) - 1 && count($pilar) % 2 === 1 ? 'col-span-2 lg:col-span-1' : '' ?>">
                <?= $this->insert('components/card-program', ['pilar' => $p]) ?>
            </li>
            <?php endforeach ?>
        </ul>
    </div>
</section>

<?php /* 5. Info BAZNAS (ticker) */ ?>
<?php if ($ticker !== []): ?>
<section aria-label="Info BAZNAS" class="mx-auto max-w-6xl px-4 pt-10 lg:pt-16">
    <div data-ticker class="flex h-14 items-center gap-3 rounded-2xl border border-primary/15 bg-primary/5 pr-1 pl-3">
        <span class="flex shrink-0 items-center gap-1.5 rounded-full bg-primary px-3 py-1 text-xs font-bold text-white">
            <?= icon('info', 'size-4') ?> Info
        </span>
        <ul class="relative h-full min-w-0 flex-1 overflow-hidden" aria-live="off">
            <?php foreach ($ticker as $i => $t): ?>
            <?php /* Ganti item: geser naik singkat, bukan fade — teks tidak pernah setengah transparan (kontras AA tetap utuh) */ ?>
            <li data-ticker-item class="absolute inset-0 flex items-center transition-transform duration-300 ease-out<?= $i > 0 ? ' invisible translate-y-3' : '' ?>"<?= $i > 0 ? ' aria-hidden="true"' : '' ?>>
                <a href="<?= e(url('berita/' . $t['slug'])) ?>"<?= $i > 0 ? ' tabindex="-1"' : '' ?> class="block w-full truncate py-3 text-sm font-medium text-ink hover:text-primary hover:underline"><?= e($t['judul']) ?></a>
            </li>
            <?php endforeach ?>
        </ul>
        <button type="button" data-ticker-pause hidden aria-pressed="false" aria-label="Jeda info bergulir"
                class="grid size-11 shrink-0 place-items-center rounded-full text-primary hover:bg-primary/10">
            <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path data-ikon-jeda d="M7 5h3.5v14H7zM13.5 5H17v14h-3.5z"/><path data-ikon-putar hidden d="M8 5.5v13l10.5-6.5z"/></svg>
        </button>
    </div>
</section>
<?php endif ?>

<?php /* 6. Kabar BAZNAS */ ?>
<section class="cv-auto <?= $blok ?>" aria-labelledby="judul-kabar" data-tabs>
    <?= $this->insert('components/section-title', ['judul' => 'Kabar BAZNAS', 'id' => 'judul-kabar', 'link' => '/berita']) ?>
    <?= $this->insert('components/chip-tabs', [
        'label' => 'Jenis kabar',
        'items' => array_map(static fn (string $kunci, array $tab): array => [
            'label' => $tab[0],
            'url'   => $tab[1] === null ? '/berita' : '/berita?jenis=' . $tab[1],
            'aktif' => $kunci === 'semua',
            'tab'   => $kunci,
        ], array_keys(HomeService::TAB_KABAR), HomeService::TAB_KABAR),
    ]) ?>
    <?php foreach (HomeService::TAB_KABAR as $kunci => [$labelTab]):
        $isiPanel = ($kabar[$kunci] ?? []) === []
            ? $this->insert('components/empty-state', ['judul' => 'Belum ada ' . mb_strtolower($labelTab), 'ikon' => 'newspaper'])
            : $this->insert('components/carousel', [
                'label'  => 'Kabar: ' . $labelTab,
                'slides' => array_map(fn (array $p): string => $this->insert('components/card-post', ['post' => $p]), $kabar[$kunci]),
            ]);
    ?>
    <?php if ($kunci === 'semua'): ?>
    <div class="mt-5" data-tab-panel="<?= e($kunci) ?>"><?= $isiPanel ?></div>
    <?php else: ?>
    <?php /* Tab lain di <template>: tidak masuk DOM aktif (lebih ringan) sampai dipilih — tabs.js menyisipkannya */ ?>
    <div class="mt-5" data-tab-panel="<?= e($kunci) ?>" hidden><template data-tab-isi><?= $isiPanel ?></template></div>
    <?php endif ?>
    <?php endforeach ?>
</section>

<?php /* 7. Video */ ?>
<?php if ($video !== []): ?>
<section class="cv-auto bg-surface" aria-labelledby="judul-video">
    <div class="<?= $blok ?>">
        <?= $this->insert('components/section-title', ['judul' => 'Video', 'id' => 'judul-video', 'subjudul' => 'Kisah penyaluran zakat dari lapangan.', 'link' => '/video']) ?>
        <?= $this->insert('components/carousel', [
            'label'   => 'Video terbaru',
            'kolomLg' => 3,
            'slides'  => array_map(fn (array $v): string => $this->insert('components/card-video', ['video' => $v]), $video),
        ]) ?>
    </div>
</section>
<?php endif ?>

<?php /* 8. Penjelasan Zakat */ ?>
<?php if ($zakat !== []): ?>
<section class="cv-auto <?= $blok ?>" aria-labelledby="judul-zakat">
    <?= $this->insert('components/section-title', ['judul' => 'Kenali Zakat', 'id' => 'judul-zakat', 'subjudul' => 'Pahami jenis zakat sebelum menunaikannya.', 'link' => '/zakat']) ?>
    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
        <?php foreach ($zakat as $z): ?>
        <li>
            <a href="<?= e(url('zakat/' . $z['slug'])) ?>" class="flex h-full min-h-14 items-center gap-2.5 rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-ink hover:border-primary hover:text-primary">
                <?= icon('book', 'size-5 shrink-0 text-primary') ?><?= e($z['judul']) ?>
            </a>
        </li>
        <?php endforeach ?>
    </ul>
    <div class="mt-6 flex flex-col items-start gap-4 rounded-2xl bg-primary p-6 text-white sm:flex-row sm:items-center sm:justify-between lg:p-8">
        <div>
            <p class="text-lg font-bold lg:text-xl">Berapa zakat yang harus Anda tunaikan?</p>
            <p class="mt-1 text-white/85">Hitung zakat penghasilan, emas, perdagangan, dan fidyah dalam hitungan detik.</p>
        </div>
        <?= $this->insert('components/button', ['label' => 'Buka Kalkulator', 'href' => '/kalkulator-zakat', 'variant' => 'light', 'icon' => 'calculator', 'class' => 'shrink-0']) ?>
    </div>
</section>
<?php endif ?>

<?php /* 9. Testimoni (tampil bila sudah ada data terbit) */ ?>
<?php if ($testimoni !== []): ?>
<section class="cv-auto bg-surface" aria-labelledby="judul-testimoni">
    <div class="<?= $blok ?>">
        <?= $this->insert('components/section-title', ['judul' => 'Kata Mereka', 'id' => 'judul-testimoni', 'subjudul' => 'Pengalaman muzaki dan mustahik bersama BAZNAS Bangka Selatan.', 'link' => '/testimoni']) ?>
        <?= $this->insert('components/carousel', [
            'label'     => 'Testimoni',
            'itemClass' => 'w-[85%] sm:w-[45%] lg:w-[calc((100%-2*1rem)/3)]',
            'slides'    => array_map(fn (array $t): string => $this->insert('components/card-testimoni', ['testimoni' => $t]), $testimoni),
        ]) ?>
    </div>
</section>
<?php endif ?>

<?php /* 10. Mitra */ ?>
<?php if ($mitra !== []): ?>
<section id="mitra" class="cv-auto scroll-mt-16 <?= $testimoni !== [] ? '' : 'bg-surface' ?>" aria-labelledby="judul-mitra">
    <div class="<?= $blok ?>">
        <?= $this->insert('components/section-title', ['judul' => 'Mitra Kami', 'id' => 'judul-mitra', 'tengah' => true]) ?>
        <ul tabindex="0" aria-label="Logo mitra — geser untuk melihat lainnya"
            class="-mx-4 flex snap-x gap-3 overflow-x-auto px-4 pb-2 scrollbar-none lg:mx-0 lg:flex-wrap lg:justify-center lg:overflow-visible lg:px-0">
            <?php foreach ($mitra as $m): ?>
            <li class="shrink-0 snap-start">
                <div class="grid h-20 w-36 place-items-center rounded-xl border border-gray-200 bg-white p-3">
                    <?= img($m['logo'], 'mitra', $m['nama'], [
                        'width' => $m['dim'][0] ?? null, 'height' => $m['dim'][1] ?? null, 'sizes' => '120px',
                        'class' => 'max-h-14 w-auto object-contain opacity-80 grayscale transition hover:opacity-100 hover:grayscale-0',
                    ]) ?>
                </div>
            </li>
            <?php endforeach ?>
        </ul>
    </div>
</section>
<?php endif ?>

<?php $this->start('scripts') ?>
    <?php /* Prioritas rendah: tidak berebut bandwidth dengan gambar LCP; halaman sudah berfungsi tanpa JS */ ?>
    <script type="module" src="<?= e(asset('js/carousel.js')) ?>" fetchpriority="low"></script>
    <script type="module" src="<?= e(asset('js/tabs.js')) ?>" fetchpriority="low"></script>
    <script type="module" src="<?= e(asset('js/ticker.js')) ?>" fetchpriority="low"></script>
    <script type="module" src="<?= e(asset('js/lite-yt.js')) ?>" fetchpriority="low"></script>
<?php $this->stop() ?>
