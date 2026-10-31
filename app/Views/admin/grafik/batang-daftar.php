<?php
/**
 * Daftar batang mendatar satu warna (besaran), nilai di ujung batang + persentase. Urutan mengikuti data.
 *
 * @var list<array{label: string, total: int, jumlah: int}> $item
 * @var int $total total keseluruhan (untuk persentase)
 */
$terbesar = max(1, $item[0]['total'] ?? 1);
?>
<ul class="space-y-3">
    <?php foreach ($item as $b): ?>
    <li>
        <div class="flex items-baseline justify-between gap-3 text-sm">
            <span class="min-w-0 truncate font-medium text-ink"><?= e($b['label']) ?></span>
            <span class="shrink-0 text-muted"><?= e(number_format($b['jumlah'], 0, ',', '.')) ?> kegiatan</span>
        </div>
        <div class="mt-1 flex items-center gap-2">
            <span class="h-3 shrink-0 rounded-r bg-primary" style="width: calc((100% - 8.5rem) * <?= e(number_format(max(0.01, $b['total'] / $terbesar), 4, '.', '')) ?>)"></span>
            <span class="shrink-0 text-sm font-semibold text-ink"><?= e(rupiah_ringkas($b['total'])) ?>
                <span class="font-normal text-muted">· <?= e(number_format($b['total'] / max(1, $total) * 100, 1, ',', '.')) ?>%</span></span>
        </div>
    </li>
    <?php endforeach ?>
</ul>
