<?php
/**
 * /kalkulator-zakat — 5 kalkulator (tab). Dihitung langsung oleh zakat.js; tanpa JS form dikirim (GET)
 * dan dihitung ZakatCalculator di server.
 *
 * @var App\Core\View $this
 * @var string        $aktif    kunci kalkulator aktif
 * @var array         $param    harga_emas, nishab_bulanan, nishab_tahunan, nilai_fitrah, nilai_fidyah (null = belum diatur)
 * @var array<string, int> $idJenis kunci kalkulator => id jenis_zakat (tujuan "Tunaikan")
 * @var array         $input    query string (isian tanpa JS)
 * @var array|null    $hasil    hasil server untuk kalkulator aktif
 */

use App\Services\ZakatCalculator;

$this->layout('site', [
    'title'       => 'Kalkulator Zakat',
    'description' => 'Hitung zakat penghasilan, zakat emas, zakat perdagangan, zakat fitrah, dan fidyah sesuai nishab dan kadar 2,5%.',
    'canonical'   => url('kalkulator-zakat'),
]);

$nilai = static fn (string $kunci): string => isset($input[$kunci]) && is_scalar($input[$kunci]) ? (string) $input[$kunci] : '';
$rupiah = ['data-rupiah' => '', 'inputmode' => 'numeric'];
$fieldHargaEmas = function (string $panel) use ($param, $nilai, $rupiah): string {
    if ($param['harga_emas'] !== null) {
        return '';
    }
    return $this->insert('components/form-field', [
        'name' => 'harga_emas', 'id' => "k-{$panel}-harga-emas", 'label' => 'Harga emas hari ini (per gram)',
        'value' => $nilai('harga_emas'), 'placeholder' => 'mis. 2.000.000', 'atribut' => $rupiah + ['data-harga-emas-input' => ''],
        'hint' => 'Nishab dihitung dari 85 gram emas. Cek harga emas hari ini (mis. Antam/Pegadaian).',
    ]);
};

$deskripsi = [
    'penghasilan' => 'Untuk gaji, honor, atau pendapatan rutin. Wajib bila penghasilan per bulan mencapai nishab (setara 85 gram emas per tahun ÷ 12). Kadar 2,5%.',
    'emas'        => 'Untuk emas simpanan yang sudah dimiliki selama satu tahun (haul). Wajib bila mencapai 85 gram. Kadar 2,5%. Emas perhiasan yang dipakai wajar tidak dihitung.',
    'perdagangan' => 'Untuk usaha dagang. Dihitung dari modal yang diputar + keuntungan + piutang lancar − utang jatuh tempo, setelah satu tahun. Wajib bila mencapai nishab 85 gram emas. Kadar 2,5%.',
    'fitrah'      => 'Wajib bagi setiap jiwa muslim, ditunaikan di bulan Ramadan sebelum salat Idulfitri.',
    'fidyah'      => 'Pengganti puasa Ramadan bagi yang tidak mampu berpuasa (sakit menahun, lanjut usia, dsb.), dihitung per hari yang ditinggalkan.',
];
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Kalkulator Zakat',
    'subjudul'   => 'Hitung kewajiban zakat Anda, lalu tunaikan langsung secara online.',
    'breadcrumb' => [['label' => 'Layanan'], ['label' => 'Kalkulator Zakat']],
]) ?>

<div class="mx-auto max-w-4xl px-4 py-8 lg:py-12" data-tabs
     data-url-bayar="<?= e(url('bayar-zakat')) ?>"
     data-param-zakat
     data-harga-emas="<?= (int) $param['harga_emas'] ?>"
     data-nishab-bulanan="<?= (int) $param['nishab_bulanan'] ?>"
     data-nishab-tahunan="<?= (int) $param['nishab_tahunan'] ?>"
     data-nilai-fitrah="<?= (int) $param['nilai_fitrah'] ?>"
     data-nilai-fidyah="<?= (int) $param['nilai_fidyah'] ?>">

    <?= $this->insert('components/chip-tabs', [
        'label' => 'Jenis kalkulator',
        'items' => array_map(static fn (string $k, string $l): array => [
            'label' => $l, 'url' => '/kalkulator-zakat?jenis=' . $k, 'aktif' => $k === $aktif, 'tab' => $k,
        ], array_keys(ZakatCalculator::JENIS), ZakatCalculator::JENIS),
    ]) ?>

    <?php foreach (ZakatCalculator::JENIS as $k => $label): ?>
    <div class="mt-6" data-tab-panel="<?= e($k) ?>"<?= $k === $aktif ? '' : ' hidden' ?>>
        <form method="get" action="<?= e(url('kalkulator-zakat')) ?>" data-kalkulator="<?= e($k) ?>" data-id-jenis="<?= (int) ($idJenis[$k] ?? 0) ?>"
              class="grid gap-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm lg:grid-cols-2 lg:gap-10 lg:p-8">
            <input type="hidden" name="jenis" value="<?= e($k) ?>">
            <input type="hidden" name="hitung" value="1">
            <div class="space-y-4">
                <h2 class="text-xl font-bold text-ink">Zakat <?= e($label) ?></h2>
                <p class="text-sm text-muted"><?= e($deskripsi[$k]) ?></p>

                <?php if ($k === 'penghasilan'): ?>
                <?= $this->insert('components/form-field', ['name' => 'gaji', 'id' => 'k-penghasilan-gaji', 'label' => 'Penghasilan per bulan', 'value' => $nilai('gaji'), 'placeholder' => '0', 'atribut' => $rupiah]) ?>
                <?= $this->insert('components/form-field', ['name' => 'bonus', 'id' => 'k-penghasilan-bonus', 'label' => 'Bonus, THR, dan lainnya (per bulan)', 'value' => $nilai('bonus'), 'placeholder' => '0', 'atribut' => $rupiah]) ?>
                <?= $fieldHargaEmas('penghasilan') ?>
                <?php elseif ($k === 'emas'): ?>
                <?= $this->insert('components/form-field', ['name' => 'gram', 'id' => 'k-emas-gram', 'label' => 'Berat emas simpanan (gram)', 'type' => 'number', 'value' => $nilai('gram'), 'placeholder' => '0', 'atribut' => ['min' => 0, 'step' => '0.01', 'inputmode' => 'decimal']]) ?>
                <?= $fieldHargaEmas('emas') ?>
                <?php elseif ($k === 'perdagangan'): ?>
                <?= $this->insert('components/form-field', ['name' => 'modal', 'id' => 'k-dagang-modal', 'label' => 'Modal yang diputar (stok barang)', 'value' => $nilai('modal'), 'placeholder' => '0', 'atribut' => $rupiah]) ?>
                <?= $this->insert('components/form-field', ['name' => 'untung', 'id' => 'k-dagang-untung', 'label' => 'Keuntungan (setahun)', 'value' => $nilai('untung'), 'placeholder' => '0', 'atribut' => $rupiah]) ?>
                <?= $this->insert('components/form-field', ['name' => 'piutang', 'id' => 'k-dagang-piutang', 'label' => 'Piutang lancar', 'value' => $nilai('piutang'), 'placeholder' => '0', 'atribut' => $rupiah]) ?>
                <?= $this->insert('components/form-field', ['name' => 'utang', 'id' => 'k-dagang-utang', 'label' => 'Utang jatuh tempo', 'value' => $nilai('utang'), 'placeholder' => '0', 'atribut' => $rupiah]) ?>
                <?= $fieldHargaEmas('perdagangan') ?>
                <?php elseif ($k === 'fitrah'): ?>
                <?= $this->insert('components/form-field', ['name' => 'jiwa', 'id' => 'k-fitrah-jiwa', 'label' => 'Jumlah jiwa', 'type' => 'number', 'value' => $nilai('jiwa'), 'placeholder' => '1', 'atribut' => ['min' => 1, 'max' => 100, 'inputmode' => 'numeric'],
                    'hint' => $param['nilai_fitrah'] ? 'Nilai zakat fitrah ' . format_rupiah($param['nilai_fitrah']) . ' per jiwa.' : null]) ?>
                <?php if (!$param['nilai_fitrah']): ?>
                <?= $this->insert('components/form-field', ['name' => 'nilai', 'id' => 'k-fitrah-nilai', 'label' => 'Nilai zakat fitrah per jiwa', 'value' => $nilai('nilai'), 'placeholder' => '0', 'atribut' => $rupiah,
                    'hint' => 'Besarannya mengikuti ketetapan BAZNAS Kabupaten Bangka Selatan tahun berjalan.']) ?>
                <?php endif ?>
                <?php else: ?>
                <?= $this->insert('components/form-field', ['name' => 'hari', 'id' => 'k-fidyah-hari', 'label' => 'Jumlah hari puasa yang ditinggalkan', 'type' => 'number', 'value' => $nilai('hari'), 'placeholder' => '1', 'atribut' => ['min' => 1, 'max' => 365, 'inputmode' => 'numeric'],
                    'hint' => $param['nilai_fidyah'] ? 'Fidyah ' . format_rupiah($param['nilai_fidyah']) . ' per hari.' : null]) ?>
                <?php if (!$param['nilai_fidyah']): ?>
                <?= $this->insert('components/form-field', ['name' => 'nilai', 'id' => 'k-fidyah-nilai', 'label' => 'Fidyah per hari', 'value' => $nilai('nilai'), 'placeholder' => '0', 'atribut' => $rupiah]) ?>
                <?php endif ?>
                <?php endif ?>

                <button type="submit" data-tombol-hitung class="inline-flex min-h-12 items-center gap-2 rounded-full bg-primary px-5 font-bold text-white hover:bg-primary-dark"><?= icon('calculator', 'size-5') ?>Hitung</button>
            </div>

            <div class="rounded-2xl bg-surface p-5 lg:p-6" data-hasil aria-live="polite">
                <?php if ($k === $aktif && $hasil !== null): ?>
                <?= $this->insert('components/hasil-zakat', ['hasil' => $hasil, 'idJenis' => (int) ($idJenis[$k] ?? 0), 'kunci' => $k, 'input' => $input]) ?>
                <?php else: ?>
                <p class="text-sm font-semibold uppercase tracking-wide text-muted">Hasil perhitungan</p>
                <p class="mt-2 text-muted">Isi data di samping untuk melihat besaran zakat Anda.</p>
                <?php endif ?>
            </div>
        </form>
    </div>
    <?php endforeach ?>

    <p class="mt-6 text-sm text-muted">
        Perhitungan ini bersifat perkiraan berdasarkan nishab 85 gram emas dan kadar 2,5%. Untuk kasus khusus, silakan
        <a href="#kontak" class="font-semibold text-primary underline">konsultasi dengan BAZNAS Bangka Selatan</a>.
    </p>
</div>

<?php $this->start('scripts') ?>
    <script type="module" src="<?= e(asset('js/tabs.js')) ?>"></script>
    <script type="module" src="<?= e(asset('js/zakat.js')) ?>"></script>
<?php $this->stop() ?>
