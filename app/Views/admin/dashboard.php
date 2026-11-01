<?php
/**
 * @var App\Core\View $this
 * @var array{menunggu: int, selesai_bulan_ini: int, nominal_bulan_ini: int} $bayar
 * @var int         $konfirmasiBaru
 * @var list<array> $antrean
 * @var list<array> $terbaru
 * @var int         $jumlahKabar
 * @var list<string> $settingKurang
 * @var array       $statistik  StatistikZakatService::untuk()
 * @var bool        $kepala     peran Kepala: hanya statistik (variabel lain tidak dikirim)
 * @var int         $testimoniBaru kiriman testimoni pengunjung yang belum dimoderasi
 * @var array|null  $pengumpulan StatistikPengumpulanService::untuk() (null = belum dimigrasi)
 * @var array<string, string> $filterZakat       parameter filter zakat masuk yang aktif (ikut dikirim filter pengumpulan)
 * @var array<string, string> $filterPengumpulan parameter filter pengumpulan yang aktif (ikut dikirim filter zakat)
 */

use App\Controllers\Admin\PembayaranController;
use App\Core\Auth;

$this->layout('admin', ['title' => 'Dasbor']);

if ($kepala):
?>
<h1 class="text-2xl font-bold">Assalamu'alaikum, <?= e(Auth::user()['nama'] ?? '') ?></h1>
<p class="mt-1 text-muted">Perkembangan zakat masuk, pengumpulan, dan penyaluran BAZNAS Kabupaten Bangka Selatan.</p>
<?= $this->insert('admin/statistik', ['s' => $statistik, 'pertahankan' => $filterPengumpulan]) ?>
<?= $this->insert('admin/statistik-pengumpulan', ['p' => $pengumpulan, 'pertahankan' => $filterZakat]) ?>
<?php
    return;
endif;

$kartu = [
    ['Konfirmasi baru', (string) $konfirmasiBaru, '/admin/pembayaran?konfirmasi=baru', $konfirmasiBaru > 0],
    ['Pembayaran menunggu', (string) $bayar['menunggu'], '/admin/pembayaran?status=0', false],
    ['Selesai bulan ini', format_rupiah($bayar['nominal_bulan_ini']), '/admin/pembayaran?status=1', false],
    ['Kabar terbit', (string) $jumlahKabar, '/admin/post', false],
];
?>
<h1 class="text-2xl font-bold">Assalamu'alaikum, <?= e(Auth::user()['nama'] ?? '') ?></h1>
<p class="mt-1 text-muted">Ringkasan situs BAZNAS Kabupaten Bangka Selatan.</p>

<?php if ($settingKurang !== []): ?>
<div class="mt-6">
    <?= $this->insert('components/alert', ['jenis' => 'peringatan', 'judul' => 'Pengaturan zakat belum lengkap',
        'pesan' => 'Belum diisi: ' . implode('; ', $settingKurang) . '. Kalkulator & form bayar meminta pengunjung mengisinya sendiri sampai diisi di Pengaturan.']) ?>
    <a href="<?= e(url('admin/setting')) ?>" class="mt-2 inline-block text-sm font-semibold text-primary underline">Buka Pengaturan</a>
</div>
<?php endif ?>

<?php if ($testimoniBaru > 0): ?>
<div class="mt-6">
    <?= $this->insert('components/alert', ['jenis' => 'info', 'judul' => $testimoniBaru . ' testimoni baru menunggu moderasi',
        'pesan' => 'Kiriman muzaki/mustahik dari beranda. Periksa isinya, lalu centang "Tampilkan di situs" bila layak tampil.']) ?>
    <a href="<?= e(url('admin/testimoni')) ?>" class="mt-2 inline-block text-sm font-semibold text-primary underline">Buka Testimoni</a>
</div>
<?php endif ?>

<ul class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <?php foreach ($kartu as [$label, $nilai, $link, $sorot]): ?>
    <li>
        <a href="<?= e(url($link)) ?>" class="block rounded-2xl border bg-white p-5 shadow-sm hover:border-primary <?= $sorot ? 'border-accent ring-2 ring-accent/40' : 'border-gray-200' ?>">
            <span class="text-sm text-muted"><?= e($label) ?></span>
            <span class="mt-1 block text-2xl font-bold text-ink"><?= e($nilai) ?></span>
        </a>
    </li>
    <?php endforeach ?>
</ul>

<?= $this->insert('admin/statistik', ['s' => $statistik, 'pertahankan' => $filterPengumpulan]) ?>
<?= $this->insert('admin/statistik-pengumpulan', ['p' => $pengumpulan, 'pertahankan' => $filterZakat]) ?>

<div class="mt-8 grid gap-6 xl:grid-cols-2">
    <section class="rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-antrean">
        <h2 id="judul-antrean" class="font-bold">Konfirmasi menunggu verifikasi</h2>
        <?php if ($antrean === []): ?>
        <p class="mt-3 text-sm text-muted">Tidak ada konfirmasi yang menunggu.</p>
        <?php else: ?>
        <ul class="mt-3 divide-y divide-gray-200 text-sm">
            <?php foreach ($antrean as $a): ?>
            <li class="flex items-center gap-3 py-3">
                <div class="min-w-0 flex-1">
                    <p class="font-semibold"><span class="font-mono"><?= e($a['kode']) ?></span> · <?= e($a['nama_lengkap'] ?? 'Kode tidak cocok dengan pembayaran mana pun') ?></p>
                    <p class="text-muted">Transfer <?= $a['nominal_transfer'] !== null ? e(format_rupiah($a['nominal_transfer'])) : '—' ?> · <?= e(tgl_id(substr((string) $a['dikirim'], 0, 10))) ?></p>
                </div>
                <?php if ($a['id_pembayaran']): ?>
                <a href="<?= e(url('admin/pembayaran/' . $a['id_pembayaran'])) ?>" class="shrink-0 rounded-full bg-primary px-4 py-2 font-semibold text-white hover:bg-primary-dark">Periksa</a>
                <?php else: ?>
                <?php /* Konfirmasi yatim (umumnya data situs lama): tidak bisa diverifikasi, arsipkan agar tidak menumpuk */ ?>
                <form method="post" action="<?= e(url('admin/konfirmasi/' . $a['id'] . '/status')) ?>" data-konfirmasi="Arsipkan konfirmasi <?= e($a['kode']) ?> (ditandai ditolak)?" class="shrink-0">
                    <?= csrf_field() ?><input type="hidden" name="status" value="ditolak">
                    <button type="submit" class="rounded-full border border-gray-300 px-4 py-2 font-semibold hover:border-primary hover:text-primary">Arsipkan</button>
                </form>
                <?php endif ?>
            </li>
            <?php endforeach ?>
        </ul>
        <?php endif ?>
    </section>

    <section class="rounded-2xl border border-gray-200 bg-white p-5" aria-labelledby="judul-terbaru">
        <h2 id="judul-terbaru" class="font-bold">Pembayaran terbaru</h2>
        <ul class="mt-3 divide-y divide-gray-200 text-sm">
            <?php foreach ($terbaru as $p): ?>
            <li>
                <a href="<?= e(url('admin/pembayaran/' . $p['id'])) ?>" class="flex items-center gap-3 py-3 hover:text-primary">
                    <span class="min-w-0 flex-1"><span class="font-mono font-semibold"><?= e($p['kode']) ?></span> · <?= e($p['nama_lengkap']) ?><br>
                        <span class="text-muted"><?= e($p['jenis']) ?> · <?= e(format_rupiah($p['nominal'])) ?></span></span>
                    <?= $this->insert('admin/status-bayar', ['status' => $p['status']]) ?>
                </a>
            </li>
            <?php endforeach ?>
        </ul>
        <a href="<?= e(url('admin/pembayaran')) ?>" class="mt-3 inline-block text-sm font-semibold text-primary underline">Semua pembayaran</a>
    </section>
</div>

<section class="mt-8" aria-labelledby="judul-pintasan">
    <h2 id="judul-pintasan" class="font-bold">Pintasan</h2>
    <div class="mt-3 flex flex-wrap gap-2">
        <?= $this->insert('components/button', ['label' => 'Tulis kabar', 'href' => '/admin/post/baru', 'size' => 'sm']) ?>
        <?= $this->insert('components/button', ['label' => 'Tambah banner', 'href' => '/admin/banner/baru', 'size' => 'sm', 'variant' => 'outline']) ?>
        <?= $this->insert('components/button', ['label' => 'Tambah video', 'href' => '/admin/video/baru', 'size' => 'sm', 'variant' => 'outline']) ?>
        <?= $this->insert('components/button', ['label' => 'Pengaturan', 'href' => '/admin/setting', 'size' => 'sm', 'variant' => 'outline']) ?>
    </div>
</section>
