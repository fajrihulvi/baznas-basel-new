<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Konfirmasi transfer + bukti (file di storage/bukti, bukan publik). */
final class KonfirmasiPembayaran
{
    /** @param array{id_pembayaran: int, kode: string, bank_pengirim: string, bank_tujuan: string, norek_tujuan: string, tgl_transfer: string, nominal: int, bukti: string} $d */
    public static function buat(array $d): int
    {
        Database::execute(
            "INSERT INTO konfirmasi_pembayaran
                (id_pembayaran, kode_pembayaran, bank_pengirim, bank_tujuan, norek_tujuan, tgl_transfer, nominal_transfer,
                 bukti_pembayaran, tgl, status, dibuat)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), 'baru', NOW())",
            [$d['id_pembayaran'], $d['kode'], $d['bank_pengirim'], $d['bank_tujuan'], $d['norek_tujuan'], $d['tgl_transfer'], $d['nominal'], $d['bukti']]
        );

        return Database::lastInsertId();
    }

    /** Status konfirmasi untuk halaman instruksi (tanpa bukti/rekening pengirim). */
    public static function perPembayaran(int $idPembayaran): array
    {
        return Database::fetchAll(
            'SELECT status, tgl_transfer, nominal_transfer, COALESCE(dibuat, tgl) AS dikirim
               FROM konfirmasi_pembayaran WHERE id_pembayaran = ? ORDER BY id_konfirmasi_pembayaran DESC',
            [$idPembayaran]
        );
    }

    // --- Admin ----------------------------------------------------------------------

    /** Semua kolom (termasuk path bukti) untuk halaman detail admin. */
    public static function untukAdmin(int $idPembayaran): array
    {
        return Database::fetchAll(
            "SELECT * FROM konfirmasi_pembayaran WHERE id_pembayaran = ? ORDER BY id_konfirmasi_pembayaran DESC",
            [$idPembayaran]
        );
    }

    public static function byId(int $id): ?array
    {
        return Database::fetch("SELECT * FROM konfirmasi_pembayaran WHERE id_konfirmasi_pembayaran = ?", [$id]);
    }

    public static function ubahStatus(int $id, string $status): void
    {
        Database::execute("UPDATE konfirmasi_pembayaran SET status = ? WHERE id_konfirmasi_pembayaran = ?", [$status, $id]);
    }

    public static function hitungBaru(): int
    {
        return (int) Database::fetchColumn("SELECT COUNT(*) FROM konfirmasi_pembayaran WHERE status = 'baru'");
    }

    /** Konfirmasi terbaru yang menunggu verifikasi (dashboard). */
    public static function antrean(int $limit): array
    {
        return Database::fetchAll(
            "SELECT k.id_konfirmasi_pembayaran AS id, k.id_pembayaran, k.kode_pembayaran AS kode, k.nominal_transfer, k.tgl_transfer,
                    COALESCE(k.dibuat, k.tgl) AS dikirim, p.nama_lengkap, p.nominal
               FROM konfirmasi_pembayaran k LEFT JOIN pembayaran p ON p.id_pembayaran = k.id_pembayaran
              WHERE k.status = 'baru' ORDER BY k.id_konfirmasi_pembayaran DESC LIMIT ?",
            [$limit]
        );
    }
}
