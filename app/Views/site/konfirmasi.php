<?php
/**
 * /konfirmasi — unggah bukti transfer. File disimpan di storage/bukti (bukan publik).
 *
 * @var App\Core\View $this
 * @var string        $kode
 * @var array|null    $pembayaran     bila kode ditemukan (hanya jenis, nominal, status yang ditampilkan)
 * @var list<array>   $rekening
 * @var list<int>     $rekeningSaran  id rekening yang cocok dengan jenis pembayaran
 */

use App\Models\Pembayaran;
use App\Services\PembayaranService;

$this->layout('site', [
    'title'       => 'Konfirmasi Pembayaran',
    'description' => 'Konfirmasi transfer zakat, infaq, dan sedekah ke BAZNAS Kabupaten Bangka Selatan dengan mengunggah bukti transfer.',
    'canonical'   => url('konfirmasi'),
]);

$batas = batas_unggah(PembayaranService::MAKS_BUKTI);
$teksBatas = number_format($batas / 1048576, 1, ',', '.') . ' MB';
$opsiRekening = [];
foreach ($rekening as $r) {
    $opsiRekening[$r['id']] = trim($r['nama_bank']) . ' — ' . $r['nomor_rekening'];
}
$rekeningAwal = old('rekening', count($rekeningSaran) === 1 ? (string) $rekeningSaran[0] : '');
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Konfirmasi Pembayaran',
    'subjudul'   => 'Sudah transfer? Unggah bukti transfer agar pembayaran Anda segera kami verifikasi.',
    'breadcrumb' => [['label' => 'Layanan'], ['label' => 'Konfirmasi Pembayaran']],
]) ?>

<div class="mx-auto max-w-2xl px-4 py-8 lg:py-12">
    <?php if ($pembayaran !== null && $pembayaran['status'] === Pembayaran::MENUNGGU): ?>
    <div class="mb-6 flex items-center justify-between gap-4 rounded-2xl bg-primary/5 p-4 text-sm">
        <div><p class="text-muted"><?= e($pembayaran['jenis']) ?> · kode <span class="font-mono font-bold text-ink"><?= e($pembayaran['kode']) ?></span></p>
            <p class="text-lg font-bold text-ink"><?= e(format_rupiah($pembayaran['nominal'])) ?></p></div>
        <a href="<?= e(url('bayar-zakat/' . $pembayaran['kode'])) ?>" class="shrink-0 font-semibold text-primary underline">Lihat instruksi</a>
    </div>
    <?php elseif ($pembayaran !== null): ?>
    <div class="mb-6"><?= $this->insert('components/alert', ['jenis' => 'info', 'pesan' => $pembayaran['status'] === Pembayaran::SELESAI ? 'Pembayaran dengan kode ini sudah diverifikasi. Terima kasih.' : 'Pembayaran dengan kode ini dibatalkan. Silakan hubungi kami.']) ?></div>
    <?php endif ?>

    <form method="post" action="<?= e(url('konfirmasi')) ?>" enctype="multipart/form-data" class="space-y-5 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:p-8">
        <?= csrf_field() ?>
        <div class="hidden" aria-hidden="true">
            <label>Jangan diisi <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <?= $this->insert('components/form-field', [
            'name' => 'kode', 'label' => 'Kode pembayaran', 'required' => true, 'value' => $kode, 'placeholder' => 'mis. AB7K9QXZ',
            'hint' => 'Tertera di halaman instruksi transfer.', 'autocomplete' => 'off', 'atribut' => ['maxlength' => 12, 'pattern' => '[A-Za-z0-9]{4,12}', 'autocapitalize' => 'characters', 'spellcheck' => 'false'],
        ]) ?>
        <?= $this->insert('components/form-field', [
            'name' => 'bank_pengirim', 'label' => 'Bank / e-wallet pengirim', 'required' => true, 'placeholder' => 'mis. BSI, BRI, Bank Sumsel Babel, DANA',
            'atribut' => ['maxlength' => 100],
        ]) ?>
        <?= $this->insert('components/form-field', [
            'name' => 'rekening', 'label' => 'Rekening tujuan', 'type' => 'select', 'required' => true, 'options' => $opsiRekening, 'value' => $rekeningAwal,
        ]) ?>
        <div class="grid gap-5 sm:grid-cols-2">
            <?= $this->insert('components/form-field', [
                'name' => 'tgl_transfer', 'label' => 'Tanggal transfer', 'type' => 'date', 'required' => true, 'value' => old('tgl_transfer', date('Y-m-d')),
                'atribut' => ['max' => date('Y-m-d')],
            ]) ?>
            <?= $this->insert('components/form-field', [
                'name' => 'nominal_transfer', 'label' => 'Nominal transfer (Rp)', 'required' => true, 'placeholder' => '0',
                'value' => old('nominal_transfer', $pembayaran ? number_format((int) $pembayaran['nominal'], 0, ',', '.') : ''),
                'atribut' => ['data-rupiah' => '', 'inputmode' => 'numeric', 'autocomplete' => 'off'],
            ]) ?>
        </div>
        <?= $this->insert('components/form-field', [
            'name' => 'bukti', 'label' => 'Bukti transfer', 'type' => 'file', 'required' => true, 'accept' => 'image/jpeg,image/png,image/webp,application/pdf',
            'hint' => "Foto atau tangkapan layar (JPG, PNG, WebP) atau PDF, maksimal {$teksBatas}. Bukti hanya dapat dilihat petugas BAZNAS.",
        ]) ?>

        <button type="submit" class="inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-full bg-primary px-6 font-bold text-white hover:bg-primary-dark sm:w-auto">
            <?= icon('upload', 'size-5') ?>Kirim konfirmasi
        </button>
    </form>

    <?php if ($wa = setting('whatsapp')): ?>
    <p class="mt-6 text-center text-sm text-muted">
        Kesulitan mengunggah? <a href="https://wa.me/<?= e($wa) ?>" target="_blank" rel="noopener" class="font-semibold text-primary underline">Kirim bukti lewat WhatsApp</a>.
    </p>
    <?php endif ?>
</div>

<?php $this->start('scripts') ?>
    <script type="module" src="<?= e(asset('js/zakat.js')) ?>"></script>
<?php $this->stop() ?>
