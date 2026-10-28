<?php
/**
 * Status sistem (kesiapan server).
 *
 * @var App\Core\View $this
 * @var array<string, list<array{label: string, nilai: string, status: string, saran: ?string}>> $grup
 */
$this->layout('admin', ['title' => 'Status Sistem']);
$semua = array_merge(...array_values($grup));
$jumlah = array_count_values(array_column($semua, 'status'));
$gaya = [
    'ok'         => ['Baik', 'bg-green-100 text-green-800'],
    'peringatan' => ['Perlu dicek', 'bg-amber-100 text-amber-900'],
    'galat'      => ['Bermasalah', 'bg-red-100 text-red-800'],
];
?>
<h1 class="text-2xl font-bold">Status Sistem</h1>
<p class="mt-2 max-w-3xl text-sm text-muted">Periksa halaman ini setelah memindahkan situs ke server baru atau mengubah pengaturan hosting.</p>

<p class="mt-4 flex flex-wrap gap-2 text-sm">
    <?php foreach ($gaya as $status => [$label, $kelas]): ?>
    <span class="inline-flex rounded-full px-3 py-1 font-semibold <?= $kelas ?>"><?= (int) ($jumlah[$status] ?? 0) ?> <?= e(strtolower($label)) ?></span>
    <?php endforeach ?>
</p>

<div class="mt-6 grid gap-6 xl:grid-cols-2">
    <?php foreach ($grup as $judul => $baris): ?>
    <section class="rounded-2xl border border-gray-200 bg-white" aria-labelledby="status-<?= e(strtolower($judul)) ?>">
        <h2 id="status-<?= e(strtolower($judul)) ?>" class="border-b border-gray-200 px-5 py-3 text-lg font-bold"><?= e($judul) ?></h2>
        <dl class="divide-y divide-gray-200">
            <?php foreach ($baris as $b): ?>
            <div class="grid gap-1 px-5 py-3 sm:grid-cols-[minmax(0,2fr)_minmax(0,3fr)] sm:gap-4">
                <dt class="text-sm font-semibold text-ink"><?= e($b['label']) ?></dt>
                <dd class="min-w-0 text-sm">
                    <span class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $gaya[$b['status']][1] ?>"><?= e($gaya[$b['status']][0]) ?></span>
                        <span class="break-all"><?= e($b['nilai']) ?></span>
                    </span>
                    <?php if ($b['saran']): ?>
                    <span class="mt-1 block text-muted"><?= e($b['saran']) ?></span>
                    <?php endif ?>
                </dd>
            </div>
            <?php endforeach ?>
        </dl>
    </section>
    <?php endforeach ?>
</div>
