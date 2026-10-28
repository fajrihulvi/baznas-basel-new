<?php
/**
 * /bayar-zakat — form 4 langkah (§12). zakat.js menampilkan satu langkah sekaligus;
 * tanpa JS keempat langkah tampil berurutan dan server tetap memvalidasi ulang semua isian.
 *
 * @var App\Core\View $this
 * @var list<array>   $jenis        id, nama, utama, mode (fitrah|fidyah|nominal), kalkulator
 * @var array         $param        parameter ZakatCalculator
 * @var string        $jenisDipilih id jenis terpilih (query/old)
 * @var string        $nominalAwal
 * @var string        $jumlahAwal   jiwa/hari dari kalkulator
 */

use App\Services\PembayaranService;

$this->layout('site', [
    'title'       => 'Bayar Zakat',
    'description' => 'Tunaikan zakat penghasilan, zakat fitrah, fidyah, infaq, dan sedekah secara online melalui BAZNAS Kabupaten Bangka Selatan.',
    'canonical'   => url('bayar-zakat'),
]);

$utama = array_values(array_filter($jenis, static fn (array $j): bool => $j['utama']));
$maal  = array_values(array_filter($jenis, static fn (array $j): bool => !$j['utama']));
$ikonJenis = static fn (array $j): string => match (true) {
    $j['mode'] === 'fitrah'                      => 'heart',
    $j['mode'] === 'fidyah'                      => 'calendar',
    $j['kalkulator'] === 'penghasilan'           => 'users',
    $j['kalkulator'] === 'emas'                  => 'star',
    $j['kalkulator'] === 'perdagangan'           => 'store',
    str_contains(mb_strtolower($j['nama']), 'infaq')   => 'hand-heart',
    str_contains(mb_strtolower($j['nama']), 'sedekah') => 'hand-heart',
    default                                      => 'chart',
};
$modeDipilih = 'nominal';
$kalkulatorDipilih = null;
foreach ($jenis as $j) {
    if ((string) $j['id'] === $jenisDipilih) {
        $modeDipilih = $j['mode'];
        $kalkulatorDipilih = $j['kalkulator'];
    }
}
$rupiah = ['data-rupiah' => '', 'inputmode' => 'numeric'];
$langkah = ['Jenis', 'Nominal', 'Data diri', 'Ringkasan'];

// Keadaan awal dirender server = keadaan yang akan diatur zakat.js → tidak ada pergeseran layout (CLS)
$mulai = $jenisDipilih !== '' && galat('jenis') === null ? 2 : 1;
foreach (['jenis' => 1, 'jiwa' => 2, 'hari' => 2, 'nominal' => 2, 'sapaan' => 3, 'nama_lengkap' => 3, 'no_hp' => 3, 'email' => 3, 'npwz' => 3] as $field => $nomor) {
    if (galat($field) !== null) {
        $mulai = $nomor;
        break;
    }
}
$tampilGrup = [
    'jiwa'    => $modeDipilih === 'fitrah',
    'hari'    => $modeDipilih === 'fidyah',
    'nominal' => $modeDipilih === 'nominal' || ($modeDipilih === 'fitrah' && !$param['nilai_fitrah']) || ($modeDipilih === 'fidyah' && !$param['nilai_fidyah']),
];
$sembunyi = static fn (bool $tampil): string => $tampil ? '' : ' hidden';
// Langkah & grup isian: tersembunyi lewat data-js-sembunyi agar <noscript> bisa menampilkannya
$tersembunyi = static fn (bool $tampil): string => $tampil ? '' : ' data-js-sembunyi';
$kelasBulatan = static fn (int $ke): string => 'grid size-9 place-items-center rounded-full border-2 text-sm '
    . ($ke < $mulai ? 'border-primary bg-primary text-white' : ($ke === $mulai ? 'border-primary bg-white text-primary' : 'border-gray-300 bg-white'));

$kartuJenis = function (array $j) use ($jenisDipilih, $ikonJenis): string {
    $checked = (string) $j['id'] === $jenisDipilih ? ' checked' : '';
    return '<label class="relative flex min-h-24 cursor-pointer flex-col justify-between gap-2 rounded-2xl border-2 border-gray-200 bg-white p-4 transition hover:border-primary/50 has-checked:border-primary has-checked:bg-primary/5 has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-primary">'
        . '<input type="radio" name="jenis" value="' . (int) $j['id'] . '" class="sr-only" data-mode="' . e($j['mode']) . '" data-kalkulator="' . e((string) $j['kalkulator']) . '" data-nama="' . e($j['nama']) . '"' . $checked . '>'
        . '<span class="grid size-10 place-items-center rounded-xl bg-primary/10 text-primary">' . icon($ikonJenis($j), 'size-5') . '</span>'
        . '<span class="text-sm font-bold leading-snug text-ink">' . e($j['nama']) . '</span>'
        . '</label>';
};

// Kalkulator mini di langkah 2 (tanpa atribut name → tidak ikut terkirim)
$kalkulatorMini = [
    'penghasilan' => ['Hitung zakat penghasilan', [['gaji', 'Penghasilan per bulan'], ['bonus', 'Bonus, THR, dan lainnya (per bulan)']]],
    'emas'        => ['Hitung zakat emas', [['gram', 'Berat emas simpanan (gram)']]],
    'perdagangan' => ['Hitung zakat perdagangan', [['modal', 'Modal yang diputar'], ['untung', 'Keuntungan setahun'], ['piutang', 'Piutang lancar'], ['utang', 'Utang jatuh tempo']]],
];
?>
<?= $this->insert('components/page-header', [
    'judul'      => 'Bayar Zakat',
    'subjudul'   => 'Tunaikan zakat, infaq, dan sedekah dalam empat langkah mudah.',
    'breadcrumb' => [['label' => 'Layanan'], ['label' => 'Bayar Zakat']],
]) ?>

<?php $this->start('head') ?>
    <noscript><style>
        /* Tanpa JS: keempat langkah tampil berurutan sebagai satu form panjang */
        form[data-bayar] fieldset[data-langkah], form[data-bayar] [data-grup] { display: block !important; }
        form[data-bayar] [data-indikator], form[data-bayar] [data-navigasi], form[data-bayar] [data-ringkasan],
        form[data-bayar] [data-nominal-cepat-grup] { display: none !important; }
        form[data-bayar] fieldset[data-langkah] + fieldset { margin-top: 2.5rem; }
    </style></noscript>
<?php $this->stop() ?>

<div class="mx-auto max-w-3xl px-4 py-8 lg:py-12">
    <form method="post" action="<?= e(url('bayar-zakat')) ?>" data-bayar data-param-zakat
          data-mulai="<?= $mulai ?>"
          data-harga-emas="<?= (int) $param['harga_emas'] ?>"
          data-nishab-bulanan="<?= (int) $param['nishab_bulanan'] ?>"
          data-nishab-tahunan="<?= (int) $param['nishab_tahunan'] ?>"
          data-nilai-fitrah="<?= (int) $param['nilai_fitrah'] ?>"
          data-nilai-fidyah="<?= (int) $param['nilai_fidyah'] ?>">
        <?= csrf_field() ?>
        <div class="hidden" aria-hidden="true">
            <label>Jangan diisi <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
        </div>

        <ol class="mb-8 grid grid-cols-4 gap-2" data-indikator aria-label="Langkah pembayaran">
            <?php foreach ($langkah as $i => $nama): ?>
            <li class="flex flex-col items-center gap-1.5 text-center text-xs font-semibold text-muted<?= $i + 1 <= $mulai ? ' text-primary' : '' ?>" data-indikator-langkah="<?= $i + 1 ?>"<?= $i + 1 === $mulai ? ' aria-current="step"' : '' ?>>
                <span class="<?= $kelasBulatan($i + 1) ?>"><?= $i + 1 ?></span>
                <?= e($nama) ?>
            </li>
            <?php endforeach ?>
        </ol>

        <?php /* Langkah 1 — jenis */ ?>
        <fieldset data-langkah="1" class="space-y-4"<?= $tersembunyi($mulai === 1) ?>>
            <legend class="mb-4 text-xl font-bold text-ink">1. Pilih jenis</legend>
            <?php if ($pesan = galat('jenis')): ?>
            <p class="flex items-center gap-1.5 text-sm font-medium text-red-700" id="galat-jenis"><?= icon('alert', 'size-4') ?><?= e($pesan) ?></p>
            <?php endif ?>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <?php foreach ($utama as $j): ?><?= $kartuJenis($j) ?><?php endforeach ?>
            </div>
            <?php if ($maal !== []): ?>
            <p class="pt-2 text-sm font-bold uppercase tracking-wide text-muted">Zakat maal lainnya</p>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <?php foreach ($maal as $j): ?><?= $kartuJenis($j) ?><?php endforeach ?>
            </div>
            <?php endif ?>
        </fieldset>

        <?php /* Langkah 2 — nominal */ ?>
        <fieldset data-langkah="2" class="space-y-5"<?= $tersembunyi($mulai === 2) ?>>
            <legend class="mb-4 text-xl font-bold text-ink">2. Tentukan nominal</legend>

            <div data-grup="jiwa" class="space-y-4"<?= $tersembunyi($tampilGrup['jiwa']) ?>>
                <?= $this->insert('components/form-field', [
                    'name' => 'jiwa', 'label' => 'Jumlah jiwa (zakat fitrah)', 'type' => 'number', 'value' => old('jiwa', $modeDipilih === 'fitrah' ? $jumlahAwal : ''),
                    'atribut' => ['min' => 1, 'max' => 100, 'inputmode' => 'numeric', 'data-wajib' => ''],
                    'hint' => $param['nilai_fitrah'] ? format_rupiah($param['nilai_fitrah']) . ' per jiwa.' : 'Nilai per jiwa belum ditetapkan di situs ini — isi total nominal sesuai ketetapan BAZNAS Bangka Selatan.',
                ]) ?>
            </div>

            <div data-grup="hari" class="space-y-4"<?= $tersembunyi($tampilGrup['hari']) ?>>
                <?= $this->insert('components/form-field', [
                    'name' => 'hari', 'label' => 'Jumlah hari (fidyah)', 'type' => 'number', 'value' => old('hari', $modeDipilih === 'fidyah' ? $jumlahAwal : ''),
                    'atribut' => ['min' => 1, 'max' => 365, 'inputmode' => 'numeric', 'data-wajib' => ''],
                    'hint' => $param['nilai_fidyah'] ? format_rupiah($param['nilai_fidyah']) . ' per hari.' : null,
                ]) ?>
            </div>

            <div data-grup="nominal" class="space-y-4"<?= $tersembunyi($tampilGrup['nominal']) ?>>
                <?= $this->insert('components/form-field', [
                    'name' => 'nominal', 'label' => 'Nominal (Rp)', 'value' => $nominalAwal !== '' ? number_format(angka($nominalAwal), 0, ',', '.') : '',
                    'placeholder' => 'mis. 100.000', 'atribut' => $rupiah + ['data-wajib' => '', 'autocomplete' => 'off'],
                    'hint' => 'Minimal ' . format_rupiah(PembayaranService::MIN_NOMINAL) . '.',
                ]) ?>
                <div class="flex flex-wrap gap-2" data-nominal-cepat-grup<?= $sembunyi($modeDipilih === 'nominal' && $kalkulatorDipilih === null) ?>>
                    <?php foreach ([50000, 100000, 250000, 500000, 1000000] as $n): ?>
                    <button type="button" data-nominal-cepat="<?= $n ?>" class="inline-flex h-10 items-center rounded-full border border-gray-300 bg-white px-4 text-sm font-semibold hover:border-primary hover:text-primary"><?= e(format_rupiah($n)) ?></button>
                    <?php endforeach ?>
                </div>

                <?php foreach ($kalkulatorMini as $kunci => [$judul, $isian]): ?>
                <details data-hitung="<?= e($kunci) ?>" class="group rounded-2xl border border-gray-200 bg-surface"<?= $sembunyi($kalkulatorDipilih === $kunci) ?>>
                    <summary class="flex min-h-12 cursor-pointer items-center gap-2 px-4 font-semibold text-primary">
                        <?= icon('calculator', 'size-5') ?><?= e($judul) ?><?= icon('chevron-down', 'ml-auto size-4 transition-transform group-open:rotate-180') ?>
                    </summary>
                    <div class="space-y-4 px-4 pb-4">
                        <?php foreach ($isian as [$nama, $label]): ?>
                        <?= $this->insert('components/form-field', [
                            'name' => '', 'nameAttr' => '', 'id' => "mini-{$kunci}-{$nama}", 'label' => $label, 'placeholder' => '0',
                            'atribut' => ($nama === 'gram' ? ['inputmode' => 'decimal'] : $rupiah) + ['data-isian' => $nama],
                        ]) ?>
                        <?php endforeach ?>
                        <?php if ($param['harga_emas'] === null): ?>
                        <?= $this->insert('components/form-field', [
                            'name' => '', 'nameAttr' => '', 'id' => "mini-{$kunci}-harga-emas", 'label' => 'Harga emas hari ini (per gram)', 'placeholder' => 'mis. 2.000.000',
                            'atribut' => $rupiah + ['data-isian' => 'harga_emas'], 'hint' => 'Nishab dihitung dari 85 gram emas.',
                        ]) ?>
                        <?php endif ?>
                        <div data-hasil-mini aria-live="polite" class="text-sm text-muted">Isi data di atas untuk menghitung.</div>
                        <button type="button" data-pakai-hasil hidden class="inline-flex h-11 items-center gap-2 rounded-full bg-primary px-4 text-sm font-bold text-white hover:bg-primary-dark">Gunakan nominal ini</button>
                    </div>
                </details>
                <?php endforeach ?>
            </div>

            <p class="hidden rounded-2xl bg-primary/5 p-4 text-ink" data-total aria-live="polite"></p>
        </fieldset>

        <?php /* Langkah 3 — data muzaki */ ?>
        <fieldset data-langkah="3" class="space-y-4"<?= $tersembunyi($mulai === 3) ?>>
            <legend class="mb-4 text-xl font-bold text-ink">3. Data diri</legend>
            <div>
                <p class="mb-1.5 text-sm font-semibold text-ink" id="label-sapaan">Sapaan <span class="text-red-700" aria-hidden="true">*</span></p>
                <div class="flex gap-2" role="radiogroup" aria-labelledby="label-sapaan"<?= galat('sapaan') ? ' aria-describedby="galat-sapaan"' : '' ?>>
                    <?php foreach (['BAPAK' => 'Bapak', 'IBU' => 'Ibu'] as $nilaiSapaan => $labelSapaan): ?>
                    <label class="inline-flex h-12 cursor-pointer items-center rounded-full border-2 border-gray-300 bg-white px-5 font-semibold has-checked:border-primary has-checked:bg-primary has-checked:text-white has-focus-visible:outline-2 has-focus-visible:outline-offset-2 has-focus-visible:outline-primary">
                        <input type="radio" name="sapaan" value="<?= $nilaiSapaan ?>" class="sr-only" required<?= old('sapaan') === $nilaiSapaan ? ' checked' : '' ?>><?= $labelSapaan ?>
                    </label>
                    <?php endforeach ?>
                </div>
                <?php if ($pesan = galat('sapaan')): ?>
                <p id="galat-sapaan" class="mt-1.5 flex items-center gap-1.5 text-sm font-medium text-red-700"><?= icon('alert', 'size-4') ?><?= e($pesan) ?></p>
                <?php endif ?>
            </div>
            <?= $this->insert('components/form-field', ['name' => 'nama_lengkap', 'label' => 'Nama lengkap', 'required' => true, 'autocomplete' => 'name', 'atribut' => ['maxlength' => 100, 'minlength' => 3]]) ?>
            <?= $this->insert('components/form-field', ['name' => 'no_hp', 'label' => 'Nomor WhatsApp', 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel', 'inputmode' => 'tel', 'placeholder' => '08xxxxxxxxxx', 'hint' => 'Untuk konfirmasi dan pengiriman Bukti Setor Zakat.', 'atribut' => ['maxlength' => 16]]) ?>
            <?= $this->insert('components/form-field', ['name' => 'email', 'label' => 'Email (opsional)', 'type' => 'email', 'autocomplete' => 'email', 'atribut' => ['maxlength' => 100]]) ?>
            <?= $this->insert('components/form-field', ['name' => 'npwz', 'label' => 'NPWZ (opsional)', 'hint' => 'Nomor Pokok Wajib Zakat, bila sudah terdaftar.', 'atribut' => ['maxlength' => 50]]) ?>
        </fieldset>

        <?php /* Langkah 4 — ringkasan */ ?>
        <fieldset data-langkah="4" class="space-y-4" data-js-sembunyi>
            <legend class="mb-4 text-xl font-bold text-ink">4. Ringkasan</legend>
            <dl class="divide-y divide-gray-200 rounded-2xl border border-gray-200 bg-white" data-ringkasan></dl>
            <noscript><p class="text-sm text-muted">Periksa kembali isian Anda sebelum mengirim.</p></noscript>
            <?= $this->insert('components/alert', ['pesan' => 'Setelah dikirim, Anda akan mendapat kode pembayaran dan nomor rekening resmi tujuan transfer.']) ?>
            <button type="submit" class="inline-flex min-h-14 w-full items-center justify-center gap-2 rounded-full bg-primary px-7 text-lg font-bold text-white hover:bg-primary-dark sm:w-auto">
                Kirim &amp; lihat instruksi transfer <?= icon('arrow-right', 'size-5') ?>
            </button>
        </fieldset>

        <div class="mt-8 flex items-center justify-between gap-3" data-navigasi>
            <button type="button" data-kembali<?= $mulai === 1 ? ' hidden' : '' ?> class="inline-flex min-h-12 items-center gap-2 rounded-full border-2 border-gray-300 px-5 font-bold text-ink hover:border-primary hover:text-primary"><?= icon('chevron-left', 'size-5') ?>Kembali</button>
            <button type="button" data-lanjut class="ml-auto inline-flex min-h-12 items-center gap-2 rounded-full bg-primary px-6 font-bold text-white hover:bg-primary-dark">Lanjut<?= icon('chevron-right', 'size-5') ?></button>
        </div>
    </form>
</div>

<?php $this->start('scripts') ?>
    <script type="module" src="<?= e(asset('js/zakat.js')) ?>"></script>
<?php $this->stop() ?>
