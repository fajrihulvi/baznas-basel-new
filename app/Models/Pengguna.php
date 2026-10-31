<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Akun panel admin (tabel `user`). Hash password tidak pernah ikut dalam data daftar/form. */
final class Pengguna
{
    private const KOLOM = 'id_user AS id, username, f_name, l_name, email, no_hp, level, status, last_login, created_user';

    /** @return list<array> */
    public static function daftar(string $q): array
    {
        [$where, $params] = self::filter($q);

        return Database::fetchAll('SELECT ' . self::KOLOM . " FROM user{$where} ORDER BY status DESC, level, f_name, username LIMIT 500", $params);
    }

    public static function byId(int $id): ?array
    {
        return Database::fetch('SELECT ' . self::KOLOM . ' FROM user WHERE id_user = ?', [$id]);
    }

    public static function usernameDipakai(string $username, ?int $kecuali = null): bool
    {
        return (bool) Database::fetchColumn(
            'SELECT 1 FROM user WHERE username = ? AND id_user <> ?',
            [$username, $kecuali ?? 0]
        );
    }

    /** @param array{username: string, f_name: string, l_name: ?string, email: ?string, no_hp: ?string, level: int, status: string} $data */
    public static function tambah(array $data, string $hash, string $ip): int
    {
        Database::execute(
            'INSERT INTO user (username, password, f_name, l_name, email, no_hp, level, status, created_user, created_IP)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?)',
            [$data['username'], $hash, $data['f_name'], $data['l_name'], $data['email'], $data['no_hp'], $data['level'], $data['status'], $ip]
        );

        return Database::lastInsertId();
    }

    /** @param array{username: string, f_name: string, l_name: ?string, email: ?string, no_hp: ?string, level: int, status: string} $data */
    public static function ubah(int $id, array $data, ?string $hashBaru): void
    {
        Database::execute(
            'UPDATE user SET username = ?, f_name = ?, l_name = ?, email = ?, no_hp = ?, level = ?, status = ? WHERE id_user = ?',
            [$data['username'], $data['f_name'], $data['l_name'], $data['email'], $data['no_hp'], $data['level'], $data['status'], $id]
        );
        if ($hashBaru !== null) {
            // Password diganti administrator: token reset lama ikut dibatalkan, sesi pemilik akun berakhir (lihat Auth::aktif)
            Database::execute('UPDATE user SET password = ?, token = NULL, token_expired = NULL WHERE id_user = ?', [$hashBaru, $id]);
        }
    }

    public static function hapus(int $id): void
    {
        Database::execute('DELETE FROM user WHERE id_user = ?', [$id]);
    }

    /** @return array{0: string, 1: list<string>} */
    private static function filter(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return ['', []];
        }
        $pola = '%' . addcslashes($q, '%_\\') . '%';

        return [' WHERE username LIKE ? OR f_name LIKE ? OR l_name LIKE ? OR email LIKE ?', [$pola, $pola, $pola, $pola]];
    }
}
