<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\RateLimiter;
use App\Core\Validator;
use App\Models\Testimoni;

/**
 * Testimoni yang diisi sendiri oleh muzaki/mustahik (form beranda & /testimoni/kirim).
 * Disimpan sebagai draf; tampil setelah admin mencentang "Tampilkan di situs".
 */
final class TestimoniService
{
    public const FIELD = ['nama', 'peran', 'profesi', 'rating', 'isi', 'no_hp', 'setuju'];
    private const MAKS_PER_JAM = 3;

    /** @return array{errors: array<string, string>} */
    public static function kirim(array $in, string $ip): array
    {
        // Honeypot: bot mengisi field tersembunyi → pura-pura berhasil, tidak disimpan
        if (trim((string) ($in['website'] ?? '')) !== '') {
            return ['errors' => []];
        }
        $d = [
            'nama'    => trim((string) preg_replace('/\s+/u', ' ', (string) ($in['nama'] ?? ''))),
            'peran'   => (string) ($in['peran'] ?? ''),
            'profesi' => trim((string) ($in['profesi'] ?? '')),
            'rating'  => (string) ($in['rating'] ?? ''),
            'isi'     => trim((string) preg_replace("/[ \t]+/u", ' ', str_replace("\r", '', (string) ($in['isi'] ?? '')))),
            'no_hp'   => trim((string) ($in['no_hp'] ?? '')),
        ];
        $v = Validator::make($d + ['setuju' => (string) ($in['setuju'] ?? '')], [
            'nama'    => 'required|min:2|max:100',
            'peran'   => 'required|in:' . implode(',', array_keys(Testimoni::PERAN)),
            'profesi' => 'nullable|max:100',
            'rating'  => 'required|in:1,2,3,4,5',
            'isi'     => 'required|min:20|max:1000',
            'no_hp'   => ['nullable', 'regex:/^(\+62|62|0)8[0-9 -]{7,14}$/'],
            'setuju'  => 'required',
        ], [
            'nama' => 'Nama', 'peran' => 'Anda sebagai', 'profesi' => 'Keterangan', 'rating' => 'Penilaian',
            'isi' => 'Cerita Anda', 'no_hp' => 'Nomor WhatsApp', 'setuju' => 'Persetujuan',
        ]);
        // Kiriman berisi tautan hampir selalu spam
        if (!isset($v->errors()['isi']) && preg_match('#(https?://|www\.|\.(com|net|xyz|id)/)#i', $d['isi'] . ' ' . $d['nama'] . ' ' . $d['profesi'])) {
            $v->tambah('isi', 'Cerita tidak boleh berisi tautan/alamat situs.');
        }
        if ($v->fails()) {
            return ['errors' => $v->errors()];
        }
        // Dihitung setelah isian valid: salah ketik tidak menghabiskan jatah, kiriman tersimpan dibatasi 3/jam per IP
        if (RateLimiter::terlalu('testimoni:' . $ip, self::MAKS_PER_JAM, 3600)) {
            return ['errors' => ['_form' => 'Terlalu banyak kiriman dari jaringan Anda. Silakan coba lagi dalam satu jam.']];
        }

        Testimoni::kirim([
            'nama' => mb_substr($d['nama'], 0, 100), 'peran' => $d['peran'], 'profesi' => $d['profesi'] !== '' ? $d['profesi'] : null,
            'rating' => (int) $d['rating'], 'isi' => $d['isi'], 'no_hp' => $d['no_hp'] !== '' ? $d['no_hp'] : null,
        ]);

        return ['errors' => []];
    }
}
