<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/**
 * Donatur program donasi (tabel `donasi`, admin → Donasi Bantuan). Data pribadi: hanya admin & halaman instruksi
 * milik donatur (lewat kode acak). Publik hanya melihat agregat dan doa yang diizinkan.
 */
final class Donasi
{
    public const STATUS = ['menunggu' => 'Menunggu verifikasi', 'terverifikasi' => 'Terverifikasi', 'ditolak' => 'Ditolak'];

    public static function buat(array $d): int
    {
        Database::execute(
            'INSERT INTO donasi (kode, id_banner, nominal, nama, anonim, no_hp, email, doa, id_rekening, bank_tujuan, norek_tujuan)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d['kode'], $d['id_banner'], $d['nominal'], $d['nama'], $d['anonim'], $d['no_hp'], $d['email'], $d['doa'],
             $d['id_rekening'], $d['bank_tujuan'], $d['norek_tujuan']]
        );

        return Database::lastInsertId();
    }

    public static function kodeAda(string $kode): bool
    {
        return (bool) Database::fetchColumn('SELECT COUNT(*) FROM donasi WHERE kode = ?', [$kode]);
    }

    /** Untuk halaman instruksi transfer (milik donatur yang memegang kode). */
    public static function byKode(string $kode): ?array
    {
        return Database::fetch(
            'SELECT d.id, d.kode, d.nominal, d.nama, d.anonim, d.status, d.waktu, d.bank_tujuan, d.norek_tujuan, d.id_rekening,
                    b.donasi_nama AS program, b.donasi_slug AS slug
               FROM donasi d JOIN banner b ON b.id_banner = d.id_banner
              WHERE d.kode = ?',
            [$kode]
        );
    }

    /** @return array<int, array{terkumpul: int, donatur: int}> per id banner, hanya donasi terverifikasi */
    public static function ringkasPerProgram(): array
    {
        $hasil = [];
        foreach (Database::fetchAll("SELECT id_banner, SUM(nominal) AS terkumpul, COUNT(*) AS donatur FROM donasi WHERE status = 'terverifikasi' GROUP BY id_banner") as $r) {
            $hasil[(int) $r['id_banner']] = ['terkumpul' => (int) $r['terkumpul'], 'donatur' => (int) $r['donatur']];
        }

        return $hasil;
    }

    /** Doa donatur terverifikasi yang boleh tampil (nama atau "Hamba Allah", tanpa nominal & kontak). */
    public static function doa(int $idBanner, int $limit): array
    {
        return Database::fetchAll(
            "SELECT IF(anonim = 1, 'Hamba Allah', nama) AS nama, doa, waktu FROM donasi
              WHERE id_banner = ? AND status = 'terverifikasi' AND doa_tampil = 1 AND doa IS NOT NULL AND doa <> ''
              ORDER BY waktu DESC LIMIT ?",
            [$idBanner, $limit]
        );
    }

    public static function jumlahMenunggu(): int
    {
        return (int) Database::fetchColumn("SELECT COUNT(*) FROM donasi WHERE status = 'menunggu'");
    }
}
