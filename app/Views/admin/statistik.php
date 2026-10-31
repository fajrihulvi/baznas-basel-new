<?php
/**
 * Statistik zakat masuk (dasbor): filter periode → kartu ringkasan → tren per kelompok → per jenis → kumulatif tahunan.
 * Semua bagian mengikuti satu filter di atasnya.
 *
 * @var App\Core\View $this
 * @var array $s hasil StatistikZakatService::untuk()
 */

use App\Services\StatistikZakatService as Statistik;

$periode = $s['periode'];
$r = $s['ringkasan'];
$tren = $s['tren'];
$perJenis = $s['perJenis'];
$tahunan = $s['tahunan'];
$satuan = ['hari' => 'per hari', 'bulan' => 'per bulan', 'tahun' => 'per tahun'][$periode['satuan']];
$kosong = $r['total'] === 0;

// Perubahan vs periode sebelumnya (naik = baik)
$delta = null;
if ($r['total_lalu'] !== null) {
    if ($r['total_lalu'] > 0) {
        $persen = ($r['total'] - $r['total_lalu']) / $r['total_lalu'] * 100;
        $delta = [$persen >= 0, ($persen >= 0 ? '+' : '−') . number_format(abs($persen), abs($persen) < 10 ? 1 : 0, ',', '.') . '%'];
    } elseif ($r['total'] > 0) {
        $delta = [true, 'baru'];
    }
}
$kelasInput = 'h-10 rounded-full border border-gray-300 bg-white px-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30';
$adaTahunan = array_sum($tahunan['lalu']) > 0 || array_filter($tahunan['ini']) !== [];
?>
<section id="statistik" class="mt-8 scroll-mt-6" aria-labelledby="judul-statistik">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 id="judul-statistik" class="text-xl font-bold">Zakat masuk</h2>
            <p class="text-sm text-muted">Pembayaran berstatus <strong class="font-semibold text-ink">Selesai</strong> (sudah diverifikasi), menurut tanggal pembayaran · <?= e($periode['label']) ?></p>
        </div>
    </div>

    <?php /* Satu baris filter di atas semua yang dicakupnya */ ?>
    <form method="get" action="<?= e(url('admin')) ?>#statistik" class="mt-4 flex flex-wrap items-center gap-2" data-filter-statistik>
        <label for="f-periode" class="sr-only">Periode</label>
        <select id="f-periode" name="periode" class="<?= $kelasInput ?>">
            <?php foreach (Statistik::PRESET as $k => $label): ?>
            <option value="<?= e($k) ?>"<?= $periode['kunci'] === $k ? ' selected' : '' ?>><?= e($label) ?></option>
            <?php endforeach ?>
            <?php if ($periode['kunci'] === 'rentang'): ?><option value="" selected>Rentang tanggal</option><?php endif ?>
        </select>
        <span class="text-sm text-muted">atau</span>
        <label for="f-dari" class="sr-only">Dari tanggal</label>
        <input id="f-dari" type="date" name="dari" value="<?= $periode['kunci'] === 'rentang' ? e($periode['dari']->format('Y-m-d')) : '' ?>" class="<?= $kelasInput ?>">
        <span class="text-sm text-muted" aria-hidden="true">–</span>
        <label for="f-sampai" class="sr-only">Sampai tanggal</label>
        <input id="f-sampai" type="date" name="sampai" value="<?= $periode['kunci'] === 'rentang' ? e($periode['sampai']->format('Y-m-d')) : '' ?>" class="<?= $kelasInput ?>">
        <button type="submit" class="inline-flex h-10 items-center rounded-full bg-primary px-5 text-sm font-semibold text-white hover:bg-primary-dark">Terapkan</button>
    </form>

    <?php /* Kartu ringkasan */ ?>
    <dl class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:col-span-2">
            <dt class="text-sm text-muted">Total zakat masuk</dt>
            <dd class="mt-1 text-3xl lg:text-4xl font-bold text-ink"><?= e(format_rupiah($r['total'])) ?></dd>
            <?php if ($delta !== null): ?>
            <dd class="mt-1 flex flex-wrap items-center gap-x-1.5 text-sm">
                <span class="inline-flex items-center gap-0.5 font-semibold <?= $delta[0] ? 'text-green-800' : 'text-red-700' ?>">
                    <?= icon($delta[0] ? 'arrow-up' : 'arrow-down', 'size-4') ?><?= e($delta[1]) ?>
                </span>
                <span class="text-muted">vs periode sebelumnya (<?= e(rupiah_ringkas((int) $r['total_lalu'])) ?>)</span>
            </dd>
            <?php endif ?>
        </div>
        <div class="@container rounded-2xl border border-gray-200 bg-white p-5">
            <dt class="text-sm text-muted">Transaksi selesai</dt>
            <dd class="mt-1 text-xl font-bold text-ink @[13rem]:text-2xl"><?= e(number_format($r['jumlah'], 0, ',', '.')) ?></dd>
        </div>
        <div class="@container rounded-2xl border border-gray-200 bg-white p-5">
            <dt class="text-sm text-muted">Rata-rata per transaksi</dt>
            <dd class="mt-1 text-xl font-bold text-ink @[13rem]:text-2xl"><?= e($r['jumlah'] > 0 ? format_rupiah($r['rata']) : '—') ?></dd>
        </div>
        <div class="@container relative rounded-2xl border border-gray-200 bg-white p-5<?= App\Core\Auth::kepala() ? '' : ' hover:border-primary' ?>">
            <dt class="text-sm text-muted">
                <?php if (App\Core\Auth::kepala()): ?>
                Menunggu verifikasi
                <?php else: ?>
                <a href="<?= e(url('admin/pembayaran', ['status' => '0'])) ?>" class="after:absolute after:inset-0">Menunggu verifikasi</a>
                <?php endif ?>
            </dt>
            <dd class="mt-1 text-xl font-bold text-ink @[13rem]:text-2xl"><?= e(number_format($r['menunggu'], 0, ',', '.')) ?> <span class="text-base font-semibold text-muted">transaksi</span></dd>
            <dd class="mt-1 text-sm text-muted"><?= e(format_rupiah($r['nominal_menunggu'])) ?> belum dihitung</dd>
        </div>
    </dl>

    <?php if ($kosong): ?>
    <div class="mt-6"><?= $this->insert('components/empty-state', ['judul' => 'Belum ada zakat masuk pada periode ini', 'ikon' => 'chart',
        'pesan' => 'Hanya pembayaran yang sudah diverifikasi (Selesai) yang dihitung. Coba pilih periode lain.',
        'aksi' => $periode['kunci'] !== 'semua' ? ['label' => 'Lihat semua waktu', 'href' => '/admin?periode=semua#statistik'] : null]) ?></div>
    <?php else: ?>
    <div class="mt-6 grid gap-6">
        <?php /* Tren per kelompok */ ?>
        <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-tren">
            <h3 id="judul-tren" class="font-bold">Zakat masuk <?= e($satuan) ?> menurut kelompok</h3>
            <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted" aria-label="Keterangan warna">
                <?php foreach (Statistik::KELOMPOK as $kel): ?>
                <li class="inline-flex items-center gap-1.5"><span class="size-3 rounded-sm" style="background: <?= e($kel['warna']) ?>"></span><?= e($kel['label']) ?></li>
                <?php endforeach ?>
            </ul>
            <div class="mt-3"><?= $this->insert('admin/grafik/tren', ['tren' => $tren, 'idJudul' => 'judul-tren']) ?></div>
            <details class="mt-3 text-sm">
                <summary class="cursor-pointer font-semibold text-primary">Tampilkan sebagai tabel</summary>
                <div class="relative mt-3 max-h-80 overflow-auto rounded-xl border border-gray-200" tabindex="0">
                    <table class="w-full min-w-[560px] text-right">
                        <thead class="sticky top-0 bg-surface text-xs text-muted">
                            <tr><th scope="col" class="px-3 py-2 text-left">Periode</th><?php foreach (Statistik::KELOMPOK as $kel): ?><th scope="col" class="px-3 py-2"><?= e($kel['label']) ?></th><?php endforeach ?><th scope="col" class="px-3 py-2">Total</th></tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100" style="font-variant-numeric: tabular-nums">
                            <?php foreach ($tren as $b): ?>
                            <tr><th scope="row" class="px-3 py-1.5 text-left font-medium"><?= e($b['judul']) ?></th>
                                <?php foreach (array_keys(Statistik::KELOMPOK) as $k): ?><td class="px-3 py-1.5"><?= e(format_rupiah($b['nilai'][$k])) ?></td><?php endforeach ?>
                                <td class="px-3 py-1.5 font-semibold"><?= e(format_rupiah($b['total'])) ?></td></tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </details>
        </section>

        <?php /* Per jenis zakat: batang mendatar berurutan, nilai di ujung batang */ ?>
        <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-jenis">
            <h3 id="judul-jenis" class="font-bold">Menurut jenis zakat</h3>
            <p class="text-sm text-muted">Urut dari yang terbesar · <?= e($periode['label']) ?></p>
            <ul class="mt-4 grid gap-x-10 gap-y-3 lg:grid-cols-2">
                <?php $terbesar = max(1, $perJenis[0]['total'] ?? 1); ?>
                <?php foreach ($perJenis as $j): ?>
                <li>
                    <div class="flex items-baseline justify-between gap-3 text-sm">
                        <span class="inline-flex min-w-0 items-center gap-1.5 font-medium text-ink">
                            <span class="size-2.5 shrink-0 rounded-sm" style="background: <?= e(Statistik::KELOMPOK[$j['kelompok']]['warna']) ?>" title="<?= e(Statistik::KELOMPOK[$j['kelompok']]['label']) ?>"></span>
                            <span class="truncate"><?= e($j['jenis']) ?></span>
                        </span>
                        <span class="shrink-0 text-muted"><?= e(number_format($j['jumlah'], 0, ',', '.')) ?>×</span>
                    </div>
                    <div class="mt-1 flex items-center gap-2">
                        <span class="h-4 rounded-r" style="width: calc((100% - 8.5rem) * <?= e(number_format(max(0.015, $j['total'] / $terbesar), 4, '.', '')) ?>); background: <?= e(Statistik::KELOMPOK[$j['kelompok']]['warna']) ?>"></span>
                        <span class="shrink-0 text-sm font-semibold text-ink"><?= e(rupiah_ringkas($j['total'])) ?>
                            <span class="font-normal text-muted">· <?= e(number_format($j['total'] / max(1, $r['total']) * 100, 1, ',', '.')) ?>%</span></span>
                    </div>
                </li>
                <?php endforeach ?>
            </ul>
            <p class="mt-4 text-xs text-muted">Warna kotak kecil = kelompok zakat (lihat keterangan grafik tren). Nilai lengkap: arahkan kursor ke batang di grafik tren atau buka tabel.</p>
        </section>
    </div>
    <?php endif ?>

    <?php if ($adaTahunan): ?>
    <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-tahunan">
        <h3 id="judul-tahunan" class="font-bold">Akumulasi <?= $tahunan['tahun'] ?> dibanding <?= $tahunan['tahun'] - 1 ?></h3>
        <p class="text-sm text-muted">Total berjalan sejak Januari — apakah tahun ini sudah melampaui tahun lalu?</p>
        <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted" aria-label="Keterangan garis">
            <li class="inline-flex items-center gap-1.5"><span class="h-0.5 w-4 rounded bg-[#0b6b3a]"></span><?= $tahunan['tahun'] ?></li>
            <li class="inline-flex items-center gap-1.5"><span class="h-0.5 w-4 rounded bg-[#9ca3af]"></span><?= $tahunan['tahun'] - 1 ?></li>
        </ul>
        <div class="mt-3"><?= $this->insert('admin/grafik/tahunan', ['tahunan' => $tahunan, 'idJudul' => 'judul-tahunan']) ?></div>
        <details class="mt-3 text-sm">
            <summary class="cursor-pointer font-semibold text-primary">Tampilkan sebagai tabel</summary>
            <div class="relative mt-3 overflow-x-auto rounded-xl border border-gray-200" tabindex="0">
                <table class="w-full min-w-[420px] text-right">
                    <thead class="bg-surface text-xs text-muted"><tr><th scope="col" class="px-3 py-2 text-left">Sampai akhir</th><th scope="col" class="px-3 py-2"><?= $tahunan['tahun'] ?></th><th scope="col" class="px-3 py-2"><?= $tahunan['tahun'] - 1 ?></th></tr></thead>
                    <tbody class="divide-y divide-gray-100" style="font-variant-numeric: tabular-nums">
                        <?php foreach ($tahunan['bulan'] as $i => $b): ?>
                        <tr><th scope="row" class="px-3 py-1.5 text-left font-medium"><?= e($b) ?></th>
                            <td class="px-3 py-1.5"><?= $tahunan['ini'][$i] === null ? '<span class="text-muted">—</span>' : e(format_rupiah($tahunan['ini'][$i])) ?></td>
                            <td class="px-3 py-1.5"><?= e(format_rupiah($tahunan['lalu'][$i])) ?></td></tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </details>
    </section>
    <?php endif ?>

    <?php /* Distribusi: mengikuti filter periode yang sama */ ?>
    <?= $this->insert('admin/statistik-distribusi', ['d' => $s['distribusi'], 'periode' => $periode]) ?>
</section>

<?php $this->start('scripts') ?>
<script type="module" src="<?= e(asset('js/grafik.js')) ?>"></script>
<?php $this->stop() ?>
