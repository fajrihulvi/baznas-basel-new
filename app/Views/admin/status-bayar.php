<?php
/** @var string $status "0" | "1" | "2" */
[$label, $kelas] = match ($status) {
    '1'     => ['Selesai', 'bg-green-100 text-green-800'],
    '2'     => ['Dibatalkan', 'bg-red-100 text-red-800'],
    default => ['Menunggu', 'bg-amber-100 text-amber-900'],
};
?>
<span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold <?= $kelas ?>"><?= $label ?></span>
