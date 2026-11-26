<?php
/**
 * /donasi/{slug} — detail program donasi: gambar, terkumpul vs target, pilih nominal (GET ke formulir), deskripsi, doa donatur.
 * Tanpa form POST → tanpa cookie sesi. Tombol nominal cepat = tombol submit (jalan tanpa JS).
 *
 * @var App\Core\View $this
 * @var array       $p   DonasiService::program() satu baris
 * @var list<array> $doa nama (atau "Hamba Allah"), doa, waktu — donatur terverifikasi yang mengizinkan
 */

use App\Services\DonasiService;

$namaSitus = setting('site_name', 'BAZNAS Kabupaten Bangka Selatan');
$this->layout('site', [
    'title'       => $p['nama'],
    'description' => ringkas($p['nama'] . ' — ' . ($p['deskripsi'] ? strip_tags($p['deskripsi']) : 'program donasi ' . $namaSitus . '.')),
    'image'       => url('uploads/banner/' . rawurlencode($p['foto'])),
]);
$berjalan = $p['status'] === 'berjalan';
$rp = static fn (int $n): string => number_format($n, 0, ',', '.');
?>
<div class="mx-auto max-w-3xl pb-10 lg:px-4 lg:pt-8 lg:pb-14">
    <div class="aspect-[16/7] overflow-hidden bg-surface lg:rounded-2xl">
        <?= img($p['foto'], 'banner', $p['judul'], ['eager' => true, 'sizes' => '(min-width: 768px) 736px, 100vw', 'class' => 'size-full object-cover']) ?>
    </div>

    <div class="px-4 lg:px-0">
        <div class="mt-4"><?= $this->insert('components/breadcrumb', ['items' => [['label' => 'Program Donasi', 'url' => '/donasi'], ['label' => $p['nama']]]]) ?></div>
        <h1 class="mt-2 text-2xl font-bold text-ink lg:text-3xl"><?= e($p['nama']) ?></h1>
        <p class="mt-2 text-ink" style="font-variant-numeric: tabular-nums">
            <strong class="text-lg text-primary"><?= e(format_rupiah($p['terkumpul'])) ?></strong> terkumpul dari <strong><?= e($rp($p['donatur'])) ?></strong> donatur
        </p>

        <div class="mt-4 rounded-2xl border border-gray-200 bg-white p-4 sm:p-5">
            <dl class="grid grid-cols-2 gap-4" style="font-variant-numeric: tabular-nums">
                <div class="min-w-0"><dt class="text-sm text-muted">Terkumpul</dt><dd class="text-[15px] font-bold break-words text-ink min-[400px]:text-lg sm:text-2xl"><?= e(format_rupiah($p['terkumpul'])) ?></dd></div>
                <div class="min-w-0 text-right"><dt class="text-sm text-muted">Target</dt><dd class="text-[15px] font-bold break-words text-ink min-[400px]:text-lg sm:text-2xl"><?= e(format_rupiah($p['target'])) ?></dd></div>
            </dl>
            <div class="mt-3 h-3 overflow-hidden rounded-full bg-gray-200" role="progressbar" aria-label="Progres donasi" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) round($p['persen']) ?>">
                <span class="block h-full rounded-full bg-accent" style="width: <?= e(number_format($p['persen'], 1, '.', '')) ?>%"></span>
            </div>
            <p class="mt-2 flex flex-wrap justify-between gap-x-3 text-sm text-muted">
                <span><?= e(number_format($p['persen'], 1, ',', '.')) ?>% dari target</span>
                <span><?= icon('calendar', 'mr-1 inline size-4 align-[-3px]') ?><?= e(tgl_id($p['mulai'])) ?> – <?= e(tgl_id($p['selesai'])) ?> · <strong class="text-ink"><?= e(DonasiService::keteranganWaktu($p)) ?></strong></span>
            </p>
        </div>

        <?php if ($berjalan): ?>
        <form method="get" action="<?= e(url('donasi/' . $p['slug'] . '/bayar')) ?>" class="mt-6" data-donasi-nominal>
            <label for="nominal" class="block font-semibold text-ink">Masukkan nominal donasi <span class="text-red-700" aria-hidden="true">*</span></label>
            <div class="mt-2 flex h-14 items-center rounded-xl border border-gray-300 bg-white px-4 focus-within:border-primary focus-within:ring-2 focus-within:ring-primary/30">
                <span class="text-xl font-bold text-ink">Rp</span>
                <input id="nominal" name="nominal" inputmode="numeric" autocomplete="off" required data-rupiah
                       value="<?= e($rp($p['minimal'])) ?>" aria-describedby="nominal-minimal"
                       class="min-w-0 flex-1 bg-transparent text-right text-2xl font-bold text-ink focus:outline-none" style="font-variant-numeric: tabular-nums">
            </div>
            <p id="nominal-minimal" class="mt-1.5 text-center text-sm text-muted">Minimal donasi <?= e(format_rupiah($p['minimal'])) ?></p>
            <div class="mt-3 grid grid-cols-2 gap-2 sm:grid-cols-4">
                <?php foreach (DonasiService::nominalCepat($p['minimal']) as $n): ?>
                <button type="submit" name="nominal" value="<?= $n ?>" data-nominal-cepat="<?= $n ?>"
                        class="h-12 rounded-xl bg-surface px-2 text-sm font-semibold text-ink ring-1 ring-gray-200 hover:bg-primary/10 hover:ring-primary"><?= e(format_rupiah($n)) ?></button>
                <?php endforeach ?>
            </div>
            <button type="submit" class="mt-5 inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-full bg-primary px-7 text-lg font-bold text-white hover:bg-primary-dark">
                Tunaikan Sekarang <?= icon('heart', 'size-5') ?>
            </button>
        </form>
        <?php else: ?>
        <div class="mt-6"><?= $this->insert('components/alert', $p['status'] === 'selesai'
            ? ['jenis' => 'info', 'judul' => 'Program donasi telah ditutup', 'pesan' => 'Terima kasih kepada seluruh donatur. Anda tetap dapat berinfaq dan bersedekah melalui menu Bayar Zakat.']
            : ['jenis' => 'info', 'judul' => 'Program donasi belum dibuka', 'pesan' => 'Donasi dapat ditunaikan mulai ' . tgl_id($p['mulai']) . '.']) ?></div>
        <?php endif ?>

        <?php if ($p['deskripsi']): ?>
        <section class="mt-8" aria-labelledby="judul-tentang-donasi">
            <h2 id="judul-tentang-donasi" class="text-lg font-bold text-ink"><?= e($p['nama']) ?></h2>
            <div class="relative mt-3" data-ringkas>
                <div class="konten text-ink" data-ringkas-isi><?= $p['deskripsi'] /* HTML tersanitasi saat disimpan admin */ ?></div>
            </div>
        </section>
        <?php endif ?>

        <?php if ($doa !== []): ?>
        <section class="mt-8" aria-labelledby="judul-doa">
            <h2 id="judul-doa" class="text-lg font-bold text-ink">Doa para donatur</h2>
            <ul class="mt-3 space-y-3">
                <?php foreach ($doa as $d): ?>
                <li class="rounded-2xl bg-surface p-4">
                    <p class="font-semibold text-ink"><?= e($d['nama']) ?> <span class="text-sm font-normal text-muted">· <?= e(tgl_id(substr((string) $d['waktu'], 0, 10))) ?></span></p>
                    <p class="mt-1 text-ink"><?= e($d['doa']) ?></p>
                </li>
                <?php endforeach ?>
            </ul>
        </section>
        <?php endif ?>

        <?= $this->insert('components/bagikan', ['tautan' => url('donasi/' . $p['slug']), 'teks' => $p['nama'] . ' — ' . $namaSitus, 'id' => 'bagikan-donasi']) ?>
    </div>
</div>

<?php $this->start('scripts') ?>
<script type="module" src="<?= e(asset('js/donasi.js')) ?>"></script>
<?php $this->stop() ?>
