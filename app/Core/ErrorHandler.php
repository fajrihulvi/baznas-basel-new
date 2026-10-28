<?php

declare(strict_types=1);

namespace App\Core;

use ErrorException;
use Throwable;

/**
 * Mengubah semua error PHP menjadi exception, mencatat error 5xx ke log,
 * dan menampilkan halaman 404/500 yang ramah. Detail teknis hanya tampil bila APP_DEBUG=true.
 */
final class ErrorHandler
{
    private const MESSAGES = [
        403 => 'Anda tidak memiliki akses ke halaman ini.',
        404 => 'Halaman yang Anda cari tidak ditemukan atau sudah dipindahkan.',
        405 => 'Metode permintaan tidak diizinkan untuk halaman ini.',
        413 => 'Ukuran file yang dikirim terlalu besar. Kecilkan file lalu coba lagi.',
        419 => 'Sesi formulir sudah kedaluwarsa. Muat ulang halaman, lalu coba kirim lagi.',
        429 => 'Terlalu banyak permintaan. Silakan tunggu sebentar lalu coba lagi.',
        500 => 'Terjadi kesalahan pada server. Silakan coba lagi beberapa saat lagi.',
    ];

    private const TITLES = [
        403 => 'Akses ditolak',
        404 => 'Halaman tidak ditemukan',
        405 => 'Metode tidak diizinkan',
        413 => 'File terlalu besar',
        422 => 'Permintaan tidak valid',
        419 => 'Sesi kedaluwarsa',
        429 => 'Terlalu banyak permintaan',
        500 => 'Terjadi kesalahan',
    ];

    public static function register(): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');
        ini_set('log_errors', '1');

        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler(static function (Throwable $e): void {
            self::cleanBuffers();
            self::toResponse($e)->send();
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::cleanBuffers();
                self::toResponse(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']))->send();
            }
        });
    }

    public static function toResponse(Throwable $e): Response
    {
        $status = $e instanceof HttpException ? $e->status() : 500;

        if ($status >= 500) {
            Logger::error($e->getMessage(), [
                'exception' => $e::class,
                'at'        => $e->getFile() . ':' . $e->getLine(),
                'url'       => $_SERVER['REQUEST_URI'] ?? '',
            ]);
        }

        $title   = self::TITLES[$status] ?? self::TITLES[$status >= 500 ? 500 : 404];
        $message = $e instanceof HttpException && $e->getMessage() !== ''
            ? $e->getMessage()
            : (self::MESSAGES[$status] ?? self::MESSAGES[500]);

        try {
            $html = View::make($status === 404 ? 'errors/404' : 'errors/error', [
                'status'    => $status,
                'title'     => $title,
                'message'   => $message,
                'exception' => $status >= 500 && Config::get('app.debug') ? $e : null,
            ]);
        } catch (Throwable $inner) {
            Logger::error('Gagal merender halaman error: ' . $inner->getMessage());
            $html = '<!doctype html><html lang="id"><meta charset="utf-8"><title>' . e($title) . '</title><p>' . e($message) . '</p></html>';
        }

        $response = Response::html($html, $status)->header('Cache-Control', 'no-store');
        if ($e instanceof HttpException) {
            foreach ($e->headers() as $name => $value) {
                $response->header($name, $value);
            }
        }

        return $response;
    }

    private static function cleanBuffers(): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
    }
}
