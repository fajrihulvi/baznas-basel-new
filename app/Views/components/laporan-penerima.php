<?php
/**
 * Laporan penerima manfaat 5 pilar (bergaya infografis): judul periode, total penghimpunan (bila lengkap diisi admin),
 * donat + tabel akumulasi, total, dan tabel per bulan. Dipakai dasbor admin & /tentang/sistem-pelaporan-zakat.
 *
 * @var App\Core\View $this
 * @var array  $m   StatistikPenerimaManfaatService::untuk() dengan total > 0
 * @var string $id  awalan id unik di halaman
 */

use App\Models\PenerimaManfaat;

$jiwa = static fn (int $n): string => number_format($n, 0, ',', '.');
$pil = 'bg-[linear-gradient(180deg,#0b6b3a_0%,#0b3a12_100%)] text-white ring-2 ring-[#d8b04a]';
?>
<div class="overflow-hidden rounded-2xl border border-[#d8b04a] bg-[linear-gradient(180deg,#ffffff_0%,#fdf9ea_100%)] p-4 sm:p-6">
    <div class="text-center">
        <p class="mx-auto inline-block rounded-3xl px-6 py-3 <?= $pil ?>">
            <span class="block text-base font-bold sm:text-xl"><?= $m['penghimpunan'] !== null ? 'Penghimpunan Zakat, Infak &amp; Sedekah' : 'Laporan Penerima Manfaat' ?></span>
            <span class="block text-lg font-extrabold text-accent sm:text-2xl"><?= e($m['label']) ?></span>
        </p>
        <?php if ($m['penghimpunan'] !== null): ?>
        <p class="mt-4">
            <span class="block text-sm font-semibold text-ink">Total Penghimpunan</span>
            <span class="mt-1 inline-block rounded-full bg-[linear-gradient(180deg,#fff3b0_0%,#f2c53d_100%)] px-5 py-2 text-2xl font-extrabold text-primary-dark ring-2 ring-[#d8b04a] sm:px-8 sm:text-4xl"><?= e(format_rupiah($m['penghimpunan'])) ?></span>
        </p>
        <?php endif ?>
    </div>

    <div class="mt-6 grid items-center gap-6 lg:grid-cols-[minmax(0,2fr)_minmax(0,3fr)]">
        <div class="min-w-0">
            <h3 id="<?= e($id) ?>-donat" class="text-center font-bold text-ink">Penerima Manfaat</h3>
            <?= $this->insert('components/donat', [
                'item' => array_map(static fn (array $p): array => ['label' => $p['nama'], 'nilai' => $p['jiwa'], 'persen' => $p['persen'], 'warna' => $p['warna'], 'teks' => $p['teks']], array_values($m['program'])),
                'total' => $m['total'], 'satuan' => 'jiwa', 'idJudul' => $id . '-donat',
            ]) ?>
        </div>
        <div class="min-w-0 overflow-hidden rounded-2xl border border-[#d8b04a] bg-white">
            <table class="w-full text-left" aria-label="Akumulasi penerima manfaat per program, <?= e($m['label']) ?>">
                <thead class="bg-primary text-xs text-white sm:text-base">
                    <tr><th scope="col" class="px-2 py-3 font-bold sm:px-4">Program</th><th scope="col" class="px-1 py-3 text-center font-bold sm:px-2">Jumlah Jiwa</th><th scope="col" class="px-2 py-3 text-center font-bold sm:px-4">Persentase</th></tr>
                </thead>
                <tbody class="divide-y divide-[#ead9a0]" style="font-variant-numeric: tabular-nums">
                    <?php foreach ($m['program'] as $p): ?>
                    <tr>
                        <th scope="row" class="px-2 py-2.5 font-semibold text-ink sm:px-4">
                            <span class="flex items-center gap-2 text-sm leading-snug sm:gap-2.5 sm:text-base">
                                <?php if ($p['pilar'] !== null): ?>
                                <?= $this->insert('components/ikon-pilar', ['pilar' => $p['pilar'], 'kotak' => 'size-8 rounded-lg! sm:size-11', 'ukuranIkon' => 'size-5 sm:size-6', 'ukuranUnggahan' => 'size-6 sm:size-9', 'sizes' => '36px']) ?>
                                <?php endif ?>
                                <?= e($p['nama']) ?>
                            </span>
                        </th>
                        <td class="px-1 py-2.5 text-center text-base font-bold text-ink sm:px-2 sm:text-xl"><?= e($jiwa($p['jiwa'])) ?></td>
                        <td class="px-2 py-2.5 text-center sm:px-4"><span class="inline-block min-w-16 rounded-full px-1.5 py-1 text-sm font-bold sm:min-w-28 sm:text-lg" style="background: <?= e($p['warna']) ?>; color: <?= e($p['teks']) ?>"><?= e($p['persen']) ?></span></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>

    <p class="mt-6 text-center">
        <span class="inline-block rounded-3xl px-8 py-3 <?= $pil ?>">
            <span class="block text-base font-bold sm:text-lg">Total Penerima Manfaat</span>
            <span class="block text-2xl font-extrabold text-accent sm:text-4xl"><?= e($jiwa($m['total'])) ?> Jiwa</span>
        </span>
    </p>
</div>

<details class="mt-4 text-sm">
    <summary class="cursor-pointer font-semibold text-primary">Rincian per bulan · <?= e((string) $m['tahun']) ?></summary>
    <div class="relative mt-3 overflow-x-auto rounded-xl border border-gray-200 bg-white" tabindex="0">
        <table class="w-full min-w-[720px] text-right">
            <thead class="bg-surface text-xs text-muted">
                <tr><th scope="col" class="px-3 py-2 text-left">Bulan</th><?php foreach ($m['program'] as $p): ?><th scope="col" class="px-3 py-2"><?= e(preg_replace('/^Program /', '', $p['nama'])) ?></th><?php endforeach ?><th scope="col" class="px-3 py-2">Total jiwa</th></tr>
            </thead>
            <tbody class="divide-y divide-gray-100" style="font-variant-numeric: tabular-nums">
                <?php foreach ($m['bulanan'] as $b): ?>
                <tr<?= $b['bulan'] === $m['bulan'] ? ' class="bg-surface font-semibold"' : '' ?>><th scope="row" class="px-3 py-1.5 text-left font-medium whitespace-nowrap"><?= e($b['judul']) ?></th>
                    <?php foreach (array_keys($m['program']) as $k): ?><td class="px-3 py-1.5 whitespace-nowrap"><?= e($jiwa($b['nilai'][$k])) ?> <span class="text-muted">(<?= e(PenerimaManfaat::persen($b['nilai'][$k], $b['total'])) ?>)</span></td><?php endforeach ?>
                    <td class="px-3 py-1.5 font-semibold"><?= e($jiwa($b['total'])) ?></td></tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</details>
