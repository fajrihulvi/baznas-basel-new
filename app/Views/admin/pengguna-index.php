<?php
/**
 * Daftar akun panel admin.
 *
 * @var App\Core\View $this
 * @var list<array>   $pengguna
 * @var string        $q
 * @var int           $saya id akun yang sedang login
 */

use App\Controllers\Admin\PenggunaController;

$this->layout('admin', ['title' => 'Pengguna']);
$lencana = 'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold';
?>
<div class="flex flex-wrap items-center gap-3">
    <h1 class="text-2xl font-bold">Pengguna</h1>
    <?= $this->insert('components/button', ['label' => 'Tambah pengguna', 'href' => '/admin/pengguna/baru', 'size' => 'sm', 'class' => 'ml-auto']) ?>
</div>
<p class="mt-2 max-w-3xl text-sm text-muted">
    Akun yang bisa masuk ke panel admin. <strong class="font-semibold text-ink">Administrator</strong> bisa membuka semua menu termasuk Pengguna;
    <strong class="font-semibold text-ink">Operator</strong> bisa semua menu kecuali Pengguna; <strong class="font-semibold text-ink">Kepala</strong> hanya bisa melihat Dasbor (statistik zakat).
    Akun nonaktif tidak bisa masuk.
</p>

<form method="get" action="<?= e(url('admin/pengguna')) ?>" role="search" class="mt-5 flex max-w-md gap-2">
    <label for="cari-pengguna" class="sr-only">Cari pengguna</label>
    <input id="cari-pengguna" type="search" name="q" value="<?= e($q) ?>" placeholder="Cari nama, username, email…"
           class="h-11 min-w-0 flex-1 rounded-full border border-gray-300 bg-white px-4 focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
    <button type="submit" class="inline-flex h-11 items-center rounded-full bg-primary px-4 font-semibold text-white hover:bg-primary-dark">Cari</button>
</form>
<p class="mt-3 text-sm text-muted"><?= count($pengguna) ?> pengguna<?= $q !== '' ? ' untuk “' . e($q) . '”' : '' ?></p>

<?php if ($pengguna === []): ?>
<div class="mt-4"><?= $this->insert('components/empty-state', ['judul' => 'Tidak ditemukan', 'ikon' => 'users']) ?></div>
<?php else: ?>
<div class="relative mt-4 overflow-x-auto rounded-2xl border border-gray-200 bg-white">
    <table class="w-full min-w-[640px] text-left text-sm">
        <thead class="border-b border-gray-200 bg-surface text-xs uppercase tracking-wide text-muted">
            <tr>
                <th scope="col" class="px-4 py-3 font-semibold">Nama</th>
                <th scope="col" class="px-4 py-3 font-semibold">Peran</th>
                <th scope="col" class="px-4 py-3 font-semibold">Status</th>
                <th scope="col" class="px-4 py-3 font-semibold">Terakhir masuk</th>
                <th scope="col" class="px-4 py-3 text-right font-semibold"><span class="sr-only">Aksi</span></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php foreach ($pengguna as $p): ?>
            <?php $diri = (int) $p['id'] === $saya; $nama = trim($p['f_name'] . ' ' . ($p['l_name'] ?? '')); ?>
            <tr class="align-middle hover:bg-surface/60">
                <td class="px-4 py-3">
                    <span class="font-semibold text-ink"><?= e($nama) ?></span><?php if ($diri): ?> <span class="<?= $lencana ?> bg-accent/20 text-ink">Anda</span><?php endif ?>
                    <span class="block text-xs text-muted"><span class="font-mono"><?= e($p['username']) ?></span><?php foreach (array_filter([$p['email'] ?? null, $p['no_hp'] ?? null]) as $kontak): ?> · <?= e($kontak) ?><?php endforeach ?></span>
                </td>
                <td class="px-4 py-3">
                    <span class="<?= $lencana ?> <?= match ((int) $p['level']) { App\Core\Auth::ADMINISTRATOR => 'bg-primary/10 text-primary', App\Core\Auth::KEPALA => 'bg-blue-50 text-blue-800', default => 'bg-gray-100 text-gray-700' } ?>"><?= e(PenggunaController::PERAN[(int) $p['level']] ?? 'Operator') ?></span>
                </td>
                <td class="px-4 py-3">
                    <span class="<?= $lencana ?> <?= $p['status'] === '0' ? 'bg-red-50 text-red-800' : 'bg-green-100 text-green-800' ?>"><?= $p['status'] === '0' ? 'Nonaktif' : 'Aktif' ?></span>
                </td>
                <td class="px-4 py-3 whitespace-nowrap text-muted">
                    <?= $p['last_login'] ? e(tgl_id(substr((string) $p['last_login'], 0, 10)) . ', ' . substr((string) $p['last_login'], 11, 5)) : 'Belum pernah' ?>
                </td>
                <td class="px-4 py-3">
                    <div class="flex justify-end gap-1 whitespace-nowrap">
                        <a href="<?= e(url('admin/pengguna/' . (int) $p['id'] . '/ubah')) ?>" class="inline-flex h-9 items-center rounded-full border border-gray-300 px-3 font-semibold hover:border-primary hover:text-primary">Ubah<span class="sr-only"> <?= e($nama) ?></span></a>
                        <?php if (!$diri): ?>
                        <form method="post" action="<?= e(url('admin/pengguna/' . (int) $p['id'] . '/hapus')) ?>" data-konfirmasi="Hapus akun <?= e($p['username']) ?>? Pengguna ini tidak akan bisa masuk lagi. Bila hanya sementara, pilih Ubah → nonaktifkan.">
                            <?= csrf_field() ?>
                            <button type="submit" class="inline-flex h-9 items-center rounded-full px-3 font-semibold text-red-700 hover:bg-red-50">Hapus<span class="sr-only"> <?= e($nama) ?></span></button>
                        </form>
                        <?php endif ?>
                    </div>
                </td>
            </tr>
            <?php endforeach ?>
        </tbody>
    </table>
</div>
<?php endif ?>
