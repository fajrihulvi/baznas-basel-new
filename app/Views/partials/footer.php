<?php
/** @var App\Core\View $this */

use App\Services\SiteService;

$namaSitus = setting('site_name', (string) config('app.name'));
$wa        = setting('whatsapp');
$telepon   = setting('telepon');
$email     = setting('email');
$mapsUrl   = setting('maps_url');
$jam       = setting('jam_layanan');
// Peta tersemat dari koordinat Pengaturan (hanya bila angka valid); iframe lazy → tidak dimuat sebelum footer mendekat
$lat = filter_var(setting('maps_lat'), FILTER_VALIDATE_FLOAT);
$lng = filter_var(setting('maps_lng'), FILTER_VALIDATE_FLOAT);
$petaEmbed = $lat !== false && $lng !== false && abs($lat) <= 90 && abs($lng) <= 180
    ? 'https://www.google.com/maps?' . http_build_query(['q' => $lat . ',' . $lng, 'z' => 16, 'hl' => 'id', 'output' => 'embed'])
    : null;

$tautan = [];
foreach (SiteService::halaman('ketentuan') as $h) {
    $tautan[] = ['label' => $h['judul'], 'url' => '/halaman/' . $h['slug']];
}
foreach (SiteService::halaman('layanan') as $h) {
    $tautan[] = ['label' => $h['judul'], 'url' => '/halaman/' . $h['slug']];
}
$tautan[] = ['label' => 'Regulasi Zakat', 'url' => '/regulasi'];
$tautan[] = ['label' => 'Laporan Keuangan', 'url' => '/laporan'];
$tautan[] = ['label' => 'Kalkulator Zakat', 'url' => '/kalkulator-zakat'];

$sosial = array_filter([
    'instagram' => setting('instagram'),
    'facebook'  => setting('facebook'),
    'youtube'   => setting('youtube'),
    'tiktok'    => setting('tiktok'),
    'twitter'   => setting('twitter'),
]);
$namaSosial = ['instagram' => 'Instagram', 'facebook' => 'Facebook', 'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'twitter' => 'X (Twitter)'];

$rekening = array_slice(SiteService::rekening(), 0, 3);
?>
<footer id="kontak" class="on-dark scroll-mt-16 bg-primary-dark text-white">
    <div class="mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4 lg:py-16">
        <section aria-labelledby="footer-kontak">
            <?php /* Logo bertulisan hijau tua → di atas kotak putih (footer berlatar hijau tua) */ ?>
            <h2 id="footer-kontak" class="inline-block rounded-2xl bg-white px-4 py-3">
                <?= $this->insert('partials/logo', ['class' => 'h-20 w-auto', 'sizes' => '116px', 'lazy' => true]) ?>
            </h2>
            <ul class="mt-5 space-y-3 text-sm text-white/85">
                <?php if ($alamat = setting('alamat')): ?>
                <li class="flex gap-3"><?= icon('map-pin', 'mt-0.5 size-5 shrink-0 text-accent') ?><span><?= e($alamat) ?></span></li>
                <?php endif ?>
                <?php if ($telepon): ?>
                <li><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', $telepon)) ?>" class="flex gap-3 hover:text-white"><?= icon('phone', 'size-5 shrink-0 text-accent') ?><?= e($telepon) ?></a></li>
                <?php endif ?>
                <?php if ($wa): ?>
                <li><a href="https://wa.me/<?= e($wa) ?>" class="flex gap-3 hover:text-white" rel="noopener" target="_blank"><?= icon('whatsapp', 'size-5 shrink-0 text-accent') ?>WhatsApp +<?= e($wa) ?></a></li>
                <?php endif ?>
                <?php if ($email): ?>
                <li><a href="mailto:<?= e($email) ?>" class="flex gap-3 break-all hover:text-white"><?= icon('mail', 'size-5 shrink-0 text-accent') ?><?= e($email) ?></a></li>
                <?php endif ?>
            </ul>
        </section>

        <nav aria-labelledby="footer-tautan">
            <h2 id="footer-tautan" class="text-sm font-bold uppercase tracking-wide text-accent">Tautan</h2>
            <ul class="mt-4 space-y-1 text-sm">
                <?php foreach ($tautan as $t): ?>
                <li><a href="<?= e(url($t['url'])) ?>" class="inline-block py-1.5 text-white/85 hover:text-white hover:underline"><?= e($t['label']) ?></a></li>
                <?php endforeach ?>
            </ul>
        </nav>

        <section aria-labelledby="footer-rekening">
            <h2 id="footer-rekening" class="text-sm font-bold uppercase tracking-wide text-accent">Rekening Resmi</h2>
            <ul class="mt-4 space-y-3 text-sm">
                <?php foreach ($rekening as $r): ?>
                <li>
                    <span class="block text-white/75"><?= e(trim($r['nama_bank'])) ?></span>
                    <span class="font-mono text-base font-semibold tracking-wide"><?= e($r['nomor_rekening']) ?></span>
                </li>
                <?php endforeach ?>
            </ul>
            <a href="<?= e(url('rekening')) ?>" class="mt-3 inline-flex items-center gap-1 py-1.5 text-sm font-semibold text-accent hover:underline">
                Semua rekening <?= icon('arrow-right', 'size-4') ?>
            </a>
        </section>

        <section aria-labelledby="footer-lokasi">
            <h2 id="footer-lokasi" class="text-sm font-bold uppercase tracking-wide text-accent">Lokasi &amp; Media Sosial</h2>
            <?php if ($petaEmbed !== null): ?>
            <div class="mt-4 aspect-[4/3] overflow-hidden rounded-2xl bg-white/10">
                <iframe src="<?= e($petaEmbed) ?>" title="Peta lokasi kantor <?= e($namaSitus) ?> di Google Maps" loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin" allowfullscreen class="size-full border-0"></iframe>
            </div>
            <?php endif ?>
            <?php if ($mapsUrl): ?>
            <a href="<?= e($mapsUrl) ?>" target="_blank" rel="noopener"
               class="mt-4 flex items-center gap-3 rounded-2xl bg-white/10 p-4 text-sm hover:bg-white/15">
                <span class="grid size-10 shrink-0 place-items-center rounded-full bg-accent text-primary-dark"><?= icon('map-pin', 'size-5') ?></span>
                <span><span class="block font-semibold">Buka di Google Maps</span><span class="text-white/75">Kantor BAZNAS Bangka Selatan</span></span>
            </a>
            <?php endif ?>
            <?php if ($jam): ?>
            <p class="mt-4 flex gap-3 text-sm text-white/85"><?= icon('clock', 'size-5 shrink-0 text-accent') ?><?= e($jam) ?></p>
            <?php endif ?>
            <?php if ($sosial): ?>
            <ul class="mt-5 flex flex-wrap gap-2">
                <?php foreach ($sosial as $kunci => $link): ?>
                <li>
                    <a href="<?= e($link) ?>" target="_blank" rel="noopener" aria-label="<?= e($namaSosial[$kunci]) ?>"
                       class="grid size-12 place-items-center rounded-full bg-white/10 hover:bg-accent hover:text-primary-dark"><?= icon($kunci, 'size-5') ?></a>
                </li>
                <?php endforeach ?>
            </ul>
            <?php endif ?>
        </section>
    </div>

    <div class="border-t border-white/15">
        <p class="mx-auto max-w-6xl px-4 py-5 text-sm text-white/75">&copy; <?= date('Y') ?> <?= e($namaSitus) ?>. Hak cipta dilindungi.</p>
    </div>
</footer>
