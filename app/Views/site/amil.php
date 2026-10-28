<?php
/**
 * /amil — pimpinan & pelaksana BAZNAS Bangka Selatan.
 *
 * @var App\Core\View $this
 * @var list<array>   $pimpinan   id, nama, jabatan, bidang, foto
 * @var list<array>   $pelaksana
 */
$this->layout('site', [
    'title'       => 'Amil BAZNAS',
    'description' => 'Pimpinan dan pelaksana (amil) BAZNAS Kabupaten Bangka Selatan.',
]);

$inisial = static fn (string $nama): string => mb_strtoupper(implode('', array_map(
    static fn (string $k): string => mb_substr($k, 0, 1),
    array_slice(preg_split('/\s+/', trim($nama)) ?: [], 0, 2)
)));
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Amil BAZNAS',
    'subjudul'   => 'Orang-orang yang mengemban amanah pengelolaan zakat di Bangka Selatan.',
    'breadcrumb' => [['label' => 'Tentang Kami', 'url' => '/tentang'], ['label' => 'Amil BAZNAS']],
]) ?>

<div class="mx-auto max-w-6xl space-y-12 px-4 py-8 lg:py-12">
    <?php if ($pimpinan === [] && $pelaksana === []): ?>
    <?= $this->insert('components/empty-state', ['judul' => 'Data amil sedang disiapkan', 'pesan' => 'Profil pimpinan dan pelaksana akan segera ditampilkan.', 'ikon' => 'users']) ?>
    <?php endif ?>

    <?php foreach (['Pimpinan' => $pimpinan, 'Pelaksana' => $pelaksana] as $judulGrup => $daftar): ?>
    <?php if ($daftar !== []): ?>
    <section aria-labelledby="amil-<?= e(strtolower($judulGrup)) ?>">
        <?= $this->insert('components/section-title', ['judul' => $judulGrup, 'id' => 'amil-' . strtolower($judulGrup)]) ?>
        <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <?php foreach ($daftar as $a): ?>
            <li class="rounded-2xl border border-gray-200 bg-white p-4 text-center">
                <div class="mx-auto size-24 overflow-hidden rounded-full bg-surface">
                    <?php if ($a['foto']): ?>
                    <?= img($a['foto'], 'amil', '', ['sizes' => '96px', 'class' => 'size-full object-cover']) ?>
                    <?php else: ?>
                    <span class="grid size-full place-items-center text-2xl font-bold text-primary" aria-hidden="true"><?= e($inisial($a['nama'])) ?></span>
                    <?php endif ?>
                </div>
                <h3 class="mt-3 font-bold leading-snug text-ink"><?= e($a['nama']) ?></h3>
                <p class="mt-0.5 text-sm text-muted"><?= e($a['jabatan']) ?></p>
            </li>
            <?php endforeach ?>
        </ul>
    </section>
    <?php endif ?>
    <?php endforeach ?>
</div>
