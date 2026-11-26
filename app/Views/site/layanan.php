<?php
/**
 * /layanan — tujuan menu "Layanan" (bilah navigasi bawah & panel menu HP): ikon layanan zakat (dipindah dari
 * "aksi cepat" beranda), kartu Tunaikan Zakat 2 kolom (sama dengan beranda), lalu kartu Kegiatan BAZNAS.
 *
 * @var App\Core\View $this
 * @var list<array> $tunaikan  kartu Tunaikan Zakat (admin → Tunaikan Zakat (Beranda), maks. 4)
 * @var list<array> $kegiatan  SiteService::kegiatan()
 */
$this->layout('site', [
    'title'       => 'Layanan',
    'description' => 'Layanan zakat BAZNAS Kabupaten Bangka Selatan: bayar zakat online, kalkulator zakat, konfirmasi pembayaran, dan rekening resmi.',
]);

// [label, tautan, [jenis ikon, nama]] — 'svg' = ikon dua warna partials/ikon-nav, 'img' = assets/img/aksi/{nama}-{72,108}.webp
$layanan = [
    ['Bayar Zakat', '/bayar-zakat', ['svg', 'bayar-zakat']],
    ['Kalkulator', '/kalkulator-zakat', ['img', 'kalkulator']],
    ['Konfirmasi', '/konfirmasi', ['img', 'konfirmasi']],
    ['Rekening', '/rekening', ['img', 'rekening']],
];
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Layanan',
    'subjudul'   => 'Pilih layanan zakat yang Anda butuhkan.',
    'breadcrumb' => [['label' => 'Layanan']],
]) ?>

<div class="mx-auto max-w-4xl px-4 py-8 lg:py-12">
    <ul class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
        <?php foreach ($layanan as [$label, $link, [$jenis, $ikon]]): ?>
        <li>
            <a href="<?= e(url($link)) ?>"
               class="group flex h-full flex-col items-center gap-3 rounded-2xl border border-gray-200 bg-white p-5 text-center font-bold text-ink shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:text-primary hover:shadow-md">
                <span class="grid size-20 place-items-center rounded-2xl bg-primary/10">
                    <?php if ($jenis === 'svg'): ?>
                    <?= $this->insert('partials/ikon-nav', ['nama' => $ikon, 'aktif' => false, 'berwarna' => true, 'ukuran' => 'size-12']) ?>
                    <?php else: ?>
                    <img src="<?= e(asset('img/aksi/' . $ikon . '-108.webp')) ?>" srcset="<?= e(asset('img/aksi/' . $ikon . '-72.webp')) ?> 1.5x, <?= e(asset('img/aksi/' . $ikon . '-108.webp')) ?> 2.25x"
                         width="48" height="48" alt="" decoding="async" class="size-12">
                    <?php endif ?>
                </span>
                <?= e($label) ?>
            </a>
        </li>
        <?php endforeach ?>
    </ul>
</div>

<?php if ($tunaikan !== []): ?>
<section class="mx-auto max-w-4xl px-4 pb-10 lg:pb-14" aria-labelledby="judul-tunaikan-zakat">
    <h2 id="judul-tunaikan-zakat" class="text-2xl font-bold tracking-wide text-primary uppercase lg:text-3xl">Tunaikan Zakat</h2>
    <span class="mt-2 block h-1 w-40 bg-[#ef8a14] lg:w-48" aria-hidden="true"></span>
    <ul class="mt-6 grid grid-cols-2 gap-3 sm:gap-6">
        <?php foreach ($tunaikan as $l): ?>
        <li><?= $this->insert('components/card-layanan', ['layanan' => $l]) ?></li>
        <?php endforeach ?>
    </ul>
</section>
<?php endif ?>

<?= $this->insert('components/kegiatan-baznas', ['kegiatan' => $kegiatan, 'judul' => 'Kegiatan BAZNAS', 'id' => 'judul-kegiatan-baznas']) ?>
