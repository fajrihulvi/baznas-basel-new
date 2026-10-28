<?php
/**
 * Form tambah/ubah generik.
 *
 * @var App\Core\View      $this
 * @var App\Admin\Resource $r
 * @var array|null         $baris  null = tambah
 * @var array<string, string> $awal nilai awal dari URL (field 'dari_query'), hanya saat tambah
 */
$awal ??= [];
$baru = $baris === null;
$judul = ($baru ? 'Tambah ' : 'Ubah ') . $r->tunggal();
$this->layout('admin', ['title' => $judul]);
$aksi = $baru ? 'admin/' . $r->kunci : 'admin/' . $r->kunci . '/' . (int) $baris[$r->pk()];
$adaFile = (bool) array_filter($r->field(), static fn (array $f): bool => in_array($f['tipe'], ['gambar', 'pdf'], true));
?>
<nav aria-label="Breadcrumb" class="text-sm text-muted">
    <a href="<?= e(url('admin/' . $r->kunci)) ?>" class="hover:text-primary hover:underline"><?= e($r->judul()) ?></a> › <span aria-current="page"><?= e($judul) ?></span>
</nav>
<div class="mt-2 flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-bold first-letter:uppercase"><?= e($judul) ?></h1>
    <?php if (!$baru && ($tautan = $r->tautan($baris))): ?>
    <a href="<?= e(url($tautan)) ?>" target="_blank" rel="noopener" class="text-sm font-semibold text-primary underline">Lihat di situs ↗</a>
    <?php endif ?>
</div>

<form method="post" action="<?= e(url($aksi)) ?>"<?= $adaFile ? ' enctype="multipart/form-data"' : '' ?>
      class="mt-6 max-w-3xl space-y-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:p-8">
    <?= csrf_field() ?>
    <?php foreach ($r->field() as $nama => $f): ?>
    <?= $this->insert('admin/field', ['r' => $r, 'nama' => $nama, 'f' => $f, 'baris' => $baris, 'awal' => $awal[$nama] ?? null]) ?>
    <?php endforeach ?>

    <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 pt-6">
        <button type="submit" class="inline-flex min-h-12 items-center rounded-full bg-primary px-6 font-bold text-white hover:bg-primary-dark">Simpan</button>
        <a href="<?= e(url('admin/' . $r->kunci)) ?>" class="inline-flex min-h-12 items-center rounded-full px-5 font-semibold text-muted hover:text-primary">Batal</a>
    </div>
</form>
