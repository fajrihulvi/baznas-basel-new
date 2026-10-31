<?php
/**
 * Statistik distribusi (penyaluran) di dasbor — mengikuti filter periode bagian "Zakat masuk" di atasnya.
 *
 * @var App\Core\View $this
 * @var array $d       StatistikDistribusiService::untuk()
 * @var array $periode StatistikZakatService::periode()
 */

use App\Core\Auth;

$r = $d['ringkasan'];
$kepala = Auth::kepala();
$satuan = ['hari' => 'per hari', 'bulan' => 'per bulan', 'tahun' => 'per tahun'][$periode['satuan']];

$delta = null;
if ($r['total_lalu'] !== null) {
    if ($r['total_lalu'] > 0) {
        $persen = ($r['total'] - $r['total_lalu']) / $r['total_lalu'] * 100;
        $delta = [$persen >= 0, ($persen >= 0 ? '+' : '−') . number_format(abs($persen), abs($persen) < 10 ? 1 : 0, ',', '.') . '%'];
    } elseif ($r['total'] > 0) {
        $delta = [true, 'baru'];
    }
}
// Warna kategori ACR: status (bukan warna seri) + selalu disertai teks kategori
$gayaAcr = match (true) {
    $r['acr'] === null => 'bg-gray-100 text-gray-700',
    $r['acr'] >= 70    => 'bg-green-100 text-green-800',
    $r['acr'] >= 50    => 'bg-amber-100 text-amber-900',
    default            => 'bg-red-50 text-red-800',
};
$adaTren = array_sum(array_column($d['tren'], 'salur')) + array_sum(array_column($d['tren'], 'masuk')) > 0;
?>
<section class="mt-10 border-t border-gray-200 pt-8" aria-labelledby="judul-distribusi">
    <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 id="judul-distribusi" class="text-xl font-bold">Distribusi (penyaluran)</h2>
            <p class="text-sm text-muted">Dana yang disalurkan ke sub-program pilar · <?= e($periode['label']) ?></p>
        </div>
        <?php if (!$kepala): ?>
        <?= $this->insert('components/button', ['label' => 'Catat distribusi', 'href' => '/admin/distribusi/baru', 'size' => 'sm', 'variant' => 'outline']) ?>
        <?php endif ?>
    </div>

    <dl class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 sm:col-span-2">
            <dt class="text-sm text-muted">Total disalurkan</dt>
            <dd class="mt-1 text-3xl font-bold text-ink lg:text-4xl"><?= e(format_rupiah($r['total'])) ?></dd>
            <?php if ($delta !== null): ?>
            <dd class="mt-1 flex flex-wrap items-center gap-x-1.5 text-sm">
                <span class="inline-flex items-center gap-0.5 font-semibold <?= $delta[0] ? 'text-green-800' : 'text-red-700' ?>"><?= icon($delta[0] ? 'arrow-up' : 'arrow-down', 'size-4') ?><?= e($delta[1]) ?></span>
                <span class="text-muted">vs periode sebelumnya (<?= e(rupiah_ringkas((int) $r['total_lalu'])) ?>)</span>
            </dd>
            <?php endif ?>
        </div>
        <div class="@container rounded-2xl border border-gray-200 bg-white p-5">
            <dt class="text-sm text-muted">Rasio penyaluran (ACR)</dt>
            <dd class="mt-1 text-xl font-bold text-ink @[13rem]:text-2xl"><?= $r['acr'] === null ? '—' : e(number_format($r['acr'], $r['acr'] < 10 ? 1 : 0, ',', '.') . '%') ?></dd>
            <dd class="mt-1"><span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $gayaAcr ?>"><?= e($r['kategori_acr'] ?? 'Belum ada zakat masuk') ?></span></dd>
        </div>
        <div class="@container rounded-2xl border border-gray-200 bg-white p-5">
            <dt class="text-sm text-muted">Kegiatan penyaluran</dt>
            <dd class="mt-1 text-xl font-bold text-ink @[13rem]:text-2xl"><?= e(number_format($r['jumlah'], 0, ',', '.')) ?></dd>
            <?php if ($r['total'] > 0): ?>
            <dd class="mt-1 text-sm text-muted"><?= e(number_format($r['produktif'] / $r['total'] * 100, 0, ',', '.')) ?>% produktif</dd>
            <?php endif ?>
        </div>
        <div class="@container rounded-2xl border border-gray-200 bg-white p-5">
            <dt class="text-sm text-muted">Penerima manfaat</dt>
            <dd class="mt-1 text-xl font-bold text-ink @[13rem]:text-2xl"><?= e(number_format($r['penerima'], 0, ',', '.')) ?> <span class="text-base font-semibold text-muted">jiwa</span></dd>
        </div>
    </dl>
    <p class="mt-2 text-xs text-muted">ACR = disalurkan ÷ zakat masuk (Selesai) pada periode yang sama. Kategori menurut Puskas BAZNAS: ≥90% sangat efektif · 70–89% efektif · 50–69% cukup efektif · 20–49% di bawah harapan · &lt;20% tidak efektif.</p>

    <?php if ($adaTren): ?>
    <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-masuk-salur">
        <h3 id="judul-masuk-salur" class="font-bold">Zakat masuk vs disalurkan <?= e($satuan) ?></h3>
        <ul class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-muted" aria-label="Keterangan warna">
            <li class="inline-flex items-center gap-1.5"><span class="size-3 rounded-sm bg-[#0b6b3a]"></span>Disalurkan</li>
            <li class="inline-flex items-center gap-1.5"><span class="size-3 rounded-sm bg-[#9ca3af]"></span>Zakat masuk</li>
        </ul>
        <div class="mt-3"><?= $this->insert('admin/grafik/masuk-salur', ['tren' => $d['tren'], 'idJudul' => 'judul-masuk-salur']) ?></div>
        <details class="mt-3 text-sm">
            <summary class="cursor-pointer font-semibold text-primary">Tampilkan sebagai tabel</summary>
            <div class="relative mt-3 max-h-80 overflow-auto rounded-xl border border-gray-200" tabindex="0">
                <table class="w-full min-w-[480px] text-right">
                    <thead class="sticky top-0 bg-surface text-xs text-muted"><tr><th scope="col" class="px-3 py-2 text-left">Periode</th><th scope="col" class="px-3 py-2">Zakat masuk</th><th scope="col" class="px-3 py-2">Disalurkan</th><th scope="col" class="px-3 py-2">Rasio</th></tr></thead>
                    <tbody class="divide-y divide-gray-100" style="font-variant-numeric: tabular-nums">
                        <?php foreach ($d['tren'] as $b): ?>
                        <tr><th scope="row" class="px-3 py-1.5 text-left font-medium"><?= e($b['judul']) ?></th>
                            <td class="px-3 py-1.5"><?= e(format_rupiah($b['masuk'])) ?></td>
                            <td class="px-3 py-1.5 font-semibold"><?= e(format_rupiah($b['salur'])) ?></td>
                            <td class="px-3 py-1.5"><?= $b['masuk'] > 0 ? e(number_format($b['salur'] / $b['masuk'] * 100, 0, ',', '.') . '%') : '—' ?></td></tr>
                        <?php endforeach ?>
                    </tbody>
                </table>
            </div>
        </details>
    </section>
    <?php endif ?>

    <?php if ($r['jumlah'] === 0): ?>
    <div class="mt-6"><?= $this->insert('components/empty-state', ['judul' => 'Belum ada distribusi tercatat pada periode ini', 'ikon' => 'chart',
        'pesan' => $kepala ? 'Data penyaluran dicatat oleh admin melalui menu Distribusi.' : 'Catat setiap penyaluran di menu Distribusi agar grafik ini terisi.',
        'aksi' => $kepala ? null : ['label' => 'Catat distribusi', 'href' => '/admin/distribusi/baru']]) ?></div>
    <?php else: ?>
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <?php /* Per pilar + rincian sub-program */ ?>
        <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-5 lg:row-span-2" aria-labelledby="judul-pilar">
            <h3 id="judul-pilar" class="font-bold">Menurut pilar &amp; sub-program</h3>
            <p class="text-sm text-muted">Urut dari yang terbesar</p>
            <?php $terbesar = max(1, $d['perPilar'][0]['total'] ?? 1); ?>
            <ul class="mt-4 space-y-5">
                <?php foreach ($d['perPilar'] as $p): ?>
                <li>
                    <div class="flex items-baseline justify-between gap-3 text-sm">
                        <span class="font-bold text-ink"><?= e($p['pilar']) ?></span>
                        <span class="shrink-0 text-muted"><?= e(number_format($p['jumlah'], 0, ',', '.')) ?> kegiatan</span>
                    </div>
                    <div class="mt-1 flex items-center gap-2">
                        <?php if ($p['total'] > 0): ?>
                        <span class="h-4 shrink-0 rounded-r bg-primary" style="width: calc((100% - 8.5rem) * <?= e(number_format(max(0.01, $p['total'] / $terbesar), 4, '.', '')) ?>)"></span>
                        <?php endif ?>
                        <span class="shrink-0 text-sm font-semibold text-ink"><?= e(rupiah_ringkas($p['total'])) ?>
                            <span class="font-normal text-muted">· <?= e(number_format($p['total'] / max(1, $r['total']) * 100, 1, ',', '.')) ?>%</span></span>
                    </div>
                    <?php if ($p['sub'] !== []): ?>
                    <ul class="mt-2 space-y-1.5 border-l-2 border-gray-100 pl-3">
                        <?php foreach ($p['sub'] as $s): ?>
                        <li class="text-sm">
                            <div class="flex items-baseline justify-between gap-3"><span class="min-w-0 truncate text-muted"><?= e($s['nama']) ?></span><span class="shrink-0 font-medium text-ink"><?= e(rupiah_ringkas($s['total'])) ?></span></div>
                            <?php /* skala sama dengan batang pilar → panjang bisa dibandingkan langsung */ ?>
                            <span class="mt-0.5 block h-1.5 rounded-r bg-primary/40" style="width: calc((100% - 8.5rem) * <?= e(number_format(max(0.01, $s['total'] / $terbesar), 4, '.', '')) ?>)"></span>
                        </li>
                        <?php endforeach ?>
                    </ul>
                    <?php endif ?>
                </li>
                <?php endforeach ?>
            </ul>
        </section>

        <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-asnaf">
            <h3 id="judul-asnaf" class="font-bold">Menurut asnaf penerima</h3>
            <p class="text-sm text-muted">Khusus dana zakat · persentase dari zakat yang disalurkan</p>
            <?php if ($d['perAsnaf'] === []): ?>
            <p class="mt-4 text-sm text-muted">Belum ada penyaluran dana zakat pada periode ini.</p>
            <?php else: ?>
            <div class="mt-4"><?= $this->insert('admin/grafik/batang-daftar', ['item' => $d['perAsnaf'], 'total' => array_sum(array_column($d['perAsnaf'], 'total'))]) ?></div>
            <?php endif ?>
        </section>

        <section class="min-w-0 rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-wilayah">
            <h3 id="judul-wilayah" class="font-bold">Menurut kecamatan</h3>
            <div class="mt-4"><?= $this->insert('admin/grafik/batang-daftar', ['item' => $d['perWilayah'], 'total' => $r['total']]) ?></div>
            <h3 class="mt-6 font-bold">Menurut sumber dana</h3>
            <div class="mt-4"><?= $this->insert('admin/grafik/batang-daftar', ['item' => $d['perSumber'], 'total' => $r['total']]) ?></div>
        </section>
    </div>

    <section class="mt-6 rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-salur-terbaru">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h3 id="judul-salur-terbaru" class="font-bold">Distribusi terbaru</h3>
            <?php if (!$kepala): ?><a href="<?= e(url('admin/distribusi')) ?>" class="text-sm font-semibold text-primary underline">Semua distribusi</a><?php endif ?>
        </div>
        <div class="relative mt-3 overflow-x-auto" tabindex="0">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-muted">
                    <tr><th scope="col" class="py-2 pr-4 font-semibold">Tanggal</th><th scope="col" class="py-2 pr-4 font-semibold">Program</th><th scope="col" class="py-2 pr-4 font-semibold">Pilar — sub-program</th><th scope="col" class="py-2 pr-4 text-right font-semibold">Penerima</th><th scope="col" class="py-2 text-right font-semibold">Nominal</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($d['terbaru'] as $t): ?>
                    <tr>
                        <td class="whitespace-nowrap py-2 pr-4 text-muted"><?= e(tgl_id($t['tgl'])) ?></td>
                        <td class="py-2 pr-4 font-medium text-ink">
                            <?php if ($kepala): ?><?= e($t['nama_program']) ?>
                            <?php else: ?><a href="<?= e(url('admin/distribusi/' . (int) $t['id'] . '/ubah')) ?>" class="hover:text-primary hover:underline"><?= e($t['nama_program']) ?></a><?php endif ?>
                        </td>
                        <td class="py-2 pr-4 text-muted"><?= e($t['pilar'] . ' — ' . $t['sub']) ?></td>
                        <td class="py-2 pr-4 text-right text-muted"><?= $t['penerima'] !== null ? e(number_format((int) $t['penerima'], 0, ',', '.')) : '—' ?></td>
                        <td class="whitespace-nowrap py-2 text-right font-semibold text-ink" style="font-variant-numeric: tabular-nums"><?= e(format_rupiah($t['nominal'])) ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif ?>
</section>
