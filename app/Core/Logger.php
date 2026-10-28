<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Log harian ke storage/logs/app-YYYY-MM-DD.log.
 */
final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::write('WARNING', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context): void
    {
        $line = sprintf(
            "[%s] %s: %s%s\n",
            date('Y-m-d H:i:s'),
            $level,
            $message,
            $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        // @: logger tidak boleh memicu error handler (bisa berputar tanpa akhir); jatuh ke log PHP bila gagal
        if (@file_put_contents(base_path('storage/logs/app-' . date('Y-m-d') . '.log'), $line, FILE_APPEND | LOCK_EX) === false) {
            error_log(rtrim($line));
        }
    }
}
