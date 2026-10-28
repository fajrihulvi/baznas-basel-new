<?php

/**
 * Menjalankan file database/migrations/*.sql yang belum pernah dijalankan, berurutan.
 * Riwayat disimpan di tabel `migrasi`.
 *
 *   php database/scripts/migrate.php              # jalankan semua yang tertunda
 *   php database/scripts/migrate.php --sampai=004 # berhenti setelah 004 (mis. tunda 005 di produksi)
 *   php database/scripts/migrate.php --status     # lihat status saja
 *
 * BACKUP DATABASE DULU. DDL MariaDB tidak bisa di-rollback; bila gagal di tengah file,
 * perbaiki lalu jalankan ulang (perintah di file migrasi dibuat aman diulang).
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Database;

Database::execute(
    'CREATE TABLE IF NOT EXISTS migrasi (
        nama VARCHAR(190) PRIMARY KEY,
        dijalankan_pada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
     ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
);

$sudah  = array_column(Database::fetchAll('SELECT nama FROM migrasi'), 'nama');
$files  = glob(base_path('database/migrations/*.sql')) ?: [];
sort($files);
$sampai = opsi('sampai');

printf("Database: %s\n\n", config('database.name'));

foreach ($files as $file) {
    $nama = basename($file);

    if (in_array($nama, $sudah, true)) {
        printf("  [sudah]   %s\n", $nama);
        continue;
    }
    if ($sampai !== null && strcmp(substr($nama, 0, strlen((string) $sampai)), (string) $sampai) > 0) {
        printf("  [ditunda] %s\n", $nama);
        continue;
    }
    if (opsi('status')) {
        printf("  [belum]   %s\n", $nama);
        continue;
    }

    $statements = pecah_sql((string) file_get_contents($file));
    foreach ($statements as $i => $sql) {
        try {
            Database::connection()->exec($sql);
        } catch (Throwable $e) {
            fprintf(STDERR, "\nGAGAL di %s, perintah ke-%d:\n%s\n\n%s\n", $nama, $i + 1, mb_substr($sql, 0, 400), $e->getMessage());
            exit(1);
        }
    }

    Database::execute('INSERT INTO migrasi (nama) VALUES (?)', [$nama]);
    printf("  [OK]      %s (%d perintah)\n", $nama, count($statements));
}

/**
 * Pecah isi file SQL menjadi perintah-perintah pada ';' di luar string dan komentar.
 *
 * @return list<string>
 */
function pecah_sql(string $sql): array
{
    $hasil = [];
    $buf   = '';
    $len   = strlen($sql);
    $kutip = null;

    for ($i = 0; $i < $len; $i++) {
        $c = $sql[$i];

        if ($kutip !== null) {
            $buf .= $c;
            if ($c === '\\' && $kutip !== '`' && $i + 1 < $len) {
                $buf .= $sql[++$i];
            } elseif ($c === $kutip) {
                $kutip = null;
            }
            continue;
        }

        if ($c === '-' && substr($sql, $i, 3) === '-- ' || $c === '#') {
            $i = ($p = strpos($sql, "\n", $i)) === false ? $len : $p;
            $buf .= "\n";
            continue;
        }
        if ($c === '/' && ($sql[$i + 1] ?? '') === '*') {
            $i = ($p = strpos($sql, '*/', $i + 2)) === false ? $len : $p + 1;
            continue;
        }
        if ($c === "'" || $c === '"' || $c === '`') {
            $kutip = $c;
        }
        if ($c === ';') {
            if (trim($buf) !== '') {
                $hasil[] = trim($buf);
            }
            $buf = '';
            continue;
        }
        $buf .= $c;
    }

    if (trim($buf) !== '') {
        $hasil[] = trim($buf);
    }

    return $hasil;
}
