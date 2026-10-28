<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Hentikan request dengan status HTTP tertentu (404, 405, 419, 429, ...).
 */
final class HttpException extends RuntimeException
{
    /** @param array<string, string> $headers */
    public function __construct(
        private readonly int $status,
        string $message = '',
        private readonly array $headers = [],
    ) {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    /** @return array<string, string> */
    public function headers(): array
    {
        return $this->headers;
    }
}
