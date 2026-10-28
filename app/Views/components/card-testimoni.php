<?php
/**
 * Kartu testimoni: foto bulat (atau inisial), nama, profesi, rating, kutipan 3 baris yang bisa dibuka.
 *
 * @var array{id: int, nama: string, profesi: ?string, foto: ?string, isi: string, rating: int} $testimoni
 */
$t = $testimoni;
$rating = max(0, min(5, (int) $t['rating']));
$inisial = mb_strtoupper(implode('', array_map(
    static fn (string $kata): string => mb_substr($kata, 0, 1),
    array_slice(preg_split('/\s+/', trim($t['nama'])) ?: [], 0, 2)
)));
$idIsi = 'testimoni-' . (int) $t['id'];
?>
<figure class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex items-center gap-3">
        <?php if (!empty($t['foto'])): ?>
        <?= img($t['foto'], 'testimoni', '', ['class' => 'size-14 shrink-0 rounded-full object-cover', 'sizes' => '56px']) ?>
        <?php else: ?>
        <span class="grid size-14 shrink-0 place-items-center rounded-full bg-primary/10 font-bold text-primary" aria-hidden="true"><?= e($inisial) ?></span>
        <?php endif ?>
        <figcaption class="min-w-0">
            <span class="block font-bold text-ink"><?= e($t['nama']) ?></span>
            <?php if (!empty($t['profesi'])): ?>
            <span class="block text-sm text-muted"><?= e($t['profesi']) ?></span>
            <?php endif ?>
        </figcaption>
    </div>
    <p class="mt-3 flex gap-0.5 text-accent" role="img" aria-label="Rating <?= $rating ?> dari 5">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <?= icon($i <= $rating ? 'star-solid' : 'star', 'size-4' . ($i <= $rating ? '' : ' text-gray-300')) ?>
        <?php endfor ?>
    </p>
    <blockquote id="<?= e($idIsi) ?>" class="mt-3 line-clamp-3 text-sm text-ink/90">
        “<?= e($t['isi']) ?>”
    </blockquote>
    <button type="button" data-expand aria-controls="<?= e($idIsi) ?>" aria-expanded="false" hidden
            class="mt-2 self-start py-1 text-sm font-semibold text-primary hover:underline">Selengkapnya</button>
</figure>
