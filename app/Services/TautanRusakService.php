<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\Request;
use App\Models\TautanRusak;
use Throwable;

/**
 * Mencatat 404 dari pengunjung ke tabel tautan_rusak (dilihat di Admin → Tautan Rusak),
 * agar URL lama yang belum dipetakan bisa segera dibuatkan pengalihan.
 */
final class TautanRusakService
{
    /** Probe pemindai keamanan (wp-login.php, .env, …) bukan tautan rusak — tidak dicatat. */
    private const POLA_ABAIKAN = '#(\.(php\d?|aspx?|jsp|cgi|env|git|sql|bak|old|zip|tar|gz|rar|ini|log|ya?ml|json|lock|md|xml\.gz)$)|/wp-|/\.|xmlrpc|phpmyadmin|cgi-bin#i';

    public static function catat(Request $request): void
    {
        $path = $request->path();
        if ($request->method() !== 'GET' || strlen($path) > 255 || str_starts_with($path . '/', '/admin/')
            || preg_match(self::POLA_ABAIKAN, $path) || !mb_check_encoding($path, 'UTF-8')) {
            return;
        }

        $referer = trim((string) $request->header('Referer'));
        $referer = $referer === '' || !preg_match('#^https?://#i', $referer) ? null : mb_substr($referer, 0, 255);

        try {
            TautanRusak::catat($path, $referer);
        } catch (Throwable $e) {
            Logger::error('Gagal mencatat tautan rusak: ' . $e->getMessage());
        }
    }
}
