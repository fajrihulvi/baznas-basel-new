<?php
// Uji mengandaikan pengaturan zakat kosong: kosongkan sementara, pulihkan saat skrip selesai (juga bila gagal di tengah).
$__kunci = "'harga_emas_per_gram', 'nilai_fidyah', 'nilai_fitrah', 'nishab_bulanan'";
$__awal = $pdo->query("SELECT kunci, nilai FROM setting WHERE kunci IN ($__kunci)")->fetchAll(PDO::FETCH_KEY_PAIR);
$__flush = fn () => array_map('unlink', glob(dirname(__DIR__, 2) . '/storage/cache/*.cache') ?: []);
$pdo->exec("UPDATE setting SET nilai = '' WHERE kunci IN ($__kunci)");
$__flush();
register_shutdown_function(function () use ($pdo, $__awal, $__flush): void {
    $st = $pdo->prepare('UPDATE setting SET nilai = ? WHERE kunci = ?');
    foreach ($__awal as $k => $v) {
        $st->execute([$v, $k]);
    }
    $__flush();
});
