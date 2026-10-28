<?php
/**
 * @var App\Core\View $this
 * @var string|null   $status
 * @var bool          $konfirmasiBaru
 * @var string        $q
 * @var list<array>   $baris
 * @var App\Core\Paginator $paginator
 */

use App\Controllers\Admin\PembayaranController;

$this->layout('admin', ['title' => 'Pembayaran']);
$filter = [
    ['Semua', [], $status === null && !$konfirmasiBaru],
    ['Konfirmasi baru', ['konfirmasi' => 'baru'], $konfirmasiBaru],
    ...array_map(static fn (string $k, string $l): array => [$l, ['status' => $k], $status === $k && !$konfirmasiBaru], array_keys(PembayaranController::STATUS), PembayaranController::STATUS),
];
?>
<h1 class="text-2xl font-bold">Pembayaran</h1>

<div class="mt-5 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
    <nav aria-label="Filter status">
        <ul class="flex flex-wrap gap-2">
            <?php foreach ($filter as [$label, $query, $on]): ?>
            <li><a href="<?= e(url('admin/pembayaran', $query)) ?>"<?= $on ? ' aria-current="page"' : '' ?>
                   class="inline-flex h-10 items-center rounded-full border px-4 text-sm font-semibold <?= $on ? 'border-primary bg-primary text-white' : 'border-gray-300 bg-white hover:border-primary hover:text-primary' ?>"><?= e($label) ?></a></li>
            <?php endforeach ?>
        </ul>
    </nav>
    <form method="get" action="<?= e(url('admin/pembayaran')) ?>" role="search" class="flex gap-2 lg:w-80">
        <?php if ($status !== null): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif ?>
        <label for="cari-bayar" class="sr-only">Cari kode, nama, atau nomor HP</label>
        <input id="cari-bayar" type="search" name="q" value="<?= e($q) ?>" placeholder="Kode, nama, atau no. HP"
               class="h-10 min-w-0 flex-1 rounded-full border border-gray-300 bg-white px-4 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
        <button type="submit" class="inline-flex h-10 items-center rounded-full bg-primary px-4 text-sm font-semibold text-white hover:bg-primary-dark">Cari</button>
    </form>
</div>

<?php if ($baris === []): ?>
<div class="mt-6"><?= $this->insert('components/empty-state', ['judul' => 'Tidak ada pembayaran', 'ikon' => 'bank']) ?></div>
<?php else: ?>
<div class="relative mt-6 overflow-x-auto rounded-2xl border border-gray-200 bg-white">
    <table class="w-full min-w-[720px] text-left text-sm">
        <thead class="border-b border-gray-200 bg-surface text-xs uppercase tracking-wide text-muted">
            <tr>
                <th scope="col" class="px-4 py-3">Waktu</th>
                <th scope="col" class="px-4 py-3">Kode</th>
                <th scope="col" class="px-4 py-3">Muzaki</th>
                <th scope="col" class="px-4 py-3">Jenis</th>
                <th scope="col" class="px-4 py-3 text-right">Nominal</th>
                <th scope="col" class="px-4 py-3">Status</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php foreach ($baris as $p): ?>
            <tr class="hover:bg-surface/60">
                <td class="whitespace-nowrap px-4 py-3 text-muted"><?= e(tgl_id(substr((string) $p['waktu'], 0, 10))) ?></td>
                <td class="px-4 py-3"><a href="<?= e(url('admin/pembayaran/' . $p['id'])) ?>" class="font-mono font-bold text-primary underline"><?= e($p['kode']) ?></a></td>
                <td class="px-4 py-3"><?= e(($p['sapaan'] === 'IBU' ? 'Ibu ' : 'Bapak ') . $p['nama_lengkap']) ?></td>
                <td class="px-4 py-3"><?= e($p['jenis']) ?></td>
                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold"><?= e(format_rupiah($p['nominal'])) ?></td>
                <td class="px-4 py-3">
                    <?= $this->insert('admin/status-bayar', ['status' => $p['status']]) ?>
                    <?php if ((int) $p['konfirmasi_baru'] > 0): ?>
                    <span class="ml-1 inline-flex rounded-full bg-accent px-2 py-0.5 text-xs font-bold">Konfirmasi baru</span>
                    <?php endif ?>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>

<?= $this->insert('components/pagination', ['paginator' => $paginator]) ?>
