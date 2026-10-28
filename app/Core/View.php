<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;
use Throwable;

/**
 * Template PHP dengan layout dan section.
 *
 * Di dalam template:
 *   $this->layout('site', ['title' => '...']);
 *   $this->start('head') ... $this->stop();
 *   echo $this->insert('components/card-post', ['post' => $post]);
 * Di dalam layout:
 *   echo $this->section('content');
 */
final class View
{
    private ?string $layout = null;

    /** @var array<string, mixed> */
    private array $layoutData = [];

    /** @var array<string, string> */
    private array $sections = [];

    /** @var list<string> */
    private array $stack = [];

    public function __construct(private readonly string $path)
    {
    }

    public static function make(string $template, array $data = []): string
    {
        return (new self(base_path('app/Views')))->render($template, $data);
    }

    public function render(string $template, array $data = []): string
    {
        $content = $this->capture($template, $data);
        if ($this->layout === null) {
            return $content;
        }

        $this->sections['content'] = $content;
        $layout = $this->layout;
        $this->layout = null;

        return $this->capture('layouts/' . $layout, array_merge($data, $this->layoutData));
    }

    public function layout(string $name, array $data = []): void
    {
        $this->layout = $name;
        $this->layoutData = $data;
    }

    public function start(string $name): void
    {
        $this->stack[] = $name;
        ob_start();
    }

    public function stop(): void
    {
        $name = array_pop($this->stack) ?? throw new RuntimeException('stop() tanpa start()');
        $this->sections[$name] = (string) ob_get_clean();
    }

    public function section(string $name, string $default = ''): string
    {
        return $this->sections[$name] ?? $default;
    }

    /** Render partial/komponen dan kembalikan HTML-nya (partial bertanggung jawab meng-escape datanya sendiri). */
    public function insert(string $template, array $data = []): string
    {
        return $this->capture($template, $data);
    }

    private function capture(string $template, array $data): string
    {
        $file = $this->path . '/' . $template . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View tidak ditemukan: {$template}");
        }

        $level = ob_get_level();
        ob_start();
        try {
            (function (string $__file, array $__data): void {
                extract($__data, EXTR_SKIP);
                require $__file;
            })($file, $data);
        } catch (Throwable $e) {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            throw $e;
        }

        return (string) ob_get_clean();
    }
}
