<?php
/**
 * /bayar-zakat/{kode} — instruksi transfer & status. Berisi nama muzaki → noindex, Cache-Control: no-store.
 *
 * @var App\Core\View $this
 * @var array       $p           id, kode, sapaan, nama_lengkap, id_jenis, jenis, nominal, jml_hari, jml_jiwa, status, waktu
 * @var list<array> $rekening    rekening yang disarankan
 * @var list<array> $konfirmasi  status, tgl_transfer, nominal_transfer, dikirim
 */

use App\Models\Pembayaran;

$this->layout('site', ['title' => 'Instruksi Transfer ' . $p['kode'], 'noindex' => true]);

$sapaan = $p['sapaan'] === 'IBU' ? 'Ibu' : 'Bapak';
$wa = setting('whatsapp');
$pesanWa = "Assalamu'alaikum, saya {$sapaan} {$p['nama_lengkap']} sudah transfer {$p['jenis']} sebesar "
    . format_rupiah($p['nominal']) . " dengan kode pembayaran {$p['kode']}. Mohon diverifikasi. Terima kasih.";
$menunggu = $p['status'] === Pembayaran::MENUNGGU;
$sudahKonfirmasi = $konfirmasi !== [];
$rincian = $p['jml_jiwa'] ? $p['jml_jiwa'] . ' jiwa' : ($p['jml_hari'] ? $p['jml_hari'] . ' hari' : null);
?>
<div class="mx-auto max-w-3xl px-4 py-6 lg:py-10">
    <?= $this->insert('components/breadcrumb', ['items' => [['label' => 'Bayar Zakat', 'url' => '/bayar-zakat'], ['label' => 'Instruksi Transfer']]]) ?>
    <h1 class="mt-3 text-2xl font-bold text-ink lg:text-3xl">
        <?= $p['status'] === Pembayaran::SELESAI ? 'Pembayaran Terverifikasi' : ($p['status'] === Pembayaran::GAGAL ? 'Pembayaran Dibatalkan' : 'Instruksi Transfer') ?>
    </h1>

    <?php if ($p['status'] === Pembayaran::SELESAI): ?>
    <div class="mt-6"><?= $this->insert('components/alert', ['jenis' => 'sukses', 'judul' => 'Jazakumullahu khairan', 'pesan' => 'Pembayaran ' . $p['jenis'] . ' sebesar ' . format_rupiah($p['nominal']) . ' telah kami terima. Semoga menjadi berkah dan pembersih harta.']) ?></div>
    <?php elseif ($p['status'] === Pembayaran::GAGAL): ?>
    <div class="mt-6"><?= $this->insert('components/alert', ['jenis' => 'error', 'pesan' => 'Pembayaran dengan kode ini dibatalkan. Bila Anda sudah transfer, silakan hubungi kami.']) ?></div>
    <?php elseif ($sudahKonfirmasi): ?>
    <div class="mt-6"><?= $this->insert('components/alert', ['jenis' => 'info', 'judul' => 'Konfirmasi sedang diverifikasi', 'pesan' => 'Bukti transfer Anda dikirim ' . tgl_id(substr((string) $konfirmasi[0]['dikirim'], 0, 10)) . '. Verifikasi maksimal 1×24 jam kerja.']) ?></div>
    <?php endif ?>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl bg-primary p-5 text-white">
            <p class="text-sm text-white/85"><?= $menunggu ? 'Transfer tepat sebesar' : 'Nominal' ?></p>
            <p class="mt-1 text-3xl font-bold tracking-tight"><?= e(format_rupiah($p['nominal'])) ?></p>
            <?php if ($menunggu): ?>
            <button type="button" hidden data-salin="<?= (int) $p['nominal'] ?>" data-salin-pesan="Nominal disalin"
                    class="mt-3 inline-flex h-10 items-center gap-2 rounded-full bg-white/15 px-4 text-sm font-semibold hover:bg-white/25"><?= icon('copy', 'size-4') ?>Salin nominal</button>
            <?php endif ?>
        </div>
        <div class="rounded-2xl border-2 border-dashed border-primary/40 bg-white p-5">
            <p class="text-sm text-muted">Kode pembayaran<?= $menunggu ? ' — tulis di berita transfer' : '' ?></p>
            <p class="mt-1 font-mono text-3xl font-bold tracking-widest text-primary"><?= e($p['kode']) ?></p>
            <button type="button" hidden data-salin="<?= e($p['kode']) ?>" data-salin-pesan="Kode pembayaran disalin"
                    class="mt-3 inline-flex h-10 items-center gap-2 rounded-full border border-gray-300 px-4 text-sm font-semibold text-primary hover:border-primary"><?= icon('copy', 'size-4') ?>Salin kode</button>
        </div>
    </div>

    <dl class="mt-4 divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white text-sm">
        <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-muted">Jenis</dt><dd class="text-right font-semibold"><?= e($p['jenis']) ?><?= $rincian ? ' · ' . e($rincian) : '' ?></dd></div>
        <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-muted">Atas nama</dt><dd class="text-right font-semibold"><?= e($sapaan . ' ' . $p['nama_lengkap']) ?></dd></div>
        <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-muted">Tanggal</dt><dd class="text-right font-semibold"><?= e(tgl_id(substr((string) $p['waktu'], 0, 10))) ?></dd></div>
        <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-muted">Status</dt>
            <dd class="text-right font-semibold"><?= match ($p['status']) {
                Pembayaran::SELESAI => '<span class="text-green-700">Terverifikasi</span>',
                Pembayaran::GAGAL   => '<span class="text-red-700">Dibatalkan</span>',
                default             => $sudahKonfirmasi ? '<span class="text-sky-800">Menunggu verifikasi</span>' : '<span class="text-amber-800">Menunggu transfer</span>',
            } ?></dd>
        </div>
    </dl>

    <?php if ($menunggu): ?>
    <section class="mt-10" aria-labelledby="judul-langkah">
        <h2 id="judul-langkah" class="text-xl font-bold text-ink">Cara menunaikan</h2>
        <ol class="mt-4 space-y-3">
            <?php foreach ([
                ['Transfer ke salah satu rekening resmi di bawah', 'Gunakan nominal tepat ' . format_rupiah($p['nominal']) . '.'],
                ['Tulis kode ' . $p['kode'] . ' di berita/keterangan transfer', 'Kode membantu kami mencocokkan pembayaran Anda.'],
                ['Kirim konfirmasi', 'Unggah foto/tangkapan layar bukti transfer, atau kirim lewat WhatsApp.'],
            ] as $i => [$judulLangkah, $keterangan]): ?>
            <li class="flex gap-4">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-accent font-bold text-ink"><?= $i + 1 ?></span>
                <div><p class="font-semibold text-ink"><?= e($judulLangkah) ?></p><p class="text-sm text-muted"><?= e($keterangan) ?></p></div>
            </li>
            <?php endforeach ?>
        </ol>
    </section>

    <section class="mt-8" aria-labelledby="judul-rekening">
        <h2 id="judul-rekening" class="text-lg font-bold text-ink">Rekening tujuan transfer</h2>
        <ul class="mt-4 grid gap-4 sm:grid-cols-2">
            <?php foreach ($rekening as $r): ?>
            <li><?= $this->insert('components/card-rekening', ['rekening' => $r, 'heading' => 'h3']) ?></li>
            <?php endforeach ?>
        </ul>
    </section>

    <div class="mt-8 flex flex-col gap-3 rounded-2xl bg-surface p-5 sm:flex-row sm:items-center">
        <p class="flex-1 text-sm text-ink"><strong>Sudah transfer?</strong> Kirim konfirmasi agar pembayaran segera diverifikasi.</p>
        <div class="flex flex-wrap gap-2">
            <?= $this->insert('components/button', ['label' => 'Unggah bukti', 'href' => '/konfirmasi?kode=' . rawurlencode($p['kode']), 'icon' => 'upload']) ?>
            <?php if ($wa): ?>
            <?= $this->insert('components/button', ['label' => 'Via WhatsApp', 'href' => 'https://wa.me/' . $wa . '?text=' . rawurlencode($pesanWa), 'variant' => 'outline', 'icon' => 'whatsapp', 'external' => true]) ?>
            <?php endif ?>
        </div>
    </div>
    <?php endif ?>

    <p class="mt-8 text-sm text-muted">
        Simpan kode <strong class="font-mono text-ink"><?= e($p['kode']) ?></strong> — halaman ini dapat dibuka kembali di
        <span class="break-all font-medium text-ink"><?= e(url('bayar-zakat/' . $p['kode'])) ?></span>.
    </p>
</div>
