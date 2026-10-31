<?php
/**
 * Satu field form admin berdasarkan tipenya (config/admin.php).
 * Nilai: isian lama (bila validasi gagal) → nilai baris (ubah) → nilai bawaan (tambah).
 *
 * @var App\Core\View      $this
 * @var App\Admin\Resource $r
 * @var string             $nama
 * @var array              $f
 * @var array|null         $baris
 * @var string|null        $awal   nilai awal dari URL (field 'dari_query')
 */

use App\Controllers\Admin\ResourceController;
use App\Core\Session;

$lama = Session::getFlash('_old');
$adaLama = is_array($lama);
$nilai = match (true) {
    $adaLama           => isset($lama[$nama]) ? (string) $lama[$nama] : '',
    $baris !== null    => (string) ($baris[$nama] ?? ''),
    isset($awal)       => $awal,
    default            => match ($f['bawaan'] ?? ($f['tipe'] === 'tanggal' ? 'hari_ini' : null)) {
        'hari_ini'   => date('Y-m-d'),
        'nama_admin' => ResourceController::namaAdmin(),
        null         => '',
        default      => (string) $f['bawaan'],
    },
};
$wajib = in_array('required', (array) (is_string($f['aturan'] ?? null) ? explode('|', $f['aturan']) : ($f['aturan'] ?? [])), true) || (!empty($f['wajib']) && $baris === null);
$id = 'f-' . $nama;
$error = galat($nama);
$deskripsi = trim((!empty($f['hint']) ? $id . '-hint ' : '') . ($error ? $id . '-error' : ''));
$kelasInput = 'block w-full rounded-xl border bg-white px-4 text-base text-ink focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 ' . ($error ? 'border-red-500' : 'border-gray-300');
$aria = ($deskripsi !== '' ? ' aria-describedby="' . e($deskripsi) . '"' : '') . ($error ? ' aria-invalid="true"' : '');
?>
<div>
    <?php if (in_array($f['tipe'], ['publish', 'centang'], true)): ?>
    <?php $centang = $adaLama ? !empty($lama[$nama]) : ($baris !== null ? ($f['tipe'] === 'publish' ? ($baris[$nama] ?? 'N') === 'Y' : (bool) ($baris[$nama] ?? 0)) : $f['tipe'] === 'publish'); ?>
    <label class="inline-flex cursor-pointer items-center gap-3 font-semibold">
        <input type="checkbox" name="<?= e($nama) ?>" value="1" class="size-5 accent-primary"<?= $centang ? ' checked' : '' ?>>
        <?= e($f['label']) ?>
    </label>
    <?php else: ?>
    <label for="<?= e($id) ?>" class="mb-1.5 block text-sm font-semibold text-ink">
        <?= e($f['label']) ?><?php if ($wajib): ?> <span class="text-red-700" aria-hidden="true">*</span><?php endif ?>
    </label>

    <?php if ($f['tipe'] === 'panjang'): ?>
    <textarea id="<?= e($id) ?>" name="<?= e($nama) ?>" rows="3"<?= $wajib ? ' required' : '' ?><?= $aria ?> class="<?= $kelasInput ?> py-3"><?= e($nilai) ?></textarea>

    <?php elseif ($f['tipe'] === 'html'): ?>
    <textarea id="<?= e($id) ?>" name="<?= e($nama) ?>" rows="14" data-editor<?= $aria ?> class="<?= $kelasInput ?> py-3 font-mono text-sm"><?= e($nilai) ?></textarea>

    <?php elseif ($f['tipe'] === 'pilihan'): ?>
    <select id="<?= e($id) ?>" name="<?= e($nama) ?>"<?= $wajib ? ' required' : '' ?><?= $aria ?> class="<?= $kelasInput ?> h-12">
        <option value=""><?= e($f['kosong'] ?? 'Pilih…') ?></option>
        <?php foreach ($r->pilihan($nama) as $kunci => $label): ?>
        <option value="<?= e((string) $kunci) ?>"<?= (string) $kunci === $nilai ? ' selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach ?>
    </select>

    <?php elseif (in_array($f['tipe'], ['gambar', 'pdf'], true)): ?>
    <?php if ($baris !== null && !empty($baris[$nama])): ?>
    <div class="mb-3 flex items-center gap-3 rounded-xl bg-surface p-3">
        <?php if ($f['tipe'] === 'gambar'): ?>
        <span class="block h-20 w-28 shrink-0 overflow-hidden rounded-lg bg-white"><?= img($baris[$nama], $f['folder'], '', ['sizes' => '112px', 'class' => 'size-full object-contain']) ?></span>
        <?php else: ?>
        <a href="<?= e(url('uploads/' . $f['folder'] . '/' . $baris[$nama])) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 font-semibold text-primary underline"><?= icon('document', 'size-5') ?>File saat ini</a>
        <?php endif ?>
        <div class="text-sm text-muted">
            <p>Pilih file baru untuk mengganti.</p>
            <?php if (empty($f['wajib'])): ?>
            <label class="mt-1 inline-flex items-center gap-2 font-semibold text-red-700"><input type="checkbox" name="hapus_<?= e($nama) ?>" value="1" class="size-4 accent-red-700">Hapus file ini</label>
            <?php endif ?>
        </div>
    </div>
    <?php endif ?>
    <input type="file" id="<?= e($id) ?>" name="<?= e($nama) ?>"<?= $wajib ? ' required' : '' ?><?= $aria ?>
           accept="<?= $f['tipe'] === 'gambar' ? 'image/jpeg,image/png,image/webp' : 'application/pdf' ?>"<?= $f['tipe'] === 'gambar' ? ' data-pratinjau' : '' ?>
           class="block w-full rounded-xl border border-dashed border-gray-300 bg-surface p-3 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-primary file:px-4 file:py-2 file:font-semibold file:text-white">
    <p class="mt-1.5 text-xs text-muted"><?= $f['tipe'] === 'gambar' ? 'JPG, PNG, atau WebP, maks ' . number_format(batas_unggah(ResourceController::MAKS_GAMBAR) / 1048576, 1, ',', '.') . ' MB. Ukuran & varian WebP dibuat otomatis.' : 'PDF, maks ' . number_format(batas_unggah(ResourceController::MAKS_PDF) / 1048576, 1, ',', '.') . ' MB.' ?></p>

    <?php elseif ($f['tipe'] === 'warna'): ?>
    <?php
    // Pemilih warna (#rrggbb) + centang "pakai warna bawaan" (input color selalu berisi nilai, jadi kosong = centang ini)
    $pakaiBawaan = $adaLama ? !empty($lama['bawaan_' . $nama]) : $nilai === '';
    $p = $f['pratinjau'] ?? null; // ['opasitas' => field angka pasangan, 'ikon' => [field gambar, folder]]
    ?>
    <div class="flex flex-wrap items-center gap-4">
        <input type="color" id="<?= e($id) ?>" name="<?= e($nama) ?>" value="<?= e($nilai !== '' ? $nilai : ($f['warna_awal'] ?? '#0b6b3a')) ?>"<?= $aria ?>
               class="h-12 w-20 cursor-pointer rounded-xl border border-gray-300 bg-white p-1">
        <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold">
            <input type="checkbox" name="bawaan_<?= e($nama) ?>" value="1" class="size-5 accent-primary"<?= $pakaiBawaan ? ' checked' : '' ?>>
            Pakai warna bawaan
        </label>
        <?php if ($p !== null): ?>
        <span class="ml-auto flex items-center gap-3 text-sm text-muted">Pratinjau
            <span class="grid size-18 place-items-center rounded-2xl border border-dashed border-gray-300 bg-primary/10"
                  data-pratinjau-warna="<?= e($nama) ?>" data-pratinjau-opasitas="<?= e($p['opasitas'] ?? '') ?>">
                <?php if ($baris !== null && !empty($p['ikon']) && !empty($baris[$p['ikon'][0]])): ?>
                <?= img($baris[$p['ikon'][0]], $p['ikon'][1], '', ['sizes' => '48px', 'class' => 'size-12 object-contain']) ?>
                <?php endif ?>
            </span>
        </span>
        <?php endif ?>
    </div>

    <?php elseif ($f['tipe'] === 'angka' && !empty($f['geser'])): ?>
    <?php [$min, $maks, $langkah, $awal] = $f['geser'] + [0, 100, 5, 100]; $nilaiGeser = $nilai !== '' ? $nilai : (string) $awal; ?>
    <div class="flex max-w-md items-center gap-4">
        <input type="range" id="<?= e($id) ?>" name="<?= e($nama) ?>" min="<?= (int) $min ?>" max="<?= (int) $maks ?>" step="<?= (int) $langkah ?>" value="<?= e($nilaiGeser) ?>"<?= $aria ?>
               class="h-12 min-w-0 flex-1 cursor-pointer accent-primary">
        <output for="<?= e($id) ?>" class="w-14 shrink-0 text-right font-semibold text-ink" style="font-variant-numeric: tabular-nums"><?= e($nilaiGeser) ?>%</output>
    </div>

    <?php else: ?>
    <?php
    if ($f['tipe'] === 'rupiah' && ctype_digit($nilai)) {
        $nilai = number_format((int) $nilai, 0, ',', '.'); // tampil 1.500.000
    }
    [$type, $extra] = match ($f['tipe']) {
        'angka'   => ['number', ' inputmode="numeric"'],
        'rupiah'  => ['text', ' inputmode="numeric" data-rupiah placeholder="Rp" autocomplete="off"'],
        'tanggal' => ['date', ''],
        'slug'    => ['text', ' pattern="[a-z0-9-]*" placeholder="otomatis"'],
        default   => ['text', ''],
    };
    ?>
    <input type="<?= $type ?>" id="<?= e($id) ?>" name="<?= e($nama) ?>" value="<?= e($nilai) ?>"<?= $wajib ? ' required' : '' ?><?= $aria ?><?= $extra ?> class="<?= $kelasInput ?> h-12">
    <?php endif ?>
    <?php endif ?>

    <?php if (!empty($f['hint'])): ?>
    <p id="<?= e($id) ?>-hint" class="mt-1.5 text-sm text-muted"><?= e($f['hint']) ?></p>
    <?php endif ?>
    <?php if ($error): ?>
    <p id="<?= e($id) ?>-error" class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-red-700"><?= icon('alert', 'size-4 shrink-0') ?><?= e($error) ?></p>
    <?php endif ?>
</div>
