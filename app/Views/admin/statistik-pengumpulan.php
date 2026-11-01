<?php
/**
 * Statistik pengumpulan ZIS dari Perangkat Daerah (dasbor, juga untuk Kepala).
 * Filter sendiri (bulan & tahun setoran) → kartu ringkasan → per bulan (bulan terpilih disorot) → per Perangkat Daerah.
 *
 * @var App\Core\View $this
 * @var array|null $p           StatistikPengumpulanService::untuk(); null = tabel belum dimigrasi
 * @var array<string, string> $pertahankan parameter filter zakat masuk yang ikut dikirim ulang
 */

use App\Core\Auth;
use App\Models\Pengumpulan;

$kepala = Auth::kepala();
$pertahankan ??= [];
?>
<section id="pengumpulan" class="mt-10 scroll-mt-6 border-t border-gray-200 pt-8" aria-labelledby="judul-pengumpulan">
<?php if ($p === null): ?>
    <h2 id="judul-pengumpulan" class="text-xl font-bold">Pengumpulan dari Perangkat Daerah</h2>
    <div class="mt-4"><?= $this->insert('components/alert', ['jenis' => 'peringatan', 'judul' => 'Tabel pengumpulan belum tersedia',
        'pesan' => 'Jalankan migrasi database 012_pengumpulan.sql agar menu & grafik Pengumpulan bisa dipakai.']) ?></div>
</section>
<?php return; endif ?>
<?php
$r = $p['ringkasan'];
$seri = Pengumpulan::JENIS;
$adaSetahun = array_sum(array_column($p['bulanan'], 'total')) > 0;

$delta = null;
if ($r['total_lalu'] > 0) {
    $persen = ($r['total'] - $r['total_lalu']) / $r['total_lalu'] * 100;
    $delta = [$persen >= 0, ($persen >= 0 ? '+' : '−') . number_format(abs($persen), abs($persen) < 10 ? 1 : 0, ',', '.') . '%'];
} elseif ($r['total'] > 0) {
    $delta = [true, 'baru'];
}
$kelasInput = 'h-10 rounded-full border border-gray-300 bg-white px-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30';
$itemPd = array_map(static fn (array $d): array => [
    'label' => $d['singkatan'], 'judul' => $d['nama'], 'nilai' => $d['nilai'], 'total' => $d['total'],
    'catatan' => $d['setoran'] > 1 ? $d['setoran'] . ' setoran' : '',
], $p['perPd']);
?>
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 id="judul-pengumpulan" class="text-xl font-bold">Pengumpulan dari Perangkat Daerah</h2>
            <p class="text-sm text-muted">Setoran zakat, infaq/sedekah &amp; DSKL menurut bulan setoran · <?= e($p['label']) ?></p>
        </div>
        <?php if (!$kepala): ?>
        <?= $this->insert('components/button', ['label' => 'Catat pengumpulan', 'href' => '/admin/pengumpulan/baru', 'size' => 'sm', 'variant' => 'outline']) ?>
        <?php endif ?>
    </div>

    <form method="get" action="<?= e(url('admin')) ?>#pengumpulan" class="mt-4 flex flex-wrap items-center gap-2" data-filter-otomatis>
        <?php foreach ($pertahankan as $k => $v): ?><input type="hidden" name="<?= e($k) ?>" value="<?= e($v) ?>"><?php endforeach ?>
        <label for="pg-bulan" class="sr-only">Bulan setoran</label>
        <select id="pg-bulan" name="pg_bulan" class="<?= $kelasInput ?>">
            <option value="0"<?= $p['bulan'] === 0 ? ' selected' : '' ?>>Semua bulan</option>
            <?php foreach (Pengumpulan::BULAN as $no => $nama): ?>
            <option value="<?= $no ?>"<?= $p['bulan'] === $no ? ' selected' : '' ?>><?= e($nama) ?></option>
            <?php endforeach ?>
        </select>
        <label for="pg-tahun" class="sr-only">Tahun</label>
        <select id="pg-tahun" name="pg_tahun" class="<?= $kelasInput ?>">
            <?php foreach ($p['daftarTahun'] as $th): ?>
            <option value="<?= $th ?>"<?= $p['tahun'] === $th ? ' selected' : '' ?>><?= $th ?></option>
            <?php endforeach ?>
        </select>
        <button type="submit" class="inline-flex h-10 items-center rounded-full bg-primary px-5 text-sm font-semibold text-white hover:bg-primary-dark">Terapkan</button>
    </form>

    <dl class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:col-span-2">
            <dt class="text-sm text-muted">Total pengumpulan</dt>
            <dd class="mt-1 text-3xl font-bold text-ink lg:text-4xl"><?= e(format_rupiah($r['total'])) ?></dd>
            <?php if ($delta !== null): ?>
            <dd class="mt-1 flex flex-wrap items-center gap-x-1.5 text-sm">
                <span class="inline-flex items-center gap-0.5 font-semibold <?= $delta[0] ? 'text-green-800' : 'text-red-700' ?>"><?= icon($delta[0] ? 'arrow-up' : 'arrow-down', 'size-4') ?><?= e($delta[1]) ?></span>
                <span class="text-muted">vs <?= e($p['labelBanding']) ?> (<?= e(rupiah_ringkas($r['total_lalu'])) ?>)</span>
            </dd>
            <?php endif ?>
            <dd class="mt-1 text-sm text-muted"><?= e(number_format($r['pd_menyetor'], 0, ',', '.')) ?> dari <?= e(number_format($r['pd_aktif'], 0, ',', '.')) ?> perangkat daerah menyetor · <?= e(number_format($r['setoran'], 0, ',', '.')) ?> setoran</dd>
        </div>
        <?php foreach ($seri as $k => $s): ?>
        <div class="@container rounded-2xl border border-gray-200 bg-white p-5">
            <dt class="inline-flex items-center gap-1.5 text-sm text-muted"><span class="size-3 shrink-0 rounded-sm" style="background: <?= e($s['warna']) ?>"></span><?= e($s['label']) ?></dt>
            <dd class="mt-1 text-xl font-bold text-ink @[13rem]:text-2xl"><?= e(format_rupiah($r['nilai'][$k])) ?></dd>
            <?php if ($r['total'] > 0): ?>
            <dd class="mt-1 text-sm text-muted"><?= e(number_format($r['nilai'][$k] / $r['total'] * 100, 1, ',', '.')) ?>% dari total</dd>
            <?php endif ?>
        </div>
        <?php endforeach ?>
    </dl>

    <?php if (!$adaSetahun): ?>
    <div class="mt-6"><?= $this->insert('components/empty-state', ['judul' => 'Belum ada pengumpulan tercatat pada tahun ' . $p['tahun'], 'ikon' => 'chart',
        'pesan' => $kepala ? 'Setoran Perangkat Daerah dicatat oleh admin melalui menu Pengumpulan.' : 'Catat setiap setoran Perangkat Daerah di menu Pengumpulan agar grafik ini terisi.',
        'aksi' => $kepala ? null : ['label' => 'Catat pengumpulan', 'href' => '/admin/pengumpulan/baru']]) ?></div>
    <?php else: ?>
    <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-pg-bulanan">
        <h3 id="judul-pg-bulanan" class="font-bold">Pemasukan pengumpulan per bulan · <?= $p['tahun'] ?></h3>
        <p class="text-sm text-muted"><?= $p['bulan'] > 0 ? 'Bulan ' . e(Pengumpulan::BULAN[$p['bulan']]) . ' disorot. ' : '' ?>Pilih bulan di filter untuk melihat rincian per perangkat daerah.</p>
        <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted" aria-label="Keterangan warna">
            <?php foreach ($seri as $s): ?>
            <li class="inline-flex items-center gap-1.5"><span class="size-3 rounded-sm" style="background: <?= e($s['warna']) ?>"></span><?= e($s['label']) ?></li>
            <?php endforeach ?>
        </ul>
        <div class="mt-3"><?= $this->insert('admin/grafik/tren', ['tren' => $p['bulanan'], 'idJudul' => 'judul-pg-bulanan', 'seri' => $seri, 'sorot' => $p['bulan'] > 0 ? $p['bulan'] - 1 : null]) ?></div>
        <details class="mt-3 text-sm">
            <summary class="cursor-pointer font-semibold text-primary">Tampilkan sebagai tabel</summary>
            <div class="relative mt-3 overflow-x-auto rounded-xl border border-gray-200" tabindex="0">
                <table class="w-full min-w-[600px] text-right">
                    <thead class="bg-surface text-xs text-muted">
                        <tr><th scope="col" class="px-3 py-2 text-left">Bulan</th><?php foreach ($seri as $s): ?><th scope="col" class="px-3 py-2"><?= e($s['label']) ?></th><?php endforeach ?><th scope="col" class="px-3 py-2">Total</th><th scope="col" class="px-3 py-2">PD menyetor</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" style="font-variant-numeric: tabular-nums">
                        <?php foreach ($p['bulanan'] as $b): ?>
                        <tr<?= $b['bulan'] === $p['bulan'] ? ' class="bg-surface font-semibold"' : '' ?>><th scope="row" class="px-3 py-1.5 text-left font-medium"><?= e($b['judul']) ?></th>
                            <?php foreach (array_keys($seri) as $k): ?><td class="px-3 py-1.5"><?= e(format_rupiah($b['nilai'][$k])) ?></td><?php endforeach ?>
                            <td class="px-3 py-1.5 font-semibold"><?= e(format_rupiah($b['total'])) ?></td>
                            <td class="px-3 py-1.5"><?= e((string) $b['pd']) ?></td></tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </details>
    </section>

    <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-pg-pd">
        <h3 id="judul-pg-pd" class="font-bold">Pemasukan per Perangkat Daerah · <?= e($p['label']) ?></h3>
        <p class="text-sm text-muted">Urut dari yang terbesar · <?= e(number_format($r['pd_menyetor'], 0, ',', '.')) ?> dari <?= e(number_format($r['pd_aktif'], 0, ',', '.')) ?> perangkat daerah aktif menyetor</p>
        <?php if ($itemPd === []): ?>
        <p class="mt-4 rounded-xl bg-surface p-4 text-sm text-muted">Belum ada setoran pada <?= e($p['label']) ?>.</p>
        <?php else: ?>
        <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted" aria-label="Keterangan warna">
            <?php foreach ($seri as $s): ?>
            <li class="inline-flex items-center gap-1.5"><span class="size-3 rounded-sm" style="background: <?= e($s['warna']) ?>"></span><?= e($s['label']) ?></li>
            <?php endforeach ?>
        </ul>
        <div class="mt-3"><?= $this->insert('admin/grafik/batang-tumpuk', ['item' => $itemPd, 'seri' => $seri, 'total' => $r['total'], 'idJudul' => 'judul-pg-pd']) ?></div>
        <details class="mt-3 text-sm">
            <summary class="cursor-pointer font-semibold text-primary">Tampilkan sebagai tabel</summary>
            <div class="relative mt-3 max-h-96 overflow-auto rounded-xl border border-gray-200" tabindex="0">
                <table class="w-full min-w-[640px] text-right">
                    <thead class="sticky top-0 bg-surface text-xs text-muted">
                        <tr><th scope="col" class="px-3 py-2 text-left">Perangkat Daerah</th><?php foreach ($seri as $s): ?><th scope="col" class="px-3 py-2"><?= e($s['label']) ?></th><?php endforeach ?><th scope="col" class="px-3 py-2">Total</th><th scope="col" class="px-3 py-2">Setoran</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100" style="font-variant-numeric: tabular-nums">
                        <?php foreach ($p['perPd'] as $d): ?>
                        <tr><th scope="row" class="px-3 py-1.5 text-left font-medium"><?= e($d['nama']) ?></th>
                            <?php foreach (array_keys($seri) as $k): ?><td class="px-3 py-1.5"><?= $d['nilai'][$k] > 0 ? e(format_rupiah($d['nilai'][$k])) : '<span class="text-muted">—</span>' ?></td><?php endforeach ?>
                            <td class="px-3 py-1.5 font-semibold"><?= e(format_rupiah($d['total'])) ?></td>
                            <td class="px-3 py-1.5"><?= e((string) $d['setoran']) ?></td></tr>
                        <?php endforeach ?>
                    </tbody>
                    <tfoot class="border-t border-gray-200 font-bold" style="font-variant-numeric: tabular-nums">
                        <tr><th scope="row" class="px-3 py-2 text-left">Jumlah</th><?php foreach (array_keys($seri) as $k): ?><td class="px-3 py-2"><?= e(format_rupiah($r['nilai'][$k])) ?></td><?php endforeach ?>
                            <td class="px-3 py-2"><?= e(format_rupiah($r['total'])) ?></td><td class="px-3 py-2"><?= e((string) $r['setoran']) ?></td></tr>
                    </tfoot>
                </table>
            </div>
        </details>
        <?php endif ?>

        <?php if ($p['belum'] !== []): ?>
        <details class="mt-3 text-sm"<?= $itemPd === [] ? ' open' : '' ?>>
            <summary class="cursor-pointer font-semibold text-primary">Belum menyetor pada <?= e($p['label']) ?> (<?= count($p['belum']) ?> perangkat daerah)</summary>
            <ul class="mt-3 grid gap-x-6 gap-y-1 text-muted sm:grid-cols-2 lg:grid-cols-3">
                <?php foreach ($p['belum'] as $b): ?>
                <li class="truncate"><?= e($b['nama']) ?></li>
                <?php endforeach ?>
            </ul>
        </details>
        <?php endif ?>
    </section>
    <?php endif ?>
</section>
