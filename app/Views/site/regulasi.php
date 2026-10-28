<?php
/**
 * /regulasi — dokumen peraturan per kategori (?kategori=id).
 *
 * @var App\Core\View $this
 * @var list<array>   $kategori  id, nama, jumlah
 * @var array|null    $aktif
 * @var list<array>   $dokumen   id, judul, file, id_kategori, kategori
 */
$judul = $aktif ? $aktif['nama'] : 'Regulasi Zakat';
$this->layout('site', [
    'title'       => $aktif ? 'Regulasi: ' . $aktif['nama'] : 'Regulasi Zakat',
    'description' => 'Undang-undang, peraturan menteri, keputusan BAZNAS, fatwa MUI, dan putusan MK tentang pengelolaan zakat — unduh dokumen PDF.',
    'canonical'   => url('regulasi', $aktif ? ['kategori' => $aktif['id']] : []),
]);

$grup = [];
foreach ($dokumen as $d) {
    $grup[$d['kategori']][] = $d;
}
?>
<?= $this->insert('components/page-header', [
    'judul'      => $judul,
    'subjudul'   => 'Dasar hukum pengelolaan zakat, infaq, dan sedekah di Indonesia.',
    'breadcrumb' => $aktif ? [['label' => 'Regulasi', 'url' => '/regulasi'], ['label' => $aktif['nama']]] : [['label' => 'Regulasi']],
]) ?>

<div class="mx-auto max-w-6xl px-4 py-8 lg:py-12">
    <?= $this->insert('components/chip-tabs', [
        'label' => 'Kategori regulasi',
        'items' => [
            ['label' => 'Semua', 'url' => '/regulasi', 'aktif' => $aktif === null],
            ...array_map(static fn (array $k): array => [
                'label' => $k['nama'] . ' (' . $k['jumlah'] . ')',
                'url'   => '/regulasi?kategori=' . $k['id'],
                'aktif' => $aktif !== null && $aktif['id'] === $k['id'],
            ], $kategori),
        ],
    ]) ?>

    <div class="mt-6 space-y-8">
        <?php if ($dokumen === []): ?>
        <?= $this->insert('components/empty-state', ['judul' => 'Belum ada dokumen di kategori ini', 'ikon' => 'scale', 'aksi' => ['label' => 'Lihat semua regulasi', 'href' => '/regulasi']]) ?>
        <?php endif ?>

        <?php foreach ($grup as $namaKategori => $daftar): ?>
        <section aria-labelledby="kat-<?= (int) $daftar[0]['id_kategori'] ?>">
            <?php if ($aktif === null): ?>
            <h2 id="kat-<?= (int) $daftar[0]['id_kategori'] ?>" class="mb-3 text-lg font-bold text-ink"><?= e($namaKategori) ?></h2>
            <?php else: ?>
            <h2 id="kat-<?= (int) $daftar[0]['id_kategori'] ?>" class="sr-only"><?= e($namaKategori) ?></h2>
            <?php endif ?>
            <ul class="divide-y divide-gray-200 overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <?php foreach ($daftar as $d): ?>
                <?php $file = 'uploads/perundangan/' . $d['file']; $ukuran = ukuran_file($file); ?>
                <li class="grid grid-cols-[auto_minmax(0,1fr)] items-start gap-x-3 gap-y-2 p-4 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center">
                    <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"><?= icon('document', 'size-5') ?></span>
                    <p class="text-sm font-medium text-ink sm:text-base"><?= e($d['judul']) ?></p>
                    <?php if ($ukuran !== null): ?>
                    <a href="<?= e(url($file)) ?>" target="_blank" rel="noopener" type="application/pdf"
                       class="col-start-2 inline-flex h-10 items-center gap-1.5 justify-self-start rounded-full border border-gray-300 px-3 text-sm font-semibold text-primary hover:border-primary sm:col-start-auto">
                        <?= icon('download', 'size-4') ?><span>PDF <span class="font-normal text-muted"><?= e($ukuran) ?></span></span>
                        <span class="sr-only">— <?= e($d['judul']) ?></span>
                    </a>
                    <?php endif ?>
                </li>
                <?php endforeach ?>
            </ul>
        </section>
        <?php endforeach ?>
    </div>
</div>
