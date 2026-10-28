<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Pagination LIMIT/OFFSET dengan link ?hal=N (halaman 1 tanpa parameter).
 * Parameter query lain (mis. ?jenis=berita) dipertahankan.
 */
final class Paginator
{
    public readonly int $page;
    public readonly int $pages;
    public readonly int $offset;

    public function __construct(
        public readonly int $total,
        public readonly int $perPage,
        int $page,
        private readonly string $path = '/',
        private readonly array $query = [],
    ) {
        $this->pages  = max(1, (int) ceil($total / max(1, $perPage)));
        $this->page   = min(max(1, $page), $this->pages);
        $this->offset = ($this->page - 1) * $perPage;
    }

    public static function fromRequest(Request $request, int $total, int $perPage): self
    {
        $hal = $request->query('hal', 1);

        return new self($total, $perPage, is_numeric($hal) ? (int) $hal : 1, $request->path(), $request->queryAll());
    }

    public function url(int $page): string
    {
        $query = $this->query;
        unset($query['hal']);
        if ($page > 1) {
            $query['hal'] = $page;
        }

        return url($this->path, $query);
    }

    public function hasPages(): bool
    {
        return $this->pages > 1;
    }

    /**
     * Nomor halaman yang ditampilkan; null = celah "…". Mis. [1, null, 4, 5, 6, null, 12].
     *
     * @return list<int|null>
     */
    public function window(int $around = 1): array
    {
        $pages = [];
        $last  = 0;
        for ($i = 1; $i <= $this->pages; $i++) {
            if ($i === 1 || $i === $this->pages || abs($i - $this->page) <= $around) {
                if ($last && $i - $last > 1) {
                    $pages[] = null;
                }
                $pages[] = $last = $i;
            }
        }

        return $pages;
    }
}
