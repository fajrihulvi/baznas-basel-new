<?php
/**
 * /arsip — Arsip BAZNAS (tujuan menu "Arsip" di panel menu HP), pola situs BAZNAS kabupaten:
 * judul + Bagikan, daftar galeri (gambar kiri, jenis · judul · tanggal) + pagination, form cari judul (?q=),
 * lalu kartu "Ayo Tunaikan Zakat" (admin → Tunaikan Zakat (Beranda)).
 *
 * @var App\Core\View $this
 * @var string        $q
 * @var list<array>   $galeri    id, jenis, judul, foto, foto_w, foto_h, tgl
 * @var App\Core\Paginator $paginator
 * @var list<array>   $tunaikan
 */
$namaSitus = setting('site_name', 'BAZNAS Kabupaten Bangka Selatan');
$label = ['foto' => 'Foto', 'poster' => 'Poster', 'infografis' => 'Infografis'];
$this->layout('site', [
    'title'       => 'Arsip BAZNAS' . ($q !== '' ? ' — Cari "' . $q . '"' : '') . ($paginator->page > 1 ? ' — Halaman ' . $paginator->page : ''),
    'description' => 'Kumpulan arsip aksi, berita, dan informasi ' . $namaSitus . '.',
    'canonical'   => $paginator->url($paginator->page),
    'noindex'     => $q !== '', // hasil pencarian tidak diindeks
]);
?>
<section class="mx-auto max-w-4xl px-4 pt-8 text-center lg:pt-12" aria-labelledby="judul-arsip">
    <h1 id="judul-arsip" class="text-3xl font-extrabold text-primary lg:text-4xl">Arsip BAZNAS</h1>
    <p class="mt-2 text-base text-ink lg:text-lg">Kumpulan arsip aksi, berita, dan informasi BAZNAS.</p>
    <span class="mx-auto mt-5 block h-1 w-24 rounded-full bg-[#ef8a14]" aria-hidden="true"></span>
    <?= $this->insert('components/bagikan', ['tautan' => url('arsip'), 'teks' => 'Arsip BAZNAS — ' . $namaSitus, 'id' => 'bagikan-arsip']) ?>
</section>

<section class="mx-auto max-w-4xl px-4 py-8 lg:py-10" aria-label="Daftar arsip">
    <?php if ($q !== ''): ?>
    <p class="mb-4 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-muted" role="status">
        <span><?= e(number_format($paginator->total, 0, ',', '.')) ?> arsip untuk "<strong class="text-ink"><?= e($q) ?></strong>"</span>
        <a href="<?= e(url('arsip')) ?>" class="font-semibold text-primary hover:underline">Hapus pencarian</a>
    </p>
    <?php endif ?>

    <?php if ($galeri === []): ?>
    <?= $this->insert('components/empty-state', $q !== ''
        ? ['judul' => 'Arsip tidak ditemukan', 'pesan' => 'Coba kata lain atau lebih singkat.', 'ikon' => 'search', 'aksi' => ['label' => 'Lihat semua arsip', 'href' => '/arsip']]
        : ['judul' => 'Arsip masih kosong', 'pesan' => 'Dokumentasi kegiatan akan tampil di sini setelah diunggah.', 'ikon' => 'image', 'aksi' => ['label' => 'Lihat kabar terbaru', 'href' => '/berita']]) ?>
    <?php else: ?>
    <ul class="divide-y divide-gray-200">
        <?php foreach ($galeri as $i => $g): ?>
        <li>
            <a href="<?= e(url('uploads/galeri/' . rawurlencode($g['foto']))) ?>" target="_blank" rel="noopener" class="group flex items-start gap-3 py-4 sm:gap-5">
                <span class="block aspect-[2/1] w-32 shrink-0 overflow-hidden rounded-lg bg-surface sm:w-56">
                    <?= img($g['foto'], 'galeri', '', ['width' => $g['foto_w'], 'height' => $g['foto_h'], 'eager' => $i < 2,
                        'sizes' => '(min-width: 640px) 224px, 128px',
                        'class' => 'size-full object-cover transition-transform duration-300 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100']) ?>
                </span>
                <span class="min-w-0">
                    <span class="block text-xs font-semibold text-primary sm:text-sm"><?= e($label[$g['jenis']] ?? ucfirst((string) $g['jenis'])) ?></span>
                    <span class="mt-0.5 line-clamp-3 text-base font-medium text-ink group-hover:text-primary group-hover:underline sm:text-lg"><?= e($g['judul']) ?></span>
                    <span class="mt-1 flex items-center gap-1.5 text-sm text-muted">
                        <?= icon('calendar', 'size-4 shrink-0') ?><time datetime="<?= e($g['tgl']) ?>"><?= e(tgl_id($g['tgl'])) ?></time>
                    </span>
                </span>
            </a>
        </li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
    <?= $this->insert('components/pagination', ['paginator' => $paginator]) ?>

    <form action="<?= e(url('arsip')) ?>" method="get" role="search" class="mt-8 rounded-2xl border border-gray-200 bg-surface p-4 sm:p-5">
        <label for="cari-arsip" class="block font-bold text-ink">Cari arsip</label>
        <div class="mt-2 flex gap-2">
            <input id="cari-arsip" type="search" name="q" value="<?= e($q) ?>" maxlength="100" placeholder="Judul arsip, mis. santunan"
                   class="min-h-12 w-full min-w-0 flex-1 rounded-xl border border-gray-300 bg-white px-4 text-base focus:border-primary focus:ring-2 focus:ring-primary/30 focus:outline-none">
            <button type="submit" class="inline-flex min-h-12 shrink-0 items-center gap-2 rounded-xl bg-primary px-4 font-bold text-white hover:bg-primary-dark">
                <?= icon('search', 'size-5') ?><span class="sr-only sm:not-sr-only">Cari</span>
            </button>
        </div>
    </form>
</section>

<?php if ($tunaikan !== []): ?>
<section class="cv-auto mx-auto max-w-4xl px-4 pb-10 lg:pb-14" aria-labelledby="judul-ayo-tunaikan">
    <h2 id="judul-ayo-tunaikan" class="text-2xl font-bold tracking-wide text-primary uppercase lg:text-3xl">Ayo Tunaikan Zakat</h2>
    <span class="mt-2 block h-1 w-40 bg-[#ef8a14] lg:w-48" aria-hidden="true"></span>
    <ul class="mt-6 grid grid-cols-2 gap-3 sm:gap-6">
        <?php foreach ($tunaikan as $l): ?>
        <li><?= $this->insert('components/card-layanan', ['layanan' => $l]) ?></li>
        <?php endforeach ?>
    </ul>
</section>
<?php endif ?>
