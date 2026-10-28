<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Data pembayaran muzaki — data pribadi, JANGAN ditampilkan di halaman publik selain instruksi milik kodenya sendiri. */
final class Pembayaran
{
    public const MENUNGGU = '0';
    public const SELESAI  = '1';
    public const GAGAL    = '2';

    /** @param array{kode: string, sapaan: string, nama: string, npwz: ?string, no_hp: string, email: ?string, jenis: int, nominal: int, hari: ?int, jiwa: ?int} $d */
    public static function buat(array $d): int
    {
        Database::execute(
            'INSERT INTO pembayaran (kode_pembayaran, sapaan, nama_lengkap, npwz, no_hp, email, jenis_zakat, nominal, jml_hari, jml_jiwa, status, waktu)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())',
            [$d['kode'], $d['sapaan'], $d['nama'], $d['npwz'], $d['no_hp'], $d['email'], $d['jenis'], $d['nominal'], $d['hari'], $d['jiwa'], self::MENUNGGU]
        );

        return Database::lastInsertId();
    }

    public static function kodeAda(string $kode): bool
    {
        return Database::fetchColumn('SELECT 1 FROM pembayaran WHERE kode_pembayaran = ?', [$kode]) !== null;
    }

    public static function byKode(string $kode): ?array
    {
        return Database::fetch(
            'SELECT p.id_pembayaran AS id, p.kode_pembayaran AS kode, p.sapaan, p.nama_lengkap, p.jenis_zakat AS id_jenis,
                    TRIM(j.jenis_zakat) AS jenis, p.nominal, p.jml_hari, p.jml_jiwa, p.status, p.waktu
               FROM pembayaran p JOIN jenis_zakat j ON j.id_jenis_zakat = p.jenis_zakat
              WHERE p.kode_pembayaran = ?',
            [$kode]
        );
    }

    // --- Admin ----------------------------------------------------------------------

    /** @param string|null $status "0" | "1" | "2" | null (semua); $hanyaKonfirmasiBaru: punya konfirmasi berstatus baru */
    public static function daftarAdmin(?string $status, bool $hanyaKonfirmasiBaru, string $q, int $limit, int $offset): array
    {
        [$where, $params] = self::filterAdmin($status, $hanyaKonfirmasiBaru, $q);

        return Database::fetchAll(
            "SELECT p.id_pembayaran AS id, p.kode_pembayaran AS kode, p.sapaan, p.nama_lengkap, TRIM(j.jenis_zakat) AS jenis,
                    p.nominal, p.status, p.waktu,
                    (SELECT COUNT(*) FROM konfirmasi_pembayaran k WHERE k.id_pembayaran = p.id_pembayaran AND k.status = 'baru') AS konfirmasi_baru
               FROM pembayaran p JOIN jenis_zakat j ON j.id_jenis_zakat = p.jenis_zakat{$where}
              ORDER BY p.waktu DESC, p.id_pembayaran DESC LIMIT ? OFFSET ?",
            [...$params, $limit, $offset]
        );
    }

    public static function hitungAdmin(?string $status, bool $hanyaKonfirmasiBaru, string $q): int
    {
        [$where, $params] = self::filterAdmin($status, $hanyaKonfirmasiBaru, $q);

        return (int) Database::fetchColumn("SELECT COUNT(*) FROM pembayaran p{$where}", $params);
    }

    public static function byId(int $id): ?array
    {
        return Database::fetch(
            "SELECT p.*, p.id_pembayaran AS id, p.kode_pembayaran AS kode, TRIM(j.jenis_zakat) AS jenis
               FROM pembayaran p JOIN jenis_zakat j ON j.id_jenis_zakat = p.jenis_zakat WHERE p.id_pembayaran = ?",
            [$id]
        );
    }

    public static function ubahStatus(int $id, string $status): void
    {
        Database::execute("UPDATE pembayaran SET status = ? WHERE id_pembayaran = ?", [$status, $id]);
    }

    /** @return array{menunggu: int, selesai_bulan_ini: int, nominal_bulan_ini: int} */
    public static function ringkasan(): array
    {
        $r = Database::fetch(
            "SELECT SUM(status = '0') AS menunggu,
                    SUM(status = '1' AND waktu >= DATE_FORMAT(CURDATE(), '%Y-%m-01')) AS selesai_bulan_ini,
                    COALESCE(SUM(CASE WHEN status = '1' AND waktu >= DATE_FORMAT(CURDATE(), '%Y-%m-01') THEN nominal END), 0) AS nominal_bulan_ini
               FROM pembayaran"
        ) ?? [];

        return array_map("intval", $r + ["menunggu" => 0, "selesai_bulan_ini" => 0, "nominal_bulan_ini" => 0]);
    }

    /** @return array{0: string, 1: list<string>} */
    private static function filterAdmin(?string $status, bool $hanyaKonfirmasiBaru, string $q): array
    {
        $kondisi = [];
        $params = [];
        if ($status !== null) {
            $kondisi[] = "p.status = ?";
            $params[] = $status;
        }
        if ($hanyaKonfirmasiBaru) {
            $kondisi[] = "EXISTS (SELECT 1 FROM konfirmasi_pembayaran k WHERE k.id_pembayaran = p.id_pembayaran AND k.status = 'baru')";
        }
        if (trim($q) !== "") {
            $pola = "%" . addcslashes(trim($q), '%_\\') . "%";
            $kondisi[] = "(p.kode_pembayaran LIKE ? OR p.nama_lengkap LIKE ? OR p.no_hp LIKE ?)";
            array_push($params, $pola, $pola, $pola);
        }

        return [$kondisi === [] ? "" : " WHERE " . implode(" AND ", $kondisi), $params];
    }
}
