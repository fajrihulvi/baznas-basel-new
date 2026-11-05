<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;
use App\Core\Cache;
use App\Core\Request;
use App\Models\Post;
use PDOException;

/**
 * Penghitung "dilihat" berita. Dipanggil dari sinyal JS (assets/js/dilihat.js), bukan saat halaman dibuka,
 * agar bot, pratinjau tautan, dan permintaan HEAD tidak terhitung.
 *
 * Satu orang satu hitungan: localStorage di perangkat + sidik harian di server (jendela privat, skrip).
 * Sidik = HMAC(IP + browser) dengan garam acak harian — IP tidak pernah disimpan dan sidik tidak bisa dibalik.
 * Nilainya perkiraan: orang yang sama di perangkat lain terhitung lagi; beberapa orang di balik satu IP
 * dengan browser identik pada hari yang sama terhitung sekali.
 */
final class DilihatService
{
    /** Agen otomatis yang menjalankan JS. Pratinjau WhatsApp/Facebook dsb. tidak menjalankan JS, jadi tidak perlu didaftar. */
    private const BOT = '/bot|crawl|spider|slurp|lighthouse|pagespeed|gtmetrix|headless|phantom|puppeteer|playwright|selenium|curl|wget|python|httpclient|uptime/i';

    /** Sinyal hanya sah dari halaman situs ini: pengganti CSRF karena endpoint ini sengaja tanpa sesi. */
    public static function dariSitusIni(Request $request): bool
    {
        $situs = $request->header('Sec-Fetch-Site');
        if ($situs !== null && $situs !== 'same-origin') {
            return false;
        }
        $asal = $request->header('Origin');
        if ($asal === null) {
            return false; // fetch POST di browser selalu mengirim Origin
        }

        return strtolower((string) parse_url($asal, PHP_URL_HOST)) === $request->host();
    }

    public static function bot(?string $userAgent): bool
    {
        return $userAgent === null || trim($userAgent) === '' || preg_match(self::BOT, $userAgent) === 1;
    }

    /** Catat satu kunjungan bila belum tercatat hari ini. Petugas yang sedang masuk admin tidak dihitung. */
    public static function catat(int $idPost, Request $request): void
    {
        if (Auth::user() !== null) {
            return;
        }
        $hariIni = date('Y-m-d');
        try {
            Post::catatKunjungan($idPost, self::sidik($idPost, $request->ip(), (string) $request->header('User-Agent'), $hariIni), $hariIni);
            if (random_int(1, 100) === 1) {
                Post::hapusKunjunganLama($hariIni); // cadangan bila cron rawat.php belum dipasang
            }
        } catch (PDOException $e) {
            if ($e->getCode() !== '42S02') { // tabel belum ada: kode terunggah sebelum migrasi 014
                throw $e;
            }
        }
    }

    private static function sidik(int $idPost, string $ip, string $userAgent, string $hari): string
    {
        // Garam acak per hari, hanya di cache (bukan DB) — setelah berganti, sidik lama tidak bisa dicocokkan lagi
        $garam = (string) Cache::remember('dilihat-garam-' . $hari, 172800, static fn (): string => bin2hex(random_bytes(32)));

        return substr(hash_hmac('sha256', $idPost . '|' . $ip . '|' . $userAgent, $garam), 0, 32);
    }
}
