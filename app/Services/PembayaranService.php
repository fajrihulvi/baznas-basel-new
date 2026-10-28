<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\HttpException;
use App\Core\RateLimiter;
use App\Core\Upload;
use App\Core\UploadException;
use App\Core\Validator;
use App\Models\JenisZakat;
use App\Models\KonfirmasiPembayaran;
use App\Models\Pembayaran;
use Throwable;

/**
 * Alur bayar zakat (§12) — validasi ulang di server, honeypot, rate limit per IP, kode unik acak.
 */
final class PembayaranService
{
    public const MIN_NOMINAL = 10000;
    public const MAX_NOMINAL = 10_000_000_000;
    public const MAKS_BUKTI  = 5 * 1048576;

    /** Huruf tanpa I/O dan angka tanpa 0/1 agar kode mudah dibaca & diketik */
    private const ALFABET_KODE = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    /** Field yang boleh dikembalikan ke form (old input) */
    public const FIELD_BAYAR = ['jenis', 'jiwa', 'hari', 'nominal', 'sapaan', 'nama_lengkap', 'no_hp', 'email', 'npwz'];
    public const FIELD_KONFIRMASI = ['kode', 'bank_pengirim', 'rekening', 'tgl_transfer', 'nominal_transfer'];

    /**
     * Jenis zakat dengan mode isian: fitrah (jumlah jiwa), fidyah (jumlah hari), atau nominal.
     * 'kalkulator' = jenis kalkulator inline yang tersedia (penghasilan/emas/perdagangan).
     *
     * @return list<array{id: int, nama: string, utama: bool, mode: string, kalkulator: ?string}>
     */
    public static function jenis(): array
    {
        $urutan = ['penghasilan' => 1, 'fitrah' => 2, 'fidyah' => 3, 'infaq' => 4, 'sedekah' => 5];
        $daftar = [];
        foreach (JenisZakat::semua() as $j) {
            $n = mb_strtolower($j['nama']);
            $kunci = null;
            foreach (array_keys($urutan) as $k) {
                if (str_contains($n, $k)) {
                    $kunci = $k;
                }
            }
            $daftar[] = [
                'id'         => (int) $j['id'],
                'nama'       => $j['nama'] === 'Zakat Perusahan' ? 'Zakat Perusahaan' : $j['nama'],
                'utama'      => $kunci !== null,
                'urut'       => $urutan[$kunci] ?? 10 + (int) $j['id'],
                'mode'       => match ($kunci) { 'fitrah' => 'fitrah', 'fidyah' => 'fidyah', default => 'nominal' },
                'kalkulator' => match (true) {
                    $kunci === 'penghasilan'          => 'penghasilan',
                    str_contains($n, 'emas')          => 'emas',
                    str_contains($n, 'perdagangan')   => 'perdagangan',
                    default                           => null,
                },
            ];
        }
        usort($daftar, static fn (array $a, array $b): int => $a['urut'] <=> $b['urut']);

        return array_map(static function (array $j): array {
            unset($j['urut']);
            return $j;
        }, $daftar);
    }

    /**
     * Simpan pembayaran baru.
     *
     * @return array{kode: ?string, errors: array<string, string>}
     */
    public static function buat(array $in, string $ip): array
    {
        if (RateLimiter::terlalu('bayar:' . $ip, 10, 600)) {
            throw new HttpException(429);
        }
        if (trim((string) ($in['website'] ?? '')) !== '') {
            return ['kode' => null, 'errors' => ['_form' => 'Permintaan tidak dapat diproses. Muat ulang halaman lalu coba lagi.']];
        }

        $in['no_hp'] = self::normalisasiHp((string) ($in['no_hp'] ?? ''));
        $v = Validator::make($in, [
            'jenis'        => 'required|integer',
            'sapaan'       => 'required|in:BAPAK,IBU',
            'nama_lengkap' => 'required|min:3|max:100',
            'no_hp'        => ['required', 'regex:/^08\d{7,12}$/'],
            'email'        => 'nullable|email|max:100',
            'npwz'         => ['nullable', 'max:50', 'regex:/^[A-Za-z0-9.\-\/]+$/'],
        ], ['jenis' => 'Jenis zakat', 'nama_lengkap' => 'Nama lengkap', 'no_hp' => 'Nomor WhatsApp', 'email' => 'Email', 'npwz' => 'NPWZ']);

        $jenis = null;
        foreach (self::jenis() as $j) {
            if ((string) $j['id'] === (string) ($in['jenis'] ?? '')) {
                $jenis = $j;
            }
        }
        if ($jenis === null) {
            $v->tambah('jenis', 'Pilih jenis zakat.');
        }

        [$nominal, $hari, $jiwa] = $jenis ? self::nominal($jenis['mode'], $in, $v) : [0, null, null];

        if ($v->fails()) {
            return ['kode' => null, 'errors' => $v->errors()];
        }

        $kode = self::kodeBaru();
        Pembayaran::buat([
            'kode'    => $kode,
            'sapaan'  => $in['sapaan'],
            'nama'    => trim((string) $in['nama_lengkap']),
            'npwz'    => trim((string) ($in['npwz'] ?? '')) ?: null,
            'no_hp'   => $in['no_hp'],
            'email'   => trim((string) ($in['email'] ?? '')) ?: null,
            'jenis'   => $jenis['id'],
            'nominal' => $nominal,
            'hari'    => $hari,
            'jiwa'    => $jiwa,
        ]);

        return ['kode' => $kode, 'errors' => []];
    }

    /**
     * Simpan konfirmasi transfer + bukti.
     *
     * @return array{kode: ?string, errors: array<string, string>}
     */
    public static function konfirmasi(array $in, ?array $file, string $ip): array
    {
        if (RateLimiter::terlalu('konfirmasi:' . $ip, 10, 600)) {
            throw new HttpException(429);
        }
        if (trim((string) ($in['website'] ?? '')) !== '') {
            return ['kode' => null, 'errors' => ['_form' => 'Permintaan tidak dapat diproses. Muat ulang halaman lalu coba lagi.']];
        }

        $in['nominal_transfer'] = (string) angka($in['nominal_transfer'] ?? '');
        $v = Validator::make($in, [
            'kode'             => ['required', 'regex:/^[A-Za-z0-9]{4,12}$/'],
            'bank_pengirim'    => 'required|min:2|max:100',
            'rekening'         => 'required|integer',
            'tgl_transfer'     => 'required|date|before_or_equal:today',
            'nominal_transfer' => 'required|between:' . self::MIN_NOMINAL . ',' . self::MAX_NOMINAL,
        ], ['kode' => 'Kode pembayaran', 'bank_pengirim' => 'Bank pengirim', 'rekening' => 'Rekening tujuan', 'tgl_transfer' => 'Tanggal transfer', 'nominal_transfer' => 'Nominal transfer']);

        $pembayaran = !isset($v->errors()['kode']) ? Pembayaran::byKode((string) $in['kode']) : null;
        if (!isset($v->errors()['kode'])) {
            if ($pembayaran === null) {
                $v->tambah('kode', 'Kode pembayaran tidak ditemukan. Periksa kembali kode pada halaman instruksi transfer.');
            } elseif ($pembayaran['status'] === Pembayaran::SELESAI) {
                $v->tambah('kode', 'Pembayaran ini sudah diverifikasi. Terima kasih.');
            } elseif ($pembayaran['status'] === Pembayaran::GAGAL) {
                $v->tambah('kode', 'Pembayaran ini dibatalkan. Silakan hubungi kami.');
            } elseif (count(array_filter(KonfirmasiPembayaran::perPembayaran((int) $pembayaran['id']), static fn (array $k): bool => $k['status'] === 'baru')) >= 3) {
                $v->tambah('kode', 'Konfirmasi untuk kode ini sudah kami terima dan sedang diverifikasi.');
            }
        }

        $rekening = null;
        foreach (SiteService::rekening() as $r) {
            if ((string) $r['id'] === (string) ($in['rekening'] ?? '')) {
                $rekening = $r;
            }
        }
        if ($rekening === null && !isset($v->errors()['rekening'])) {
            $v->tambah('rekening', 'Pilih rekening tujuan.');
        }

        if ($v->fails()) {
            return ['kode' => null, 'errors' => $v->errors()];
        }

        // File disimpan paling akhir: tidak ada file yatim bila isian lain belum valid
        $folder = base_path('storage/bukti');
        try {
            $bukti = Upload::simpan($file, $folder, self::MAKS_BUKTI);
        } catch (UploadException $e) {
            return ['kode' => null, 'errors' => ['bukti' => $e->getMessage()]];
        }

        try {
            KonfirmasiPembayaran::buat([
                'id_pembayaran' => (int) $pembayaran['id'],
                'kode'          => $pembayaran['kode'],
                'bank_pengirim' => trim((string) $in['bank_pengirim']),
                'bank_tujuan'   => trim($rekening['nama_bank']),
                'norek_tujuan'  => $rekening['nomor_rekening'],
                'tgl_transfer'  => $in['tgl_transfer'],
                'nominal'       => (int) $in['nominal_transfer'],
                'bukti'         => $bukti,
            ]);
        } catch (Throwable $e) {
            @unlink($folder . '/' . $bukti);
            throw $e;
        }

        return ['kode' => $pembayaran['kode'], 'errors' => []];
    }

    /** Rekening yang disarankan: rekening zakat untuk zakat, rekening infaq untuk infaq/sedekah. */
    public static function rekeningUntuk(string $namaJenis): array
    {
        $semua = SiteService::rekening();
        $n = mb_strtolower($namaJenis);
        $kata = str_contains($n, 'infaq') || str_contains($n, 'sedekah') ? 'infaq' : (str_contains($n, 'zakat') ? 'zakat' : null);
        if ($kata === null) {
            return $semua;
        }
        $cocok = array_values(array_filter($semua, static fn (array $r): bool => str_contains(mb_strtolower($r['nama_bank'] . ' ' . $r['atas_nama']), $kata)));

        return $cocok !== [] ? $cocok : $semua;
    }

    /** "+62 812-3456-789" / "62812…" / "812…" → "0812…" */
    public static function normalisasiHp(string $hp): string
    {
        $digit = (string) preg_replace('/\D/', '', $hp);
        if (str_starts_with($digit, '62')) {
            $digit = '0' . substr($digit, 2);
        } elseif (str_starts_with($digit, '8')) {
            $digit = '0' . $digit;
        }

        return $digit;
    }

    /**
     * Nominal final menurut mode jenis — dihitung ulang di server, tidak percaya angka dari browser
     * bila nilai fitrah/fidyah sudah ditetapkan admin.
     *
     * @return array{0: int, 1: ?int, 2: ?int} nominal, jumlah hari, jumlah jiwa
     */
    private static function nominal(string $mode, array $in, Validator $v): array
    {
        $p = ZakatCalculator::parameter();
        $rentang = 'between:' . self::MIN_NOMINAL . ',' . self::MAX_NOMINAL;
        $label = ['jiwa' => 'Jumlah jiwa', 'hari' => 'Jumlah hari', 'nominal' => 'Nominal'];

        if ($mode === 'fitrah' || $mode === 'fidyah') {
            $kolom = $mode === 'fitrah' ? 'jiwa' : 'hari';
            $nilaiTetap = $mode === 'fitrah' ? $p['nilai_fitrah'] : $p['nilai_fidyah'];
            $cek = Validator::make($in, [$kolom => $mode === 'fitrah' ? 'required|between:1,100' : 'required|between:1,365'], $label);
            $nominal = 0;
            if ($nilaiTetap !== null) {
                $nominal = angka($in[$kolom] ?? '') * $nilaiTetap;
            } else {
                $in['nominal'] = (string) angka($in['nominal'] ?? '');
                $cek = Validator::make($in, [$kolom => $mode === 'fitrah' ? 'required|between:1,100' : 'required|between:1,365', 'nominal' => 'required|' . $rentang], $label);
                $nominal = (int) $in['nominal'];
            }
            foreach ($cek->errors() as $f => $pesan) {
                $v->tambah($f, $pesan);
            }
            $jumlah = angka($in[$kolom] ?? '');

            return [$nominal, $mode === 'fidyah' ? $jumlah : null, $mode === 'fitrah' ? $jumlah : null];
        }

        $in['nominal'] = (string) angka($in['nominal'] ?? '');
        foreach (Validator::make($in, ['nominal' => 'required|' . $rentang], $label)->errors() as $f => $pesan) {
            $v->tambah($f, $pesan);
        }

        return [(int) $in['nominal'], null, null];
    }

    private static function kodeBaru(): string
    {
        do {
            $kode = '';
            for ($i = 0; $i < 8; $i++) {
                $kode .= self::ALFABET_KODE[random_int(0, strlen(self::ALFABET_KODE) - 1)];
            }
        } while (Pembayaran::kodeAda($kode));

        return $kode;
    }
}
