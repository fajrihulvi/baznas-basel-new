<?php

/**
 * Ganti password akun admin dengan hash modern (bcrypt via password_hash).
 * Password diketik di terminal (tidak tampil, tidak disimpan di file/log).
 *
 *   php database/scripts/reset_admin_password.php                  # akun "admin"
 *   php database/scripts/reset_admin_password.php --username=nama
 *
 * Butuh migrasi 005 (kolom password VARCHAR(255)).
 * bcrypt dipilih (bukan argon2id) karena pasti tersedia di semua build PHP hosting.
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use App\Core\Database;

$username = (string) opsi('username', 'admin');

$user = Database::fetch('SELECT id_user, username FROM user WHERE username = ?', [$username]);
if ($user === null) {
    fwrite(STDERR, "Akun \"{$username}\" tidak ditemukan.\n");
    exit(1);
}

$panjangKolom = (int) Database::fetchColumn(
    "SELECT CHARACTER_MAXIMUM_LENGTH FROM information_schema.columns
      WHERE table_schema = DATABASE() AND table_name = 'user' AND column_name = 'password'"
);
if ($panjangKolom < 60) {
    fwrite(STDERR, "Kolom user.password masih {$panjangKolom} karakter. Jalankan migrasi 005 dulu.\n");
    exit(1);
}

$password = tanya_rahasia("Password baru untuk \"{$username}\" (min. 8 karakter): ");
if (mb_strlen($password) < 8) {
    fwrite(STDERR, "Terlalu pendek. Minimal 8 karakter.\n");
    exit(1);
}
if (tanya_rahasia('Ulangi password: ') !== $password) {
    fwrite(STDERR, "Password tidak sama.\n");
    exit(1);
}

Database::execute(
    'UPDATE user SET password = ?, token = NULL, token_expired = NULL WHERE id_user = ?',
    [password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]), $user['id_user']]
);

echo "Password \"{$username}\" sudah diganti (bcrypt).\n";

function tanya_rahasia(string $prompt): string
{
    echo $prompt;
    $tty = stream_isatty(STDIN);
    if ($tty) {
        shell_exec('stty -echo');
    }
    $nilai = rtrim((string) fgets(STDIN), "\r\n");
    if ($tty) {
        shell_exec('stty echo');
        echo "\n";
    }

    return $nilai;
}
