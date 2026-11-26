<?php
/**
 * Baris "Bagikan" (Facebook, X, WhatsApp, Telegram, Email) berupa tombol bulat bergaris. Dipakai /tentang & /arsip.
 *
 * @var string $tautan  URL absolut halaman yang dibagikan
 * @var string $teks    teks/judul bagikan
 * @var string $id      id label "Bagikan" (aria-labelledby)
 */
$teksUrl = rawurlencode($teks);
$tautanUrl = rawurlencode($tautan);
$bagikan = [
    ['Facebook', 'facebook', 'https://www.facebook.com/sharer/sharer.php?u=' . $tautanUrl, 'border-[#1877f2] text-[#1877f2]'],
    ['X (Twitter)', 'twitter', 'https://twitter.com/intent/tweet?url=' . $tautanUrl . '&text=' . $teksUrl, 'border-[#111827] text-[#111827]'],
    ['WhatsApp', 'whatsapp', 'https://wa.me/?text=' . $teksUrl . '%20' . $tautanUrl, 'border-[#128c7e] text-[#128c7e]'],
    ['Telegram', 'send', 'https://t.me/share/url?url=' . $tautanUrl . '&text=' . $teksUrl, 'border-[#0088cc] text-[#0088cc]'],
    ['Email', 'mail', 'mailto:?subject=' . $teksUrl . '&body=' . $tautanUrl, 'border-[#dc2626] text-[#dc2626]'],
];
?>
<div class="mt-6 flex flex-wrap items-center justify-center gap-x-3 gap-y-2">
    <span class="text-base text-muted lg:text-lg" id="<?= e($id) ?>">Bagikan</span>
    <ul class="flex items-center gap-2" aria-labelledby="<?= e($id) ?>">
        <?php foreach ($bagikan as [$nama, $ikon, $link, $warna]): ?>
        <li>
            <a href="<?= e($link) ?>" target="_blank" rel="noopener" aria-label="Bagikan ke <?= e($nama) ?>"
               class="grid size-10 place-items-center rounded-full border-2 bg-white transition hover:-translate-y-0.5 hover:shadow-md <?= $warna ?>"><?= icon($ikon, 'size-5') ?></a>
        </li>
        <?php endforeach ?>
    </ul>
</div>
