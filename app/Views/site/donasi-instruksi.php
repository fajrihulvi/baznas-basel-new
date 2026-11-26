<?php
/**
 * /donasi/transfer/{kode} — instruksi transfer & konfirmasi WhatsApp. Berisi nama donatur → noindex, Cache-Control: no-store.
 *
 * @var App\Core\View $this
 * @var array      $d        kode, nominal, nama, anonim, status, waktu, bank_tujuan, norek_tujuan, program, slug
 * @var array|null $rekening rekening tujuan (master data) bila masih ada
 */
$this->layout('site', ['title' => 'Instruksi Transfer Donasi ' . $d['kode'], 'noindex' => true]);

$wa = preg_replace('/\D/', '', (string) setting('whatsapp'));
$bank = $rekening ? trim($rekening['nama_bank']) : (string) $d['bank_tujuan'];
$norek = $rekening ? $rekening['nomor_rekening'] : (string) $d['norek_tujuan'];
$pesanWa = "Assalamu'alaikum, saya {$d['nama']} sudah transfer donasi \"{$d['program']}\" sebesar " . format_rupiah($d['nominal'])
    . " ke {$bank} ({$norek}) dengan kode donasi {$d['kode']}. Mohon diverifikasi. Terima kasih.";
$menunggu = $d['status'] === 'menunggu';
?>
<div class="mx-auto max-w-3xl px-4 py-6 lg:py-10">
    <?= $this->insert('components/breadcrumb', ['items' => [['label' => 'Program Donasi', 'url' => '/donasi'], ['label' => $d['program'], 'url' => '/donasi/' . $d['slug']], ['label' => 'Instruksi Transfer']]]) ?>
    <h1 class="mt-3 text-2xl font-bold text-ink lg:text-3xl"><?= match ($d['status']) { 'terverifikasi' => 'Donasi Terverifikasi', 'ditolak' => 'Donasi Dibatalkan', default => 'Instruksi Transfer Donasi' } ?></h1>

    <?php if ($d['status'] === 'terverifikasi'): ?>
    <div class="mt-6"><?= $this->insert('components/alert', ['jenis' => 'sukses', 'judul' => 'Jazakumullahu khairan', 'pesan' => 'Donasi sebesar ' . format_rupiah($d['nominal']) . ' untuk ' . $d['program'] . ' telah kami terima. Semoga menjadi amal jariyah.']) ?></div>
    <?php elseif ($d['status'] === 'ditolak'): ?>
    <div class="mt-6"><?= $this->insert('components/alert', ['jenis' => 'error', 'pesan' => 'Donasi dengan kode ini dibatalkan. Bila Anda sudah transfer, silakan hubungi kami lewat WhatsApp.']) ?></div>
    <?php else: ?>
    <div class="mt-6"><?= $this->insert('components/alert', ['jenis' => 'sukses', 'judul' => 'Terima kasih, ' . ($d['anonim'] ? 'Hamba Allah' : $d['nama']), 'pesan' => 'Data donasi tersimpan. Silakan transfer sesuai instruksi di bawah ini, lalu konfirmasi lewat WhatsApp.']) ?></div>
    <?php endif ?>

    <div class="mt-6 grid gap-4 sm:grid-cols-2">
        <div class="rounded-2xl bg-primary p-5 text-white">
            <p class="text-sm text-white/85"><?= $menunggu ? 'Transfer tepat sebesar' : 'Nominal' ?></p>
            <p class="mt-1 text-3xl font-bold tracking-tight"><?= e(format_rupiah($d['nominal'])) ?></p>
            <?php if ($menunggu): ?>
            <button type="button" hidden data-salin="<?= (int) $d['nominal'] ?>" data-salin-pesan="Nominal disalin"
                    class="mt-3 inline-flex h-10 items-center gap-2 rounded-full bg-white/15 px-4 text-sm font-semibold hover:bg-white/25"><?= icon('copy', 'size-4') ?>Salin nominal</button>
            <?php endif ?>
        </div>
        <div class="rounded-2xl border-2 border-dashed border-primary/40 bg-white p-5">
            <p class="text-sm text-muted">Kode donasi<?= $menunggu ? ' — tulis di berita transfer' : '' ?></p>
            <p class="mt-1 font-mono text-3xl font-bold tracking-widest text-primary"><?= e($d['kode']) ?></p>
            <button type="button" hidden data-salin="<?= e($d['kode']) ?>" data-salin-pesan="Kode donasi disalin"
                    class="mt-3 inline-flex h-10 items-center gap-2 rounded-full border border-gray-300 px-4 text-sm font-semibold text-primary hover:border-primary"><?= icon('copy', 'size-4') ?>Salin kode</button>
        </div>
    </div>

    <dl class="mt-4 divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white text-sm">
        <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-muted">Program</dt><dd class="text-right font-semibold"><?= e($d['program']) ?></dd></div>
        <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-muted">Atas nama</dt><dd class="text-right font-semibold"><?= e($d['nama']) ?><?= $d['anonim'] ? ' <span class="font-normal text-muted">(tampil sebagai Hamba Allah)</span>' : '' ?></dd></div>
        <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-muted">Tanggal</dt><dd class="text-right font-semibold"><?= e(tgl_id(substr((string) $d['waktu'], 0, 10))) ?></dd></div>
        <div class="flex justify-between gap-4 px-5 py-3"><dt class="text-muted">Status</dt>
            <dd class="text-right font-semibold"><?= match ($d['status']) {
                'terverifikasi' => '<span class="text-green-700">Terverifikasi</span>',
                'ditolak'       => '<span class="text-red-700">Dibatalkan</span>',
                default         => '<span class="text-amber-800">Menunggu transfer &amp; verifikasi</span>',
            } ?></dd>
        </div>
    </dl>

    <?php if ($menunggu): ?>
    <section class="mt-8" aria-labelledby="judul-rekening">
        <h2 id="judul-rekening" class="text-lg font-bold text-ink">Rekening tujuan transfer</h2>
        <div class="mt-4 max-w-md">
            <?php if ($rekening): ?>
            <?= $this->insert('components/card-rekening', ['rekening' => $rekening, 'heading' => 'h3']) ?>
            <?php else: ?>
            <div class="rounded-2xl border border-gray-200 bg-white p-5"><p class="font-bold text-ink"><?= e($bank) ?></p><p class="mt-2 font-mono text-2xl font-bold text-primary"><?= e($norek) ?></p></div>
            <?php endif ?>
        </div>
    </section>

    <section class="mt-8" aria-labelledby="judul-langkah">
        <h2 id="judul-langkah" class="text-lg font-bold text-ink">Langkah selanjutnya</h2>
        <ol class="mt-4 space-y-3">
            <?php foreach ([
                ['Transfer ke rekening di atas', 'Gunakan nominal tepat ' . format_rupiah($d['nominal']) . '.'],
                ['Tulis kode ' . $d['kode'] . ' di berita/keterangan transfer', 'Kode membantu petugas mencocokkan donasi Anda.'],
                ['Konfirmasi lewat WhatsApp', 'Kirim pesan konfirmasi beserta foto bukti transfer. Petugas akan memverifikasi secara manual.'],
            ] as $i => [$judulLangkah, $keterangan]): ?>
            <li class="flex gap-4">
                <span class="grid size-9 shrink-0 place-items-center rounded-full bg-accent font-bold text-ink"><?= $i + 1 ?></span>
                <div><p class="font-semibold text-ink"><?= e($judulLangkah) ?></p><p class="text-sm text-muted"><?= e($keterangan) ?></p></div>
            </li>
            <?php endforeach ?>
        </ol>
        <?php if ($wa !== ''): ?>
        <a href="<?= e('https://wa.me/' . $wa . '?text=' . rawurlencode($pesanWa)) ?>" target="_blank" rel="noopener"
           class="mt-6 inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-full bg-[#128c7e] px-7 text-lg font-bold text-white hover:bg-[#0e6f64] sm:w-auto">
            <?= icon('whatsapp', 'size-6') ?>Konfirmasi via WhatsApp
        </a>
        <p class="mt-2 text-sm text-muted">Ke nomor <?= e(nomor_hp($wa)) ?>.</p>
        <?php endif ?>
    </section>
    <?php endif ?>

    <p class="mt-8 text-sm text-muted">
        Simpan kode <strong class="font-mono text-ink"><?= e($d['kode']) ?></strong> — halaman ini dapat dibuka kembali di
        <span class="break-all font-medium text-ink"><?= e(url('donasi/transfer/' . $d['kode'])) ?></span>.
    </p>
</div>
