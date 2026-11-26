<?php
/**
 * Laporan penerima manfaat di dasbor (juga untuk Kepala).
 *
 * @var App\Core\View $this
 * @var array|null $m  StatistikPenerimaManfaatService::untuk(); null = tabel belum dimigrasi
 * @var array<string, string> $pertahankan parameter filter lain di dasbor yang ikut dikirim ulang
 */

use App\Core\Auth;

$kepala = Auth::kepala();
?>
<section id="penerima-manfaat" class="mt-10 scroll-mt-6 border-t border-gray-200 pt-8" aria-labelledby="judul-penerima">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 id="judul-penerima" class="text-xl font-bold">Penerima Manfaat</h2>
            <p class="text-sm text-muted">Jumlah jiwa penerima manfaat per program<?= $m !== null ? ' · ' . e($m['label']) : '' ?></p>
        </div>
        <?php if (!$kepala && $m !== null): ?>
        <?= $this->insert('components/button', ['label' => 'Catat penerima manfaat', 'href' => '/admin/penerima-manfaat/baru', 'size' => 'sm', 'variant' => 'outline']) ?>
        <?php endif ?>
    </div>
    <?php if ($m === null): ?>
    <div class="mt-4"><?= $this->insert('components/alert', ['jenis' => 'peringatan', 'judul' => 'Tabel penerima manfaat belum tersedia',
        'pesan' => 'Jalankan migrasi database 020_penerima_manfaat.sql agar menu & laporan Penerima Manfaat bisa dipakai.']) ?></div>
    <?php elseif ($m['total'] === 0): ?>
    <div class="mt-6"><?= $this->insert('components/empty-state', ['judul' => 'Belum ada data penerima manfaat', 'ikon' => 'users',
        'pesan' => $kepala ? 'Jumlah penerima manfaat dicatat oleh admin melalui menu Penyaluran → Penerima Manfaat.' : 'Catat jumlah penerima manfaat tiap bulan di menu Penyaluran → Penerima Manfaat.',
        'aksi' => $kepala ? null : ['label' => 'Catat penerima manfaat', 'href' => '/admin/penerima-manfaat/baru']]) ?></div>
    <?php else: ?>
    <?= $this->insert('components/filter-penerima', ['m' => $m, 'aksi' => 'admin', 'jangkar' => 'penerima-manfaat', 'pertahankan' => $pertahankan ?? []]) ?>
    <?php if (!$kepala && $m['penghimpunan'] === null && $m['penghimpunanTerisi'] > 0): ?>
    <p class="mt-3 text-sm text-muted">Total penghimpunan belum ditampilkan: baru diisi untuk <?= $m['penghimpunanTerisi'] ?> dari <?= $m['bulanTercatat'] ?> bulan.</p>
    <?php endif ?>
    <div class="mt-5"><?= $this->insert('components/laporan-penerima', ['m' => $m, 'id' => 'dasbor-penerima']) ?></div>
    <?php endif ?>
</section>
