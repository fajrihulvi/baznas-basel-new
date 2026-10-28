<?php
/**
 * @var App\Core\View $this
 * @var array         $p           kolom pembayaran + id, kode, jenis
 * @var list<array>   $konfirmasi  semua kolom konfirmasi_pembayaran
 */

use App\Controllers\Admin\PembayaranController;

$this->layout('admin', ['title' => 'Pembayaran ' . $p['kode']]);
$hp = preg_replace('/\D/', '', (string) $p['no_hp']);
$wa = str_starts_with($hp, '0') ? '62' . substr($hp, 1) : $hp;
$folderBukti = base_path('storage/bukti/');
$data = [
    'Waktu'      => tgl_id(substr((string) $p['waktu'], 0, 10)) . ' ' . substr((string) $p['waktu'], 11, 5),
    'Muzaki'     => ($p['sapaan'] === 'IBU' ? 'Ibu ' : 'Bapak ') . $p['nama_lengkap'],
    'WhatsApp'   => $p['no_hp'],
    'Email'      => $p['email'] ?: '—',
    'NPWZ'       => $p['npwz'] ?: '—',
    'Jenis'      => $p['jenis'] . ($p['jml_jiwa'] ? ' · ' . $p['jml_jiwa'] . ' jiwa' : '') . ($p['jml_hari'] ? ' · ' . $p['jml_hari'] . ' hari' : ''),
    'Nominal'    => format_rupiah($p['nominal']),
];
?>
<nav aria-label="Breadcrumb" class="text-sm text-muted"><a href="<?= e(url('admin/pembayaran')) ?>" class="hover:text-primary hover:underline">Pembayaran</a> › <span aria-current="page"><?= e($p['kode']) ?></span></nav>
<div class="mt-2 flex flex-wrap items-center gap-3">
    <h1 class="font-mono text-2xl font-bold"><?= e($p['kode']) ?></h1>
    <?= $this->insert('admin/status-bayar', ['status' => $p['status']]) ?>
    <a href="<?= e(url('bayar-zakat/' . $p['kode'])) ?>" target="_blank" rel="noopener" class="ml-auto text-sm font-semibold text-primary underline">Halaman muzaki ↗</a>
</div>

<div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
    <section class="rounded-2xl border border-gray-200 bg-white" aria-labelledby="judul-data">
        <h2 id="judul-data" class="border-b border-gray-200 px-5 py-3 font-bold">Data pembayaran</h2>
        <dl class="divide-y divide-gray-200 text-sm">
            <?php foreach ($data as $label => $nilai): ?>
            <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-muted"><?= e($label) ?></dt><dd class="text-right font-semibold break-all"><?= e($nilai) ?></dd></div>
            <?php endforeach ?>
        </dl>
        <div class="flex flex-wrap gap-2 border-t border-gray-200 px-5 py-4">
            <a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener" class="inline-flex h-10 items-center gap-2 rounded-full border border-gray-300 px-4 text-sm font-semibold hover:border-primary hover:text-primary"><?= icon('whatsapp', 'size-4') ?>Hubungi muzaki</a>
        </div>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-status">
        <h2 id="judul-status" class="font-bold">Ubah status pembayaran</h2>
        <p class="mt-1 text-sm text-muted">Biasanya cukup memverifikasi konfirmasi di bawah. Pakai ini untuk transfer tanpa konfirmasi atau pembatalan.</p>
        <div class="mt-4 flex flex-col gap-2">
            <?php foreach (PembayaranController::STATUS as $kode => $label): ?>
            <?php if ($kode !== $p['status']): ?>
            <form method="post" action="<?= e(url('admin/pembayaran/' . $p['id'] . '/status')) ?>" data-konfirmasi="Ubah status pembayaran <?= e($p['kode']) ?> menjadi <?= e($label) ?>?">
                <?= csrf_field() ?>
                <input type="hidden" name="status" value="<?= e($kode) ?>">
                <button type="submit" class="inline-flex min-h-11 w-full items-center justify-center rounded-full border-2 px-4 text-sm font-bold <?= $kode === '1' ? 'border-primary bg-primary text-white hover:bg-primary-dark' : ($kode === '2' ? 'border-red-200 text-red-700 hover:bg-red-50' : 'border-gray-300 hover:border-primary hover:text-primary') ?>">Tandai <?= e(mb_strtolower($label)) ?></button>
            </form>
            <?php endif ?>
            <?php endforeach ?>
        </div>
    </section>
</div>

<section class="mt-8" aria-labelledby="judul-konfirmasi">
    <h2 id="judul-konfirmasi" class="text-lg font-bold">Konfirmasi transfer (<?= count($konfirmasi) ?>)</h2>
    <?php if ($konfirmasi === []): ?>
    <p class="mt-3 text-sm text-muted">Muzaki belum mengirim konfirmasi.</p>
    <?php endif ?>
    <ul class="mt-4 grid gap-4 lg:grid-cols-2">
        <?php foreach ($konfirmasi as $k): ?>
        <?php
        $idK = (int) $k['id_konfirmasi_pembayaran'];
        $adaFile = $k['bukti_pembayaran'] && is_file($folderBukti . $k['bukti_pembayaran']);
        $pdf = str_ends_with((string) $k['bukti_pembayaran'], '.pdf');
        $selisih = $k['nominal_transfer'] !== null && (int) $k['nominal_transfer'] !== (int) $p['nominal'];
        ?>
        <li class="rounded-2xl border bg-white p-5 <?= $k['status'] === 'baru' ? 'border-accent ring-2 ring-accent/30' : 'border-gray-200' ?>">
            <div class="flex items-center gap-2">
                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold <?= ['baru' => 'bg-accent text-ink', 'diverifikasi' => 'bg-green-100 text-green-800', 'ditolak' => 'bg-red-100 text-red-800'][$k['status']] ?? 'bg-gray-100' ?>"><?= e(ucfirst($k['status'])) ?></span>
                <span class="text-sm text-muted">dikirim <?= e(tgl_id(substr((string) ($k['dibuat'] ?? $k['tgl']), 0, 10))) ?></span>
            </div>
            <dl class="mt-3 space-y-1.5 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-muted">Tanggal transfer</dt><dd class="font-semibold"><?= e(tgl_id($k['tgl_transfer'])) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Dari</dt><dd class="text-right font-semibold"><?= e($k['bank_pengirim']) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Ke</dt><dd class="text-right font-semibold"><?= e($k['bank_tujuan'] . ' ' . $k['norek_tujuan']) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-muted">Nominal transfer</dt>
                    <dd class="font-semibold <?= $selisih ? 'text-red-700' : '' ?>"><?= $k['nominal_transfer'] !== null ? e(format_rupiah($k['nominal_transfer'])) : '—' ?><?= $selisih ? ' (berbeda!)' : '' ?></dd></div>
            </dl>
            <div class="mt-4">
                <?php if ($adaFile && !$pdf): ?>
                <a href="<?= e(url('admin/bukti/' . $idK)) ?>" target="_blank" rel="noopener" class="block overflow-hidden rounded-xl border border-gray-200 bg-surface">
                    <img src="<?= e(url('admin/bukti/' . $idK)) ?>" alt="Bukti transfer <?= e($k['kode_pembayaran']) ?>" loading="lazy" class="max-h-80 w-full object-contain">
                </a>
                <?php elseif ($adaFile): ?>
                <a href="<?= e(url('admin/bukti/' . $idK)) ?>" target="_blank" rel="noopener" class="inline-flex items-center gap-2 font-semibold text-primary underline"><?= icon('document', 'size-5') ?>Buka bukti (PDF)</a>
                <?php else: ?>
                <p class="rounded-xl bg-surface p-3 text-sm text-muted">File bukti tidak tersedia<?= $k['bukti_pembayaran'] ? ' (konfirmasi dari situs lama — file belum dipindahkan)' : '' ?>.</p>
                <?php endif ?>
            </div>
            <?php if ($k['status'] === 'baru'): ?>
            <div class="mt-4 flex flex-wrap gap-2">
                <form method="post" action="<?= e(url('admin/konfirmasi/' . $idK . '/status')) ?>" data-konfirmasi="Verifikasi konfirmasi ini? Pembayaran <?= e($p['kode']) ?> akan ditandai selesai.">
                    <?= csrf_field() ?><input type="hidden" name="status" value="diverifikasi">
                    <button type="submit" class="inline-flex min-h-11 items-center gap-2 rounded-full bg-primary px-5 text-sm font-bold text-white hover:bg-primary-dark"><?= icon('check-circle', 'size-5') ?>Verifikasi</button>
                </form>
                <form method="post" action="<?= e(url('admin/konfirmasi/' . $idK . '/status')) ?>" data-konfirmasi="Tolak konfirmasi ini?">
                    <?= csrf_field() ?><input type="hidden" name="status" value="ditolak">
                    <button type="submit" class="inline-flex min-h-11 items-center rounded-full border-2 border-red-200 px-5 text-sm font-bold text-red-700 hover:bg-red-50">Tolak</button>
                </form>
            </div>
            <?php endif ?>
        </li>
        <?php endforeach ?>
    </ul>
</section>
