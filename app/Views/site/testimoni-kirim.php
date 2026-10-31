<?php
/**
 * /testimoni/kirim — form testimoni (cadangan tanpa JavaScript; tautannya juga bisa dibagikan ke muzaki/mustahik).
 *
 * @var App\Core\View $this
 */
$this->layout('site', [
    'title'       => 'Kirim Testimoni',
    'description' => 'Bagikan pengalaman Anda sebagai muzaki atau mustahik BAZNAS Kabupaten Bangka Selatan.',
]);
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Bagikan Pengalaman Anda',
    'subjudul'   => 'Muzaki atau mustahik? Ceritakan pengalaman Anda bersama BAZNAS Bangka Selatan.',
    'breadcrumb' => [['label' => 'Testimoni', 'url' => '/testimoni'], ['label' => 'Kirim Testimoni']],
]) ?>

<div class="mx-auto max-w-3xl px-4 py-8 lg:py-12">
    <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:p-8">
        <?= $this->insert('components/form-testimoni', ['asal' => 'halaman']) ?>
    </div>
</div>
