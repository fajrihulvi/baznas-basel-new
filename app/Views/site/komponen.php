<?php
/**
 * Katalog komponen (dev). Bukan halaman publik.
 *
 * @var App\Core\View $this
 * @var array $posts
 * @var array $pilar
 * @var array $layanan
 * @var array $video
 * @var array $testimoni
 * @var App\Core\Paginator $paginator
 */
$this->layout('site', ['title' => 'Katalog Komponen', 'noindex' => true]);

$blok = 'mx-auto max-w-6xl px-4 py-10 lg:py-16';
?>
<div class="<?= $blok ?> pb-0 lg:pb-0">
    <?= $this->insert('components/breadcrumb', ['items' => [['label' => 'Pengembangan'], ['label' => 'Katalog Komponen']]]) ?>
    <h1 class="mt-3 text-2xl font-bold lg:text-3xl">Katalog Komponen</h1>
    <p class="mt-1 text-muted">Semua komponen §13.3 dengan data asli. Hanya tampil saat APP_DEBUG=true.</p>
</div>

<section class="<?= $blok ?>" aria-labelledby="k-tombol">
    <?= $this->insert('components/section-title', ['judul' => 'Tombol & badge', 'id' => 'k-tombol', 'subjudul' => 'primary / outline / ghost — sm / md / lg']) ?>
    <div class="flex flex-wrap items-center gap-3">
        <?= $this->insert('components/button', ['label' => 'Bayar Zakat', 'href' => '/bayar-zakat', 'size' => 'lg', 'icon' => 'arrow-right']) ?>
        <?= $this->insert('components/button', ['label' => 'Kalkulator', 'href' => '/kalkulator-zakat', 'variant' => 'outline']) ?>
        <?= $this->insert('components/button', ['label' => 'Konfirmasi', 'href' => '/konfirmasi', 'variant' => 'ghost', 'size' => 'sm']) ?>
        <?= $this->insert('components/button', ['label' => 'Kirim', 'type' => 'button']) ?>
    </div>
    <div class="mt-4 flex flex-wrap gap-2">
        <?= $this->insert('components/badge', ['label' => 'Berita']) ?>
        <?= $this->insert('components/badge', ['label' => 'Baru', 'variant' => 'accent']) ?>
        <?= $this->insert('components/badge', ['label' => 'Draf', 'variant' => 'muted']) ?>
    </div>
</section>

<section class="bg-surface" aria-labelledby="k-layanan">
    <div class="<?= $blok ?>">
        <?= $this->insert('components/section-title', ['judul' => 'Tunaikan Zakat', 'id' => 'k-layanan', 'subjudul' => 'card-layanan dalam carousel']) ?>
        <?= $this->insert('components/carousel', [
            'label'  => 'Layanan zakat',
            'slides' => array_map(fn (array $l): string => $this->insert('components/card-layanan', ['layanan' => $l]), $layanan),
        ]) ?>
    </div>
</section>

<section class="<?= $blok ?>" aria-labelledby="k-program">
    <?= $this->insert('components/section-title', ['judul' => 'Program Kami', 'id' => 'k-program', 'tengah' => true, 'subjudul' => 'card-program — 5 pilar']) ?>
    <ul class="grid grid-cols-2 gap-3 lg:grid-cols-5 lg:gap-4">
        <?php foreach ($pilar as $i => $p): ?>
        <li class="<?= $i === count($pilar) - 1 && count($pilar) % 2 === 1 ? 'col-span-2 lg:col-span-1' : '' ?>">
            <?= $this->insert('components/card-program', ['pilar' => $p]) ?>
        </li>
        <?php endforeach ?>
    </ul>
</section>

<section class="bg-surface" aria-labelledby="k-kabar">
    <div class="<?= $blok ?>">
        <?= $this->insert('components/section-title', ['judul' => 'Kabar BAZNAS', 'id' => 'k-kabar', 'link' => '/berita']) ?>
        <?= $this->insert('components/chip-tabs', ['label' => 'Filter kabar', 'items' => [
            ['label' => 'Semua', 'url' => '/_komponen', 'aktif' => true],
            ['label' => 'Berita', 'url' => '/berita?jenis=berita', 'aktif' => false],
            ['label' => 'Artikel', 'url' => '/berita?jenis=artikel', 'aktif' => false],
            ['label' => 'Berkah Zakat', 'url' => '/berita?jenis=berkah_zakat', 'aktif' => false],
        ]]) ?>
        <div class="mt-5">
            <?= $this->insert('components/carousel', [
                'label'    => 'Kabar terbaru',
                'autoplay' => 6000,
                'slides'   => array_map(fn (array $p): string => $this->insert('components/card-post', ['post' => $p]), $posts),
            ]) ?>
        </div>
        <?= $this->insert('components/pagination', ['paginator' => $paginator]) ?>
    </div>
</section>

<section class="<?= $blok ?>" aria-labelledby="k-video">
    <?= $this->insert('components/section-title', ['judul' => 'Video', 'id' => 'k-video', 'subjudul' => 'card-video (facade, tanpa iframe)']) ?>
    <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($video as $v): ?>
        <li><?= $this->insert('components/card-video', ['video' => $v]) ?></li>
        <?php endforeach ?>
    </ul>
</section>

<section class="bg-surface" aria-labelledby="k-testimoni">
    <div class="<?= $blok ?>">
        <?= $this->insert('components/section-title', ['judul' => 'Testimoni', 'id' => 'k-testimoni', 'subjudul' => 'Data contoh — tabel testimoni masih kosong']) ?>
        <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <?php foreach ($testimoni as $t): ?>
            <li><?= $this->insert('components/card-testimoni', ['testimoni' => $t]) ?></li>
            <?php endforeach ?>
        </ul>
    </div>
</section>

<section class="<?= $blok ?>" aria-labelledby="k-form">
    <?= $this->insert('components/section-title', ['judul' => 'Formulir, alert & empty state', 'id' => 'k-form']) ?>
    <div class="grid gap-8 lg:grid-cols-2">
        <form class="space-y-4" action="#" method="get">
            <?= $this->insert('components/form-field', ['name' => 'nama', 'label' => 'Nama lengkap', 'required' => true, 'autocomplete' => 'name']) ?>
            <?= $this->insert('components/form-field', ['name' => 'no_hp', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'required' => true, 'inputmode' => 'tel', 'hint' => 'Kami kirim konfirmasi ke nomor ini.', 'error' => 'Nomor WhatsApp wajib diisi.']) ?>
            <?= $this->insert('components/form-field', ['name' => 'jenis', 'label' => 'Jenis zakat', 'type' => 'select', 'options' => ['penghasilan' => 'Zakat Penghasilan', 'fitrah' => 'Zakat Fitrah', 'infaq' => 'Infaq']]) ?>
            <?= $this->insert('components/form-field', ['name' => 'pesan', 'label' => 'Pesan', 'type' => 'textarea']) ?>
            <?= $this->insert('components/form-field', ['name' => 'bukti', 'label' => 'Bukti transfer', 'type' => 'file', 'accept' => 'image/*,.pdf']) ?>
        </form>
        <div class="space-y-3">
            <?= $this->insert('components/alert', ['jenis' => 'sukses', 'judul' => 'Pembayaran tercatat', 'pesan' => 'Kode pembayaran Anda: AB12CD34.']) ?>
            <?= $this->insert('components/alert', ['jenis' => 'error', 'pesan' => 'Periksa kembali isian formulir.']) ?>
            <?= $this->insert('components/alert', ['jenis' => 'peringatan', 'pesan' => 'Transfer sesuai nominal beserta kode unik.']) ?>
            <?= $this->insert('components/alert', ['pesan' => 'Konfirmasi diverifikasi maksimal 1×24 jam kerja.']) ?>
            <?= $this->insert('components/empty-state', ['judul' => 'Belum ada laporan keuangan', 'pesan' => 'Laporan akan ditampilkan setelah diterbitkan.', 'ikon' => 'chart', 'aksi' => ['label' => 'Kembali ke beranda', 'href' => '/']]) ?>
        </div>
    </div>
</section>
