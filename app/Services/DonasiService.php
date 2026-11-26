<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\HttpException;
use App\Core\RateLimiter;
use App\Core\Validator;
use App\Models\Donasi;
use DateTimeImmutable;
use PDOException;

/**
 * Program donasi (banner bertanda donasi) & formulir donatur. Transfer manual ke rekening resmi,
 * verifikasi manual oleh admin setelah donatur mengonfirmasi lewat WhatsApp.
 */
final class DonasiService
{
    public const MIN_BAWAAN = 10000;
    public const MAX_NOMINAL = 10_000_000_000;
    public const FIELD_FORM = ['nominal', 'nama', 'anonim', 'no_hp', 'email', 'doa', 'rekening', 'setuju'];
    private const ALFABET_KODE = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /**
     * Semua program donasi terbit (banner publish & donasi = 1), lengkap dengan terkumpul, persen & status waktu.
     * Urut: berjalan → segera dibuka → selesai. Kosong bila tabel belum dimigrasi.
     *
     * @return list<array>
     */
    public static function program(): array
    {
        try {
            $baris = Database::fetchAll(
                "SELECT * FROM banner WHERE publish = 'Y' AND donasi = 1 AND donasi_slug IS NOT NULL AND donasi_slug <> ''
                  ORDER BY urutan, donasi_mulai DESC, id_banner DESC"
            );
            $ringkas = Donasi::ringkasPerProgram();
        } catch (PDOException $e) {
            if (in_array($e->getCode(), ['42S02', '42S22'], true)) {
                return [];
            }
            throw $e;
        }
        $hasil = array_map(static fn (array $b): array => self::rakit($b, $ringkas[(int) $b['id_banner']] ?? null), $baris);
        $urut = ['berjalan' => 0, 'segera' => 1, 'selesai' => 2];
        usort($hasil, static fn (array $a, array $b): int => $urut[$a['status']] <=> $urut[$b['status']]);

        return $hasil;
    }

    /** @return list<array> program yang sedang berjalan (untuk beranda) */
    public static function berjalan(int $limit): array
    {
        return array_slice(array_values(array_filter(self::program(), static fn (array $p): bool => $p['status'] === 'berjalan')), 0, $limit);
    }

    public static function bySlug(string $slug): ?array
    {
        foreach (self::program() as $p) {
            if ($p['slug'] === $slug) {
                return $p;
            }
        }

        return null;
    }

    /** Rekening tujuan donasi: rekening infaq/sedekah resmi (cadangan: semua rekening). */
    public static function rekening(): array
    {
        return PembayaranService::rekeningUntuk('infaq');
    }

    /**
     * Simpan donatur baru.
     *
     * @return array{kode: ?string, errors: array<string, string>}
     */
    public static function buat(array $program, array $in, string $ip): array
    {
        if (RateLimiter::terlalu('donasi:' . $ip, 10, 600)) {
            throw new HttpException(429);
        }
        if (trim((string) ($in['website'] ?? '')) !== '') {
            return ['kode' => null, 'errors' => ['_form' => 'Permintaan tidak dapat diproses. Muat ulang halaman lalu coba lagi.']];
        }
        if ($program['status'] !== 'berjalan') {
            return ['kode' => null, 'errors' => ['_form' => $program['status'] === 'selesai' ? 'Program donasi ini sudah ditutup.' : 'Program donasi ini belum dibuka.']];
        }

        $in['no_hp'] = PembayaranService::normalisasiHp((string) ($in['no_hp'] ?? ''));
        $in['nominal'] = (string) angka($in['nominal'] ?? '');
        $v = Validator::make($in, [
            'nominal' => 'required|between:' . $program['minimal'] . ',' . self::MAX_NOMINAL,
            'nama'    => 'required|min:3|max:100',
            'no_hp'   => ['required', 'regex:/^08\d{7,12}$/'],
            'email'   => 'required|email|max:100',
            'doa'     => 'nullable|max:500',
        ], ['nominal' => 'Nominal donasi', 'nama' => 'Nama lengkap', 'no_hp' => 'Nomor WhatsApp', 'email' => 'Email', 'doa' => 'Doa']);
        if (isset($v->errors()['nominal']) && (int) $in['nominal'] > 0 && (int) $in['nominal'] < $program['minimal']) {
            $v = self::ganti($v, 'nominal', 'Minimal donasi ' . format_rupiah($program['minimal']) . '.');
        }

        $rekening = null;
        foreach (self::rekening() as $r) {
            if ((string) $r['id'] === (string) ($in['rekening'] ?? '')) {
                $rekening = $r;
            }
        }
        if ($rekening === null) {
            $v->tambah('rekening', 'Pilih rekening tujuan transfer.');
        }
        if (empty($in['setuju'])) {
            $v->tambah('setuju', 'Centang persetujuan Syarat dan Ketentuan serta Kebijakan Privasi.');
        }
        if ($v->fails()) {
            return ['kode' => null, 'errors' => $v->errors()];
        }

        $kode = self::kodeBaru();
        Donasi::buat([
            'kode'         => $kode,
            'id_banner'    => $program['id'],
            'nominal'      => (int) $in['nominal'],
            'nama'         => trim((string) $in['nama']),
            'anonim'       => empty($in['anonim']) ? 0 : 1,
            'no_hp'        => $in['no_hp'],
            'email'        => trim((string) $in['email']),
            'doa'          => trim((string) ($in['doa'] ?? '')) ?: null,
            'id_rekening'  => $rekening['id'],
            'bank_tujuan'  => trim($rekening['nama_bank']),
            'norek_tujuan' => $rekening['nomor_rekening'],
        ]);

        return ['kode' => $kode, 'errors' => []];
    }

    /** Pilihan nominal cepat (seperti acuan: kelipatan nominal minimal). @return list<int> */
    public static function nominalCepat(int $minimal): array
    {
        return array_map(static fn (int $k): int => $minimal * $k, [2, 4, 6, 8]);
    }

    private static function rakit(array $b, ?array $ringkas): array
    {
        $hariIni = new DateTimeImmutable('today');
        $mulai = $b['donasi_mulai'] ? new DateTimeImmutable($b['donasi_mulai']) : null;
        $selesai = $b['donasi_selesai'] ? new DateTimeImmutable($b['donasi_selesai']) : null;
        $status = match (true) {
            $mulai !== null && $hariIni < $mulai     => 'segera',
            $selesai !== null && $hariIni > $selesai => 'selesai',
            default                                  => 'berjalan',
        };
        $terkumpul = ($ringkas['terkumpul'] ?? 0) + (int) ($b['donasi_terkumpul_luar'] ?? 0);
        $target = (int) ($b['donasi_target'] ?? 0);

        return [
            'id'          => (int) $b['id_banner'],
            'nama'        => (string) $b['donasi_nama'],
            'slug'        => (string) $b['donasi_slug'],
            'judul'       => (string) $b['judul'],
            'foto'        => (string) $b['foto'],
            'foto_mobile' => $b['foto_mobile'] ?: null,
            'deskripsi'   => $b['donasi_deskripsi'] ?: null,
            'mulai'       => $b['donasi_mulai'],
            'selesai'     => $b['donasi_selesai'],
            'target'      => $target,
            'minimal'     => max(1000, (int) ($b['donasi_minimal'] ?? 0) ?: self::MIN_BAWAAN),
            'terkumpul'   => $terkumpul,
            'donatur'     => $ringkas['donatur'] ?? 0,
            'persen'      => $target > 0 ? min(100, $terkumpul / $target * 100) : 0,
            'status'      => $status,
            'sisa_hari'   => $selesai !== null && $status === 'berjalan' ? (int) $hariIni->diff($selesai)->days : null,
        ];
    }

    /** "12 hari lagi" / "Hari terakhir" / "Dibuka 3 Desember 2026" / "Selesai" */
    public static function keteranganWaktu(array $p): string
    {
        return match ($p['status']) {
            'segera'  => 'Dibuka ' . tgl_id($p['mulai']),
            'selesai' => 'Selesai',
            default   => $p['sisa_hari'] === null ? 'Berlangsung' : ($p['sisa_hari'] === 0 ? 'Hari terakhir' : $p['sisa_hari'] . ' hari lagi'),
        };
    }

    private static function ganti(Validator $v, string $field, string $pesan): Validator
    {
        $baru = Validator::make([], []);
        foreach ($v->errors() as $f => $p) {
            $baru->tambah($f, $f === $field ? $pesan : $p);
        }

        return $baru;
    }

    private static function kodeBaru(): string
    {
        do {
            $kode = '';
            for ($i = 0; $i < 8; $i++) {
                $kode .= self::ALFABET_KODE[random_int(0, strlen(self::ALFABET_KODE) - 1)];
            }
        } while (Donasi::kodeAda($kode));

        return $kode;
    }
}
