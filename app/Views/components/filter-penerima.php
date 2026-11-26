<?php
/**
 * Filter laporan penerima manfaat (bulan & tahun; hanya yang punya data). Pilihan langsung diterapkan oleh grafik.js; tombol untuk tanpa JS.
 *
 * @var array  $m      StatistikPenerimaManfaatService::untuk()
 * @var string $aksi   path tujuan, mis. 'admin'
 * @var string $jangkar id bagian
 * @var array<string, string>|null $pertahankan parameter lain yang ikut dikirim
 */

use App\Models\Pengumpulan;

$kelasInput = 'h-10 rounded-full border border-gray-300 bg-white px-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30';
?>
<form method="get" action="<?= e(url($aksi)) ?>#<?= e($jangkar) ?>" class="mt-4 flex flex-wrap items-center gap-2" data-filter-otomatis>
    <?php foreach ($pertahankan ?? [] as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach ?>
    <label for="pm-bulan" class="sr-only">Bulan laporan</label>
    <select id="pm-bulan" name="pm_bulan" class="<?= $kelasInput ?>">
        <option value="0"<?= $m['bulan'] === 0 ? ' selected' : '' ?>>Akumulasi setahun</option>
        <?php foreach ($m['daftarBulan'] as $no): ?>
        <option value="<?= $no ?>"<?= $m['bulan'] === $no ? ' selected' : '' ?>><?= e(Pengumpulan::BULAN[$no]) ?></option>
        <?php endforeach ?>
    </select>
    <label for="pm-tahun" class="sr-only">Tahun laporan</label>
    <select id="pm-tahun" name="pm_tahun" class="<?= $kelasInput ?>">
        <?php foreach ($m['daftarTahun'] as $th): ?>
        <option value="<?= $th ?>"<?= $m['tahun'] === $th ? ' selected' : '' ?>><?= $th ?></option>
        <?php endforeach ?>
    </select>
    <button type="submit" class="inline-flex h-10 items-center rounded-full bg-primary px-5 text-sm font-semibold text-white hover:bg-primary-dark">Terapkan</button>
</form>
