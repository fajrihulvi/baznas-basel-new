<?php
/**
 * /rekening — rekening resmi + tombol salin nomor.
 *
 * @var App\Core\View $this
 * @var list<array>   $rekening  id, nama_bank, nomor_rekening, atas_nama, logo
 */
$this->layout('site', [
    'title'       => 'Rekening Resmi',
    'description' => 'Daftar rekening resmi BAZNAS Kabupaten Bangka Selatan untuk transfer zakat, infaq, dan sedekah.',
]);
$wa = setting('whatsapp');
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Rekening Resmi',
    'subjudul'   => 'Transfer zakat, infaq, dan sedekah hanya ke rekening atas nama BAZNAS Kabupaten Bangka Selatan berikut.',
    'breadcrumb' => [['label' => 'Layanan'], ['label' => 'Rekening Resmi']],
]) ?>

<div class="mx-auto max-w-4xl px-4 py-8 lg:py-12">
    <?= $this->insert('components/alert', [
        'jenis' => 'peringatan',
        'judul' => 'Waspada penipuan',
        'pesan' => 'BAZNAS Bangka Selatan tidak pernah meminta transfer ke rekening pribadi. Pastikan nama penerima sesuai daftar di bawah.',
    ]) ?>

    <?php if ($rekening === []): ?>
    <div class="mt-6"><?= $this->insert('components/empty-state', ['judul' => 'Daftar rekening sedang diperbarui', 'ikon' => 'bank', 'aksi' => ['label' => 'Hubungi kami', 'href' => '#kontak']]) ?></div>
    <?php else: ?>
    <ul class="mt-6 grid gap-4 sm:grid-cols-2">
        <?php foreach ($rekening as $r): ?>
        <li><?= $this->insert('components/card-rekening', ['rekening' => $r]) ?></li>
        <?php endforeach ?>
    </ul>
    <?php endif ?>

    <div class="mt-8 rounded-2xl bg-surface p-6">
        <h2 class="font-bold text-ink">Sudah transfer?</h2>
        <p class="mt-1 text-sm text-muted">Konfirmasikan pembayaran Anda agar segera kami catat dan terbitkan Bukti Setor Zakat (BSZ).</p>
        <div class="mt-4 flex flex-wrap gap-2">
            <?= $this->insert('components/button', ['label' => 'Konfirmasi Pembayaran', 'href' => '/konfirmasi', 'icon' => 'upload']) ?>
            <?php if ($wa): ?>
            <?= $this->insert('components/button', ['label' => 'Tanya via WhatsApp', 'href' => 'https://wa.me/' . $wa, 'variant' => 'outline', 'icon' => 'whatsapp', 'external' => true]) ?>
            <?php endif ?>
        </div>
    </div>
</div>
