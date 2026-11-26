<?php
/**
 * Kartu program donasi: gambar banner (16:7), nama, progres terkumpul vs target, jumlah donatur & sisa waktu.
 *
 * @var App\Core\View $this
 * @var array       $program  DonasiService::program() satu baris
 * @var string|null $heading  tag judul (default h3)
 */

use App\Services\DonasiService;

$p = $program;
$h = $heading ?? 'h3';
?>
<article class="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-md">
    <span class="block aspect-[16/7] overflow-hidden bg-surface">
        <?= img($p['foto'], 'banner', '', ['sizes' => '(min-width: 1024px) 370px, (min-width: 640px) 50vw, 100vw',
            'class' => 'size-full object-cover transition-transform duration-300 group-hover:scale-105 motion-reduce:transition-none motion-reduce:group-hover:scale-100']) ?>
    </span>
    <div class="flex flex-1 flex-col p-4">
        <<?= $h ?> class="font-bold leading-snug text-ink group-hover:text-primary">
            <a href="<?= e(url('donasi/' . $p['slug'])) ?>" class="after:absolute after:inset-0"><?= e($p['nama']) ?></a>
        </<?= $h ?>>
        <div class="mt-auto pt-4">
            <p class="text-xs text-muted">Terkumpul</p>
            <p class="font-bold text-primary" style="font-variant-numeric: tabular-nums"><?= e(format_rupiah($p['terkumpul'])) ?></p>
            <div class="mt-2 h-2 overflow-hidden rounded-full bg-gray-200" role="progressbar" aria-label="Progres donasi" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= (int) round($p['persen']) ?>">
                <span class="block h-full rounded-full bg-accent" style="width: <?= e(number_format($p['persen'], 1, '.', '')) ?>%"></span>
            </div>
            <p class="mt-2 flex flex-wrap justify-between gap-x-3 text-xs text-muted">
                <span><?= e(number_format($p['donatur'], 0, ',', '.')) ?> donatur · target <?= e(rupiah_ringkas($p['target'])) ?></span>
                <span class="font-semibold <?= $p['status'] === 'berjalan' ? 'text-ink' : '' ?>"><?= e(DonasiService::keteranganWaktu($p)) ?></span>
            </p>
        </div>
    </div>
</article>
