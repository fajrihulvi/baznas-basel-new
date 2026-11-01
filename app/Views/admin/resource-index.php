<?php
/**
 * Daftar generik konten admin.
 *
 * @var App\Core\View      $this
 * @var App\Admin\Resource $r
 * @var string             $q
 * @var list<array>        $baris
 * @var App\Core\Paginator $paginator
 */
$this->layout('admin', ['title' => $r->judul()]);
$field = $r->field();
$kolomUtama = array_search('teks', array_diff_key($r->kolom(), [$r->pk() => true]), true); // kolom teks pertama (selain ID) ditebalkan
$labelKolom = $r->labelKolom();
$px = count($r->kolom()) >= 7 ? 'px-3' : 'px-4'; // tabel berkolom banyak: sel lebih rapat agar tombol aksi tetap terlihat di 1280px
?>
<div class="flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-bold"><?= e($r->judul()) ?></h1>
    <?php if ($r->boleh('tambah')): ?>
    <?= $this->insert('components/button', ['label' => 'Tambah ' . $r->tunggal(), 'href' => '/admin/' . $r->kunci . '/baru', 'size' => 'sm', 'class' => 'ml-auto']) ?>
    <?php endif ?>
</div>

<?php if ($r->keterangan()): ?>
<p class="mt-2 max-w-3xl text-sm text-muted"><?= e($r->keterangan()) ?></p>
<?php endif ?>

<form method="get" action="<?= e(url('admin/' . $r->kunci)) ?>" role="search" class="mt-5 flex max-w-md gap-2">
    <label for="cari-admin" class="sr-only">Cari</label>
    <input id="cari-admin" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari…"
           class="h-11 min-w-0 flex-1 rounded-full border border-gray-300 bg-white px-4 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
    <button type="submit" class="inline-flex h-11 items-center rounded-full bg-primary px-4 font-semibold text-white hover:bg-primary-dark">Cari</button>
</form>
<p class="mt-3 text-sm text-muted"><?= $paginator->total ?> data<?= $q !== '' ? ' untuk “' . e($q) . '”' : '' ?></p>

<?php if ($baris === []): ?>
<div class="mt-4"><?= $this->insert('components/empty-state', ['judul' => $q !== '' ? 'Tidak ditemukan' : 'Belum ada data', 'ikon' => 'document',
    'aksi' => $r->boleh('tambah') && $q === '' ? ['label' => 'Tambah ' . $r->tunggal(), 'href' => '/admin/' . $r->kunci . '/baru'] : null]) ?></div>
<?php else: ?>
<div class="relative mt-4 overflow-x-auto rounded-2xl border border-gray-200 bg-white">
    <table class="w-full min-w-[640px] text-left text-sm">
        <thead class="border-b border-gray-200 bg-surface text-xs uppercase tracking-wide text-muted">
            <tr>
                <?php foreach ($r->kolom() as $kolom => $tipe): ?>
                <th scope="col" class="<?= $px ?> py-3 font-semibold<?= $tipe === 'gambar' ? ' w-20' : '' ?>"><?= $tipe === 'gambar' ? '<span class="sr-only">Gambar</span>' : e($labelKolom[$kolom] ?? $field[$kolom]['label'] ?? ucfirst(str_replace('_', ' ', $kolom))) ?></th>
                <?php endforeach ?>
                <th scope="col" class="<?= $px ?> py-3 text-right font-semibold"><span class="sr-only">Aksi</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php foreach ($baris as $b): ?>
            <?php $id = (int) $b[$r->pk()]; ?>
            <tr class="align-middle hover:bg-surface/60">
                <?php foreach ($r->kolom() as $kolom => $tipe): ?>
                <td class="<?= $px ?> py-3">
                    <?php $nilai = $b[$kolom] ?? null; ?>
                    <?php if ($tipe === 'gambar'): ?>
                    <span class="block size-14 overflow-hidden rounded-lg bg-surface">
                        <?php if ($nilai): ?><?= img($nilai, $field[$kolom]['folder'], '', ['sizes' => '56px', 'class' => 'size-full object-cover']) ?><?php endif ?>
                    </span>
                    <?php elseif ($tipe === 'publish'): ?>
                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $nilai === 'Y' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-700' ?>"><?= $nilai === 'Y' ? 'Tampil' : 'Draf' ?></span>
                    <?php elseif ($tipe === 'centang'): ?>
                    <?= $nilai ? '<span class="font-semibold text-primary">Ya</span>' : '<span class="text-muted">—</span>' ?>
                    <?php elseif ($tipe === 'tanggal'): ?>
                    <span class="whitespace-nowrap"><?= e(tgl_id($nilai)) ?></span>
                    <?php elseif ($tipe === 'bulan'): ?>
                    <span class="whitespace-nowrap font-semibold text-ink"><?= e(App\Models\Pengumpulan::namaPeriode($nilai)) ?></span>
                    <?php elseif ($tipe === 'rupiah' && $nilai === null): ?>
                    <span class="text-muted">—</span>
                    <?php elseif ($tipe === 'rupiah'): ?>
                    <span class="whitespace-nowrap font-semibold text-ink" style="font-variant-numeric: tabular-nums"><?= e(format_rupiah($nilai)) ?></span>
                    <?php elseif ($tipe === 'pilihan'): ?>
                    <?= e($r->pilihanDaftar($kolom)[(string) $nilai] ?? (string) $nilai) ?>
                    <?php else: ?>
                    <span class="line-clamp-2<?= $kolom === $kolomUtama ? ' font-semibold text-ink' : '' ?>"><?= e((string) $nilai) ?></span>
                    <?php endif ?>
                </td>
                <?php endforeach ?>
                <td class="<?= $px ?> py-3">
                    <div class="flex justify-end gap-1">
                        <?php if ($tautan = $r->tautan($b)): ?>
                        <a href="<?= e(url($tautan)) ?>" target="_blank" rel="noopener" class="inline-flex h-9 items-center rounded-full px-3 font-semibold text-muted hover:text-primary">Lihat<span class="sr-only"> di situs</span></a>
                        <?php endif ?>
                        <?php foreach ($r->aksiBaris($b) as $label => $tujuan): ?>
                        <a href="<?= e(url($tujuan)) ?>" class="inline-flex h-9 items-center whitespace-nowrap rounded-full border border-gray-300 px-3 font-semibold hover:border-primary hover:text-primary"><?= e($label) ?></a>
                        <?php endforeach ?>
                        <?php if ($r->boleh('ubah')): ?>
                        <a href="<?= e(url('admin/' . $r->kunci . '/' . $id . '/ubah')) ?>" class="inline-flex h-9 items-center rounded-full border border-gray-300 px-3 font-semibold hover:border-primary hover:text-primary">Ubah</a>
                        <?php endif ?>
                        <?php if ($r->boleh('hapus')): ?>
                        <form method="post" action="<?= e(url('admin/' . $r->kunci . '/' . $id . '/hapus')) ?>" data-konfirmasi="Hapus <?= e($r->tunggal()) ?> ini? Tindakan ini tidak dapat dibatalkan.">
                            <?= csrf_field() ?>
                            <button type="submit" class="inline-flex h-9 items-center rounded-full px-3 font-semibold text-red-700 hover:bg-red-50">Hapus</button>
                        </form>
                        <?php endif ?>
                    </div>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>

<?= $this->insert('components/pagination', ['paginator' => $paginator]) ?>
