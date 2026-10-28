<?php
/**
 * /laporan — laporan keuangan (PDF).
 *
 * @var App\Core\View $this
 * @var list<array>   $laporan  id, judul, file, tahun, tgl
 */
$this->layout('site', [
    'title'       => 'Laporan Keuangan',
    'description' => 'Laporan keuangan penghimpunan dan penyaluran zakat, infaq, dan sedekah BAZNAS Kabupaten Bangka Selatan.',
]);
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Laporan Keuangan',
    'subjudul'   => 'Wujud transparansi dan akuntabilitas pengelolaan dana zakat, infaq, dan sedekah.',
    'breadcrumb' => [['label' => 'Laporan Keuangan']],
]) ?>

<div class="mx-auto max-w-4xl px-4 py-8 lg:py-12">
    <?php if ($laporan === []): ?>
    <?= $this->insert('components/empty-state', [
        'judul' => 'Laporan keuangan belum diterbitkan',
        'pesan' => 'Laporan akan tersedia untuk diunduh di halaman ini. Untuk informasi lebih lanjut, silakan hubungi kantor BAZNAS Bangka Selatan.',
        'ikon'  => 'chart',
        'aksi'  => ['label' => 'Hubungi kami', 'href' => '#kontak'],
    ]) ?>
    <?php else: ?>
    <ul class="divide-y divide-gray-200 overflow-hidden rounded-2xl border border-gray-200 bg-white">
        <?php foreach ($laporan as $l): ?>
        <?php $file = 'uploads/laporan/' . $l['file']; $ukuran = ukuran_file($file); ?>
        <li class="grid grid-cols-[auto_minmax(0,1fr)] items-start gap-x-3 gap-y-2 p-4 sm:grid-cols-[auto_minmax(0,1fr)_auto] sm:items-center">
            <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"><?= icon('chart', 'size-5') ?></span>
            <div>
                <h2 class="font-semibold text-ink"><?= e($l['judul']) ?></h2>
                <?php if ($l['tahun'] || $l['tgl']): ?>
                <p class="text-sm text-muted"><?= $l['tgl'] ? e(tgl_id($l['tgl'])) : 'Tahun ' . e($l['tahun']) ?></p>
                <?php endif ?>
            </div>
            <?php if ($ukuran !== null): ?>
            <a href="<?= e(url($file)) ?>" target="_blank" rel="noopener" type="application/pdf"
               class="col-start-2 inline-flex h-10 items-center gap-1.5 justify-self-start rounded-full border border-gray-300 px-3 text-sm font-semibold text-primary hover:border-primary sm:col-start-auto">
                <?= icon('download', 'size-4') ?>PDF <span class="font-normal text-muted"><?= e($ukuran) ?></span>
                <span class="sr-only">— <?= e($l['judul']) ?></span>
            </a>
            <?php endif ?>
        </li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>
</div>
