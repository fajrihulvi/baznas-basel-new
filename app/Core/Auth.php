<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Autentikasi admin (§10): password_hash/verify, rate limit 5×/15 menit per IP,
 * ID sesi diganti saat login, sesi berakhir setelah 30 menit tidak aktif.
 * Hash MD5 lama tidak pernah diterima — wajib reset lewat database/scripts/reset_admin_password.php.
 */
final class Auth
{
    public const BATAS_DIAM = 1800;
    /**
     * Level `user.level`: 1 = Administrator (semua + kelola pengguna), 3 = Kepala (hanya melihat dasbor),
     * selain itu = Operator (semua kecuali kelola pengguna).
     */
    public const ADMINISTRATOR = 1;
    public const OPERATOR = 2;
    public const KEPALA = 3;
    private const MAKS_PERCOBAAN = 5;
    private const JENDELA_PERCOBAAN = 900;

    /** @return array{id: int, username: string, nama: string, level: int}|null */
    public static function user(): ?array
    {
        $user = Session::get('admin');

        return is_array($user) ? $user : null;
    }

    public static function administrator(): bool
    {
        return (int) (self::user()['level'] ?? 0) === self::ADMINISTRATOR;
    }

    /** Kepala: hanya-baca, hanya halaman di config admin.akses_kepala (lihat AuthAdmin). */
    public static function kepala(): bool
    {
        return (int) (self::user()['level'] ?? 0) === self::KEPALA;
    }

    /**
     * Masih login, belum diam lebih dari 30 menit, dan akunnya masih sah di database
     * (ada, aktif, password tidak diganti orang lain). Sekaligus memperbarui waktu aktivitas & nama/peran di sesi.
     */
    public static function aktif(): bool
    {
        $user = self::user();
        if ($user === null) {
            return false;
        }
        if (time() - (int) Session::get('admin_aktif', 0) > self::BATAS_DIAM) {
            self::keluar();
            Session::flash('alert', ['jenis' => 'info', 'pesan' => 'Sesi berakhir karena tidak aktif selama 30 menit. Silakan masuk kembali.']);
            return false;
        }

        $row = Database::fetch('SELECT username, password, f_name, l_name, level, status FROM user WHERE id_user = ?', [$user['id']]);
        if ($row === null || $row['status'] === '0' || !hash_equals(self::sidik((string) $row['password']), (string) Session::get('admin_sidik', ''))) {
            self::keluar();
            Session::flash('alert', ['jenis' => 'info', 'pesan' => 'Akun Anda diubah atau dinonaktifkan oleh administrator. Silakan masuk kembali.']);
            return false;
        }
        Session::put('admin', self::dataSesi((int) $user['id'], $row));
        Session::put('admin_aktif', time());

        return true;
    }

    /** Setelah mengganti password sendiri: sesi ini tetap sah, sesi lain milik akun yang sama berakhir. */
    public static function perbaruiSidik(string $hashBaru): void
    {
        Session::put('admin_sidik', self::sidik($hashBaru));
    }

    /** @return string|null pesan kesalahan, null bila berhasil */
    public static function masuk(string $username, string $password, string $ip): ?string
    {
        if (RateLimiter::terlalu('login:' . $ip, self::MAKS_PERCOBAAN, self::JENDELA_PERCOBAAN)) {
            return 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam 15 menit.';
        }

        $row = Database::fetch(
            'SELECT id_user, username, password, f_name, l_name, level, status FROM user WHERE username = ?',
            [$username]
        );
        $hash = (string) ($row['password'] ?? '');

        // Hash MD5 lama: hanya beri tahu bila password-nya memang benar (tidak membocorkan username)
        if ($row !== null && strlen($hash) === 32 && ctype_xdigit($hash)) {
            return hash_equals($hash, md5($password))
                ? 'Password akun ini masih memakai format lama yang tidak aman. Jalankan "php database/scripts/reset_admin_password.php" lalu masuk kembali.'
                : 'Username atau password salah.';
        }

        // Tetap jalankan password_verify walau user tidak ada agar waktu respons serupa
        $cocok = password_verify($password, $row !== null ? $hash : '$2y$12$9XINYIAQ/Us4bS/C.qbE.OpuUrQhEUAj.l/TzU6tNq7xhSLLV8QMy');
        if ($row === null || !$cocok || $row['status'] === '0') {
            return 'Username atau password salah.';
        }

        if (password_needs_rehash($hash, PASSWORD_BCRYPT, ['cost' => 12])) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            Database::execute('UPDATE user SET password = ? WHERE id_user = ?', [$hash, $row['id_user']]);
        }

        Session::regenerate();
        Session::forget('_csrf');
        Session::put('admin', self::dataSesi((int) $row['id_user'], $row));
        Session::put('admin_sidik', self::sidik($hash));
        Session::put('admin_aktif', time());
        RateLimiter::bersihkan('login:' . $ip);
        Database::execute('UPDATE user SET last_login = NOW(), last_loginIP = ? WHERE id_user = ?', [$ip, $row['id_user']]);

        return null;
    }

    public static function keluar(): void
    {
        Session::forget('admin');
        Session::forget('admin_aktif');
        Session::forget('admin_sidik');
        Session::regenerate();
    }

    /** Sidik jari hash password: berubah bila password diganti → sesi lama otomatis tidak sah. */
    private static function sidik(string $hash): string
    {
        return substr(hash('sha256', $hash), 0, 32);
    }

    /** @return array{id: int, username: string, nama: string, level: int} */
    private static function dataSesi(int $id, array $row): array
    {
        return [
            'id'       => $id,
            'username' => (string) $row['username'],
            'nama'     => trim($row['f_name'] . ' ' . ($row['l_name'] ?? '')),
            'level'    => (int) $row['level'],
        ];
    }
}
