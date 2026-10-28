<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    /** @var array<string, array{0: string, 1: string}> nama header (huruf kecil) => [nama asli, nilai] */
    private array $headers = [];

    /** @param array<string, string> $headers */
    public function __construct(
        private string $body = '',
        private int $status = 200,
        array $headers = [],
    ) {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }
    }

    public static function html(string $body, int $status = 200): self
    {
        return new self($body, $status, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    public static function json(mixed $data, int $status = 200): self
    {
        $body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        return new self($body, $status, ['Content-Type' => 'application/json; charset=UTF-8']);
    }

    public static function redirect(string $url, int $status = 302): self
    {
        return new self('', $status, ['Location' => $url]);
    }

    public function header(string $name, string $value): self
    {
        $this->headers[strtolower($name)] = [$name, $value];

        return $this;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)][1] ?? null;
    }

    public function status(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function body(): string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function send(bool $withBody = true): void
    {
        if (!headers_sent()) {
            http_response_code($this->status);
            header_remove('X-Powered-By');
            foreach ($this->headers as [$name, $value]) {
                header($name . ': ' . $value);
            }
        }

        if ($withBody) {
            echo $this->body;
        }
    }
}
