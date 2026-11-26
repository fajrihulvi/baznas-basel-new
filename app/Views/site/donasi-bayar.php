<?php
/**
 * /donasi/{slug}/bayar — Informasi Pembayaran: data donatur, doa, rekening tujuan, rincian. POST → instruksi transfer.
 * Transfer manual ke rekening resmi; verifikasi manual setelah donatur konfirmasi lewat WhatsApp.
 *
 * @var App\Core\View $this
 * @var array       $p         program
 * @var int         $nominal   nominal awal
 * @var list<array> $rekening  rekening tujuan (DonasiService::rekening)
 * @var list<array> $ketentuan halaman kelompok ketentuan yang terbit (judul, slug)
 */
$this->layout('site', ['title' => 'Informasi Pembayaran — ' . $p['nama'], 'noindex' => true]);
$rp = static fn (int $n): string => number_format($n, 0, ',', '.');
$tautanKetentuan = static function (string $kata, string $label) use ($ketentuan): string {
    foreach ($ketentuan as $h) {
        if (str_contains($h['slug'], $kata)) {
            return '<a href="' . e(url('halaman/' . $h['slug'])) . '" target="_blank" class="font-semibold text-primary underline">' . e($label) . '</a>';
        }
    }

    return '<strong>' . e($label) . '</strong>';
};
$rekeningLama = old('rekening', count($rekening) === 1 ? (string) $rekening[0]['id'] : '');
?>
<div class="mx-auto max-w-2xl px-4 py-6 lg:py-10">
    <?= $this->insert('components/breadcrumb', ['items' => [['label' => 'Program Donasi', 'url' => '/donasi'], ['label' => $p['nama'], 'url' => '/donasi/' . $p['slug']], ['label' => 'Informasi Pembayaran']]]) ?>
    <h1 class="mt-3 text-2xl font-bold text-ink lg:text-3xl">Informasi Pembayaran</h1>
    <p class="mt-1 text-muted">Lanjutkan dengan data berikut.</p>

    <form method="post" action="<?= e(url('donasi/' . $p['slug'] . '/bayar')) ?>" class="mt-6 space-y-5" data-donasi-bayar data-program="<?= e($p['nama']) ?>">
        <?= csrf_field() ?>
        <div class="hidden" aria-hidden="true"><label>Jangan diisi <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

        <?= $this->insert('components/form-field', ['name' => 'nominal', 'label' => 'Nominal donasi (Rp)', 'required' => true, 'value' => $rp($nominal),
            'hint' => 'Minimal ' . format_rupiah($p['minimal']) . '.', 'atribut' => ['data-rupiah' => '', 'inputmode' => 'numeric', 'autocomplete' => 'off', 'maxlength' => 16]]) ?>

        <div>
            <?= $this->insert('components/form-field', ['name' => 'nama', 'label' => 'Nama lengkap', 'required' => true, 'autocomplete' => 'name', 'placeholder' => 'Nama lengkap Anda', 'atribut' => ['maxlength' => 100, 'minlength' => 3]]) ?>
            <label class="mt-2 inline-flex cursor-pointer items-center gap-2.5 text-ink">
                <input type="checkbox" name="anonim" value="1" class="size-5 accent-primary"<?= old('anonim') !== '' ? ' checked' : '' ?>>
                Tampilkan sebagai Hamba Allah
            </label>
        </div>
        <?= $this->insert('components/form-field', ['name' => 'no_hp', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel', 'inputmode' => 'tel',
            'placeholder' => 'Masukkan nomor WhatsApp aktif', 'hint' => 'Untuk konfirmasi transfer & kabar penyaluran.', 'atribut' => ['maxlength' => 16]]) ?>
        <?= $this->insert('components/form-field', ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'required' => true, 'autocomplete' => 'email', 'placeholder' => 'Email',
            'hint' => 'Untuk pengiriman bukti donasi & kabar penyaluran.', 'atribut' => ['maxlength' => 100]]) ?>
        <?= $this->insert('components/form-field', ['name' => 'doa', 'label' => 'Doa (opsional)', 'type' => 'textarea', 'placeholder' => 'Tuliskan doa dan harapan Anda',
            'hint' => 'Setelah donasi diverifikasi, doa tampil di halaman program dengan nama Anda (atau "Hamba Allah"), tanpa nominal & kontak.', 'atribut' => ['maxlength' => 500]]) ?>

        <fieldset>
            <legend class="mb-1.5 text-sm font-semibold text-ink">Pilih rekening tujuan transfer <span class="text-red-700" aria-hidden="true">*</span></legend>
            <?php if ($pesan = galat('rekening')): ?>
            <p class="mb-2 flex items-center gap-1.5 text-sm font-medium text-red-700"><?= icon('alert', 'size-4') ?><?= e($pesan) ?></p>
            <?php endif ?>
            <div class="grid gap-2">
                <?php foreach ($rekening as $r): ?>
                <label class="flex cursor-pointer items-center gap-3 rounded-xl border-2 border-gray-200 bg-white p-3 has-checked:border-primary has-checked:bg-primary/5 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-primary">
                    <input type="radio" name="rekening" value="<?= (int) $r['id'] ?>" class="size-5 shrink-0 accent-primary" required data-bank="<?= e(trim($r['nama_bank'])) ?>"<?= (string) $r['id'] === $rekeningLama ? ' checked' : '' ?>>
                    <?php if ($r['logo']): ?>
                    <span class="grid h-10 w-16 shrink-0 place-items-center rounded-lg border border-gray-200 bg-white p-1"><?= img($r['logo'], 'rekening', '', ['sizes' => '64px', 'class' => 'max-h-8 w-auto object-contain']) ?></span>
                    <?php endif ?>
                    <span class="min-w-0">
                        <span class="block font-semibold text-ink"><?= e(trim($r['nama_bank'])) ?></span>
                        <span class="block font-mono text-sm text-muted"><?= e($r['nomor_rekening']) ?> · a.n. <?= e(trim($r['atas_nama'])) ?></span>
                    </span>
                </label>
                <?php endforeach ?>
            </div>
            <p class="mt-2 text-sm text-muted">Transfer manual ke rekening resmi BAZNAS. Setelah transfer, konfirmasi lewat WhatsApp untuk diverifikasi petugas.</p>
        </fieldset>

        <div>
            <label class="flex cursor-pointer items-start gap-2.5 text-ink">
                <input type="checkbox" name="setuju" value="1" required class="mt-0.5 size-5 shrink-0 accent-primary"<?= old('setuju') !== '' ? ' checked' : '' ?><?= galat('setuju') ? ' aria-invalid="true" aria-describedby="galat-setuju"' : '' ?>>
                <span>Dengan ini, saya menyetujui <?= $tautanKetentuan('syarat', 'Syarat dan Ketentuan') ?> serta <?= $tautanKetentuan('privasi', 'Kebijakan Privasi') ?>.</span>
            </label>
            <?php if ($pesan = galat('setuju')): ?>
            <p id="galat-setuju" class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-red-700"><?= icon('alert', 'size-4') ?><?= e($pesan) ?></p>
            <?php endif ?>
        </div>

        <section class="border-t border-dashed border-gray-300 pt-5" aria-labelledby="judul-rincian">
            <h2 id="judul-rincian" class="text-lg font-bold text-ink">Rincian Pembayaran</h2>
            <dl class="mt-3 space-y-2 text-ink" style="font-variant-numeric: tabular-nums">
                <div class="flex justify-between gap-4"><dt class="text-muted"><?= e($p['nama']) ?> <span class="text-sm">(×1)</span></dt><dd data-rincian-nominal><?= e(format_rupiah($nominal)) ?></dd></div>
                <div class="flex justify-between gap-4 border-t border-dashed border-gray-300 pt-3 text-lg font-bold"><dt>Total</dt><dd data-rincian-total><?= e(format_rupiah($nominal)) ?></dd></div>
            </dl>
            <p class="mt-4 text-sm text-muted"><strong class="text-ink">Catatan:</strong> Dana yang didonasikan bukan bersumber dari dana yang tidak halal dan bukan untuk tujuan pencucian uang, terorisme, maupun tindak kejahatan lainnya.</p>
        </section>

        <button type="submit" class="inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-full bg-primary px-7 text-lg font-bold text-white hover:bg-primary-dark">
            Bayar Sekarang <?= icon('heart', 'size-5') ?>
        </button>
    </form>
</div>

<?php $this->start('scripts') ?>
<script type="module" src="<?= e(asset('js/donasi.js')) ?>"></script>
<?php $this->stop() ?>
