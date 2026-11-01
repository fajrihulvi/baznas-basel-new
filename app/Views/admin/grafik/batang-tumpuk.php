<?php
/**
 * Batang mendatar bertumpuk per item (mis. per Perangkat Daerah), urut terbesar, satu skala untuk semua baris.
 * HTML (bukan SVG) agar label tetap terbaca di HP; tooltip rincian & panah atas/bawah oleh assets/js/grafik.js.
 *
 * @var App\Core\View $this
 * @var list<array{label: string, judul: string, nilai: array<string, int>, total: int, catatan?: string}> $item
 * @var array<string, array{label: string, warna: string}> $seri
 * @var int    $total   penyebut persentase
 * @var string $idJudul id judul grafik
 */
$terbesar = max(1, $item[0]['total'] ?? 1);
?>
<div class="relative" data-grafik-wadah>
    <ul class="gap-x-10 lg:columns-2" data-grafik tabindex="0" aria-labelledby="<?= e($idJudul) ?>" data-grafik-awal="0">
        <?php foreach ($item as $it): ?>
        <?php
        $baris = [];
        foreach ($seri as $k => $s) {
            $baris[] = [$s['label'], $s['warna'], format_rupiah($it['nilai'][$k] ?? 0)];
        }
        $isi = array_filter(array_intersect_key($it['nilai'], $seri));
        ?>
        <li class="grafik-sentuh break-inside-avoid rounded-lg px-2 py-1.5" data-judul="<?= e($it['judul']) ?>" data-total="<?= e(format_rupiah($it['total'])) ?>"
            data-baris="<?= e((string) json_encode($baris, JSON_UNESCAPED_UNICODE)) ?>">
            <div class="flex items-baseline justify-between gap-3 text-sm">
                <span class="min-w-0 truncate"><span class="font-semibold text-ink"><?= e($it['label']) ?></span><?php if ($it['judul'] !== $it['label']): ?><span class="text-muted"> · <?= e($it['judul']) ?></span><?php endif ?></span>
                <?php if (!empty($it['catatan'])): ?><span class="shrink-0 text-muted"><?= e($it['catatan']) ?></span><?php endif ?>
            </div>
            <div class="mt-1 flex items-center gap-2">
                <?php /* segmen berbanding lurus dengan nilainya; celah 2px antarsegmen, ujung data membulat */ ?>
                <span class="flex h-4 shrink-0 gap-0.5" style="width: calc((100% - 8.5rem) * <?= e(number_format(max(0.015, $it['total'] / $terbesar), 4, '.', '')) ?>)" aria-hidden="true">
                    <?php foreach ($isi as $k => $v): ?>
                    <span class="h-full min-w-0.5 last:rounded-r" style="flex: <?= (int) $v ?> 1 0; background: <?= e($seri[$k]['warna']) ?>"></span>
                    <?php endforeach ?>
                </span>
                <span class="shrink-0 text-sm font-semibold text-ink" style="font-variant-numeric: tabular-nums"><?= e(rupiah_ringkas($it['total'])) ?>
                    <span class="font-normal text-muted">· <?= e(number_format($it['total'] / max(1, $total) * 100, 1, ',', '.')) ?>%</span></span>
            </div>
        </li>
        <?php endforeach ?>
    </ul>
</div>
