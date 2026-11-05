<?php
/**
 * Form impor pengumpulan dari Excel.
 *
 * @var App\Core\View $this
 * @var list<string>  $galat kesalahan per baris dari impor sebelumnya
 */

use App\Admin\Resource;
use App\Controllers\Admin\PengumpulanImporController;
use App\Core\Session;
use App\Services\ImporPengumpulanService as Impor;

$this->layout('admin', ['title' => 'Impor Excel pengumpulan']);
$r = Resource::cari('pengumpulan');
$lama = Session::getFlash('_old');
$modeDipilih = is_array($lama) ? (string) ($lama['mode'] ?? 'lewati') : 'lewati';
$errBerkas = galat('berkas');
$errMode = galat('mode');
?>
<nav aria-label="Breadcrumb" class="text-sm text-muted">
    <a href="<?= e(url('admin/pengumpulan')) ?>" class="hover:text-primary hover:underline">Pengumpulan</a> › <span aria-current="page">Impor Excel</span>
</nav>
<h1 class="mt-2 text-2xl font-bold">Impor pengumpulan dari Excel</h1>
<p class="mt-1 max-w-3xl text-sm text-muted">Catat setoran banyak Perangkat Daerah sekaligus untuk satu bulan. Seluruh file diperiksa lebih dulu — bila ada baris yang salah, tidak ada data yang disimpan.</p>

<ol class="mt-6 grid max-w-3xl gap-3 text-sm sm:grid-cols-3">
    <li class="rounded-2xl border border-gray-200 bg-white p-4"><span class="font-bold text-primary">1.</span> Unduh format Excel berisi semua Perangkat Daerah aktif.
        <a href="<?= e(url('admin/pengumpulan/format-impor')) ?>" class="mt-3 inline-flex min-h-10 items-center gap-2 rounded-full border border-primary px-4 font-semibold text-primary hover:bg-primary hover:text-white" download>
            <?= icon('document', 'size-4') ?>Unduh format Excel</a></li>
    <li class="rounded-2xl border border-gray-200 bg-white p-4"><span class="font-bold text-primary">2.</span> Isi nominal Zakat, Infaq/Sedekah, dan DSKL per PD (angka saja, mis. 1500000). Biarkan kosong untuk PD yang belum menyetor.</li>
    <li class="rounded-2xl border border-gray-200 bg-white p-4"><span class="font-bold text-primary">3.</span> Pilih bulan setoran, lalu unggah file di bawah ini.</li>
</ol>

<?php if ($galat !== []): ?>
<section class="mt-6 max-w-3xl rounded-2xl border border-red-200 bg-red-50 p-5" aria-labelledby="judul-galat-impor">
    <h2 id="judul-galat-impor" class="flex items-center gap-2 font-bold text-red-800"><?= icon('alert', 'size-5') ?>Kesalahan di file (<?= count($galat) ?>)</h2>
    <ul class="mt-2 list-disc space-y-1 pl-6 text-sm text-red-800">
        <?php foreach ($galat as $g): ?><li><?= e($g) ?></li><?php endforeach ?>
    </ul>
</section>
<?php endif ?>

<form method="post" action="<?= e(url('admin/pengumpulan/impor')) ?>" enctype="multipart/form-data"
      class="mt-6 max-w-3xl space-y-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:p-8">
    <?= csrf_field() ?>
    <?= $this->insert('admin/field', ['r' => $r, 'nama' => 'periode', 'baris' => null, 'f' => ['tipe' => 'bulan', 'label' => 'Bulan dan tahun setoran', 'aturan' => 'required',
        'hint' => 'Berlaku untuk semua baris di file.']]) ?>
    <?= $this->insert('admin/field', ['r' => $r, 'nama' => 'tgl', 'baris' => null, 'f' => ['tipe' => 'tanggal', 'label' => 'Tanggal pengumpulan', 'aturan' => 'required',
        'hint' => 'Dipakai untuk baris yang kolom "Tanggal Pengumpulan"-nya kosong.']]) ?>

    <fieldset<?= $errMode ? ' aria-describedby="f-mode-error"' : '' ?>>
        <legend class="mb-2 text-sm font-semibold text-ink">Bila Perangkat Daerah sudah punya data pada bulan tersebut</legend>
        <div class="space-y-2">
            <?php foreach (Impor::MODE as $k => $label): ?>
            <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-gray-200 p-3 text-sm has-checked:border-primary has-checked:bg-primary/5">
                <input type="radio" name="mode" value="<?= e($k) ?>" class="mt-0.5 size-4 accent-primary"<?= $modeDipilih === $k ? ' checked' : '' ?>>
                <span><?= e($label) ?><?= $k === 'lewati' ? ' <span class="text-muted">— disarankan, mencegah data ganda bila file diimpor dua kali</span>' : '' ?></span>
            </label>
            <?php endforeach ?>
        </div>
        <?php if ($errMode): ?><p id="f-mode-error" class="mt-1.5 text-sm font-medium text-red-700"><?= e($errMode) ?></p><?php endif ?>
    </fieldset>

    <div>
        <label for="f-berkas" class="mb-1.5 block text-sm font-semibold text-ink">File Excel (.xlsx) <span class="text-red-700" aria-hidden="true">*</span></label>
        <input type="file" id="f-berkas" name="berkas" required accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
               aria-describedby="f-berkas-hint<?= $errBerkas ? ' f-berkas-error' : '' ?>"<?= $errBerkas ? ' aria-invalid="true"' : '' ?>
               class="block w-full rounded-xl border border-dashed <?= $errBerkas ? 'border-red-500' : 'border-gray-300' ?> bg-surface p-3 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-primary file:px-4 file:py-2 file:font-semibold file:text-white">
        <p id="f-berkas-hint" class="mt-1.5 text-sm text-muted">Format .xlsx, maks <?= e(number_format(batas_unggah(PengumpulanImporController::MAKS_FILE) / 1048576, 1, ',', '.')) ?> MB. Baris dikenali dari kolom ID atau nama/singkatan Perangkat Daerah.</p>
        <?php if ($errBerkas): ?>
        <p id="f-berkas-error" class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-red-700"><?= icon('alert', 'size-4 shrink-0') ?><?= e($errBerkas) ?></p>
        <?php endif ?>
    </div>

    <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 pt-6">
        <button type="submit" class="inline-flex min-h-12 items-center rounded-full bg-primary px-6 font-bold text-white hover:bg-primary-dark">Impor</button>
        <a href="<?= e(url('admin/pengumpulan')) ?>" class="inline-flex min-h-12 items-center rounded-full px-5 font-semibold text-muted hover:text-primary">Batal</a>
    </div>
</form>
