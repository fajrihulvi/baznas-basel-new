<?php
/**
 * Ikon dua warna bottom nav, digambar ulang sebagai SVG dari public/icon/*.(svg|png)
 * (PNG aslinya ±1 MB berlatar putih; SVG inline < 1 KB & warnanya bisa diatur per keadaan).
 * Dekoratif — label teks ada di sebelahnya.
 *
 * Aktif : hijau asli + naik & membesar saat di-hover/fokus.
 * Pasif : satu warna (currentColor) seperti ikon lain; $buka = berwarna saat tombol induk aria-expanded (Menu terbuka);
 *         $berwarna = selalu berwarna (tombol tengah Bayar Zakat), animasi hover tetap hanya saat aktif.
 *
 * @var string $nama     beranda | program | kabar | menu | bayar-zakat
 * @var bool   $aktif
 * @var bool   $buka
 * @var bool   $berwarna
 * @var string $ukuran   class ukuran, default size-6
 * @var string|null $gambar berkas di uploads/ikon-nav (admin → Ikon Navigasi); null = SVG bawaan
 */
$buka ??= false;
$warna = $aktif || !empty($berwarna);
$ukuran ??= 'size-6';
$gelap  = $warna ? 'fill-[#005332]' : 'fill-current' . ($buka ? ' group-aria-expanded:fill-[#005332]' : '');
$terang = $warna ? 'fill-[#079E61]' : 'fill-current opacity-50' . ($buka ? ' group-aria-expanded:fill-[#079E61] group-aria-expanded:opacity-100' : '');
$garis  = $warna ? 'stroke-[#005332]' : 'stroke-current' . ($buka ? ' group-aria-expanded:stroke-[#005332]' : ''); // class ditulis utuh agar terbaca Tailwind
$kuning = 'fill-[#FACC15]'; // hanya ikon Bayar Zakat (selalu berwarna)
$gerak  = ' transition-transform duration-200 ease-out group-hover:-translate-y-0.5 group-hover:scale-115 group-focus-visible:-translate-y-0.5 group-focus-visible:scale-115 group-active:scale-95 motion-reduce:transition-none motion-reduce:transform-none';

// Gambar unggahan admin (Ikon Navigasi) menggantikan SVG bawaan. Pasif = keabu-abuan seperti ikon lain,
// aktif/berwarna = warna asli; hover & Menu terbuka mengembalikan warnanya.
if (!empty($gambar)) {
    $filter = $warna ? '' : ' opacity-60 grayscale transition group-hover:opacity-100 group-hover:grayscale-0'
        . ($buka ? ' group-aria-expanded:opacity-100 group-aria-expanded:grayscale-0' : '');
    echo img($gambar, 'ikon-nav', '', ['sizes' => '80px', 'class' => $ukuran . ' object-contain' . $filter . ($aktif ? $gerak : '')]);

    return;
}

// viewBox dipotong ke area gambar (±95%) agar besar visual keempat ikon setara
$ikon = match ($nama) {
    'beranda' => ['92 62 850 850', [
        [$gelap, '<path d="M112 467 L546 113 Q576 88 606 113 L702 191 L702 166 Q702 145 723 145 L800 145 Q821 145 821 166 L821 292 L912 467 Q923 486 907 502 L882 524 Q862 541 842 526 L576 310 L194 526 Q174 541 154 524 L129 502 Q113 486 112 467 Z"/>'],
        [$terang, '<path d="M210 518 L512 278 Q526 267 540 278 L814 498 L814 850 Q814 886 778 886 L246 886 Q210 886 210 850 Z"/>'],
        [$gelap, '<path d="M405 886 L405 650 Q405 600 455 600 L569 600 Q619 600 619 650 L619 886 Z"/>'],
    ]],
    'program' => ['179 159 920 920', [
        [$gelap, '<rect x="204" y="199" width="411" height="400" rx="58"/>'],
        [$terang, '<rect x="662" y="199" width="411" height="400" rx="58"/>'],
        [$terang, '<rect x="204" y="640" width="411" height="400" rx="58"/>'],
        [$gelap, '<rect x="662" y="640" width="411" height="400" rx="58"/>'],
    ]],
    'kabar' => ['136 109 1000 1000', [
        [$garis, '<circle cx="636" cy="609" r="431" fill="none" stroke-width="90"/>'],
        [$terang, '<circle cx="638" cy="402" r="86"/>'],
        [$terang, '<rect x="554" y="534" width="166" height="374" rx="38"/>'],
    ]],
    'menu' => ['152 161 976 976', [
        [$gelap, '<circle cx="289" cy="378" r="111"/>'],
        [$terang, '<rect x="438" y="280" width="666" height="196" rx="98"/>'],
        [$terang, '<circle cx="288" cy="650" r="111"/>'],
        [$gelap, '<rect x="438" y="552" width="666" height="196" rx="98"/>'],
        [$gelap, '<circle cx="287" cy="917" r="111"/>'],
        [$terang, '<rect x="438" y="818" width="666" height="196" rx="98"/>'],
    ]],
    // public/icon/bayarzakat-icon.svg (tanpa latar putih); warna hijau diseragamkan dengan ikon lain
    'bayar-zakat' => ['15 40 450 450', [
        [$gelap, '<path d="M184 86c-17 0-29 7-39 19l-35 40c-8 9-10 23-4 33l14 21c5 8 15 10 23 5l39-26c10-7 21-9 34-7l49 8c13 2 25-1 36-9l35-25-16-61-26 10c-7 3-15 4-23 3l-57-9c-11-2-21-2-30-2z"/>'],
        [$terang, '<path d="M329 66l42-15c8-3 17 2 20 10l19 61c2 8-2 16-10 19l-43 14z"/>'],
        [$kuning, '<circle cx="173" cy="202" r="31"/>'],
        [$terang, '<path d="M118 242c18 8 38 13 58 13s40-5 58-13l-17 37H135z"/>'],
        [$gelap, '<rect x="133" y="267" width="87" height="19" rx="9.5"/>'],
        [$terang, '<path d="M143 284c-6 18-21 32-37 52-22 28-37 65-37 94 0 28 22 48 51 48h112c29 0 51-20 51-48 0-29-15-66-37-94-16-20-31-34-37-52z"/>'],
        [$kuning, '<path d="M176 322l18 22h27l-2 28 20 20-20 20 2 28h-27l-18 22-18-22h-27l2-28-20-20 20-20-2-28h27z"/>'],
        [$gelap, '<path d="M176 337l12 15h19l-1 20 14 20-14 14 1 20h-19l-12 15-12-15h-19l1-20-14-14 14-20-1-20h19z"/>'],
        [$kuning, '<path d="M190 362a27 27 0 1 0 20 45 24 24 0 0 1-20-45z"/>'],
    ]],
};
[$viewBox, $bentuk] = $ikon;
?>
<svg class="<?= $ukuran ?><?= $aktif ? $gerak : '' ?>" viewBox="<?= $viewBox ?>" aria-hidden="true" focusable="false">
    <?php foreach ($bentuk as [$kelas, $el]): ?>
    <?= str_replace(['<path ', '<rect ', '<circle '], ['<path class="' . $kelas . '" ', '<rect class="' . $kelas . '" ', '<circle class="' . $kelas . '" '], $el) ?>
    <?php endforeach ?>
</svg>
