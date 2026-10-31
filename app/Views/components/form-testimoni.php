<?php
/**
 * Form testimoni untuk muzaki/mustahik. Dipakai di beranda (dilipat, token CSRF diambil JS saat mulai mengisi
 * agar beranda tetap tanpa cookie sesi) dan di /testimoni/kirim (terbuka, token langsung).
 *
 * @var App\Core\View $this
 * @var string        $asal  'beranda' | 'halaman'
 */

use App\Core\Session;
use App\Models\Testimoni;

$beranda   = ($asal ?? 'halaman') === 'beranda';
$terkirim  = (bool) Session::getFlash('testimoni_terkirim', false);
$errors    = Session::getFlash('errors', []);
$adaGalat  = is_array($errors) && $errors !== [];
$galatForm = galat('_form');
$peran     = old('peran');
$rating    = old('rating', '5');
$pilihNilai = ['5' => 'Sangat puas', '4' => 'Puas', '3' => 'Cukup', '2' => 'Kurang', '1' => 'Kecewa'];
$kelasPil  = 'flex cursor-pointer items-center gap-2 rounded-xl border border-gray-300 bg-white px-4 py-3 text-sm font-semibold text-ink hover:border-primary '
    . 'has-checked:border-primary has-checked:bg-primary/5 has-checked:text-primary has-focus-visible:ring-2 has-focus-visible:ring-primary/40';
?>
<?php if ($terkirim): ?>
<?= $this->insert('components/alert', ['jenis' => 'sukses', 'judul' => 'Terima kasih, testimoni Anda sudah kami terima',
    'pesan' => 'Testimoni akan tampil di situs setelah diperiksa oleh admin BAZNAS Bangka Selatan.']) ?>
<?php else: ?>
<form method="post" action="<?= e(url('testimoni/kirim')) ?>" class="space-y-5" novalidate<?= $beranda ? ' data-testimoni data-token="' . e(url('testimoni/token')) . '"' : '' ?>>
    <?php if ($beranda): ?>
    <input type="hidden" name="_token" value="<?= $adaGalat ? e(csrf_token()) : '' ?>">
    <noscript><p class="text-sm text-muted">JavaScript nonaktif? Isi lewat <a href="<?= e(url('testimoni/kirim')) ?>" class="font-semibold text-primary underline">halaman form testimoni</a>.</p></noscript>
    <?php else: ?>
    <?= csrf_field() ?>
    <?php endif ?>
    <input type="hidden" name="asal" value="<?= $beranda ? 'beranda' : 'halaman' ?>">
    <?php /* Honeypot: tak terlihat manusia, sering diisi bot */ ?>
    <div class="hidden" aria-hidden="true"><label>Situs web <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>

    <?php if ($galatForm): ?>
    <?= $this->insert('components/alert', ['jenis' => 'error', 'pesan' => $galatForm]) ?>
    <?php elseif ($adaGalat): ?>
    <?= $this->insert('components/alert', ['jenis' => 'error', 'pesan' => 'Periksa kembali isian yang ditandai.']) ?>
    <?php endif ?>

    <fieldset>
        <legend class="mb-1.5 text-sm font-semibold text-ink">Anda sebagai <span class="text-red-700" aria-hidden="true">*</span></legend>
        <div class="grid gap-2 sm:grid-cols-2">
            <?php foreach (['muzaki' => 'Pembayar zakat, infak, atau sedekah', 'mustahik' => 'Penerima manfaat program BAZNAS'] as $nilai => $ket): ?>
            <label class="<?= $kelasPil ?>">
                <input type="radio" name="peran" value="<?= e($nilai) ?>" required class="size-4 accent-primary"<?= $peran === $nilai ? ' checked' : '' ?>
                       <?= galat('peran') ? 'aria-describedby="f-peran-error"' : '' ?>>
                <span><?= e(Testimoni::PERAN[$nilai]) ?><span class="block text-xs font-normal text-muted"><?= e($ket) ?></span></span>
            </label>
            <?php endforeach ?>
        </div>
        <?php if ($g = galat('peran')): ?><p id="f-peran-error" class="mt-1.5 text-sm font-medium text-red-700"><?= e($g) ?></p><?php endif ?>
    </fieldset>

    <div class="grid gap-5 sm:grid-cols-2">
        <?= $this->insert('components/form-field', ['name' => 'nama', 'label' => 'Nama', 'required' => true, 'autocomplete' => 'name',
            'hint' => 'Boleh nama panggilan atau inisial.', 'atribut' => ['maxlength' => 100]]) ?>
        <?= $this->insert('components/form-field', ['name' => 'profesi', 'label' => 'Keterangan (opsional)',
            'placeholder' => 'Mis. Pedagang, Toboali', 'hint' => 'Pekerjaan, asal, atau program yang diterima.', 'atribut' => ['maxlength' => 100]]) ?>
    </div>

    <fieldset>
        <legend class="mb-1.5 text-sm font-semibold text-ink">Penilaian Anda <span class="text-red-700" aria-hidden="true">*</span></legend>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($pilihNilai as $nilai => $ket): ?>
            <label class="<?= $kelasPil ?> py-2">
                <input type="radio" name="rating" value="<?= $nilai ?>" class="sr-only"<?= $rating === (string) $nilai ? ' checked' : '' ?>>
                <span class="text-accent" aria-hidden="true"><?= icon('star-solid', 'size-4') ?></span>
                <span><?= $nilai ?> · <?= e($ket) ?></span>
            </label>
            <?php endforeach ?>
        </div>
        <?php if ($g = galat('rating')): ?><p class="mt-1.5 text-sm font-medium text-red-700"><?= e($g) ?></p><?php endif ?>
    </fieldset>

    <?= $this->insert('components/form-field', ['name' => 'isi', 'label' => 'Cerita Anda', 'type' => 'textarea', 'required' => true,
        'placeholder' => 'Ceritakan pengalaman Anda menunaikan zakat atau menerima bantuan BAZNAS…',
        'hint' => 'Minimal 20 karakter, maksimal 1.000 karakter. Tanpa tautan.', 'atribut' => ['minlength' => 20, 'maxlength' => 1000]]) ?>

    <?= $this->insert('components/form-field', ['name' => 'no_hp', 'label' => 'Nomor WhatsApp (opsional)', 'type' => 'tel', 'inputmode' => 'tel', 'autocomplete' => 'tel',
        'placeholder' => '08…', 'hint' => 'Hanya untuk konfirmasi oleh admin bila diperlukan — tidak ditampilkan di situs.', 'atribut' => ['maxlength' => 20]]) ?>

    <div>
        <label class="flex cursor-pointer items-start gap-3 text-sm text-ink">
            <input type="checkbox" name="setuju" value="1" required class="mt-0.5 size-5 shrink-0 accent-primary"<?= old('setuju') !== '' ? ' checked' : '' ?>>
            <span>Saya setuju nama, keterangan, penilaian, dan cerita di atas ditampilkan di situs BAZNAS Kabupaten Bangka Selatan setelah diperiksa admin.</span>
        </label>
        <?php if ($g = galat('setuju')): ?><p class="mt-1.5 text-sm font-medium text-red-700">Centang persetujuan agar testimoni bisa ditampilkan.</p><?php endif ?>
    </div>

    <button type="submit" class="inline-flex min-h-12 items-center gap-2 rounded-full bg-primary px-6 font-bold text-white hover:bg-primary-dark">
        <?= icon('send', 'size-5') ?>Kirim testimoni
    </button>
</form>
<?php endif ?>
