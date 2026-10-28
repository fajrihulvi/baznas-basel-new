<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Cache;
use App\Core\Database;
use InvalidArgumentException;

/**
 * Satu jenis konten admin berdasarkan definisi di config/admin.php.
 * Nama tabel/kolom berasal dari konfigurasi tepercaya dan tetap dicek polanya; nilai selalu lewat parameter.
 */
final class Resource
{
    private ?Hook $hook = null;

    /** @var array<string, array<string, string>> cache pilihan per field */
    private array $pilihan = [];

    private function __construct(public readonly string $kunci, private readonly array $def)
    {
        foreach ([$def['tabel'], $def['pk'], ...array_keys($def['field'])] as $nama) {
            self::cekNama($nama);
        }
    }

    public static function cari(string $kunci): ?self
    {
        $def = config('admin.resource.' . $kunci);

        return is_array($def) ? new self($kunci, $def) : null;
    }

    public function judul(): string
    {
        return $this->def['judul'];
    }

    public function tunggal(): string
    {
        return $this->def['tunggal'] ?? 'data';
    }

    public function pk(): string
    {
        return $this->def['pk'];
    }

    /** @return array<string, array> */
    public function field(): array
    {
        return $this->def['field'];
    }

    /** @return array<string, string> */
    public function kolom(): array
    {
        return $this->def['kolom'];
    }

    public function boleh(string $aksi): bool
    {
        return in_array($aksi, $this->def['aksi'] ?? ['tambah', 'ubah', 'hapus'], true);
    }

    /** URL publik untuk satu baris (mis. /berita/{slug}), atau null. */
    public function tautan(array $baris): ?string
    {
        $pola = $this->def['tautan'] ?? null;
        if ($pola === null || (isset($baris['publish']) && $baris['publish'] !== 'Y')) {
            return null;
        }

        return self::isiPola($pola, $baris);
    }

    /** Tombol tambahan per baris di daftar ('aksi_baris': label => pola URL admin). @return array<string, string> */
    public function aksiBaris(array $baris): array
    {
        return array_map(static fn (string $pola): string => self::isiPola($pola, $baris), $this->def['aksi_baris'] ?? []);
    }

    /** Info tambahan di atas daftar (mis. penjelasan cara kerja). */
    public function keterangan(): ?string
    {
        return $this->def['keterangan'] ?? null;
    }

    private static function isiPola(string $pola, array $baris): string
    {
        return (string) preg_replace_callback('/\{([a-z_]+)\}/', static fn (array $m): string => rawurlencode((string) ($baris[$m[1]] ?? '')), $pola);
    }

    public function hook(): Hook
    {
        return $this->hook ??= new ($this->def['hook'] ?? Hook::class)();
    }

    public function daftar(string $q, int $limit, int $offset): array
    {
        [$where, $params] = $this->filterCari($q);

        return Database::fetchAll(
            "SELECT * FROM {$this->def['tabel']}{$where} ORDER BY {$this->def['urut']} LIMIT ? OFFSET ?",
            [...$params, $limit, $offset]
        );
    }

    public function hitung(string $q): int
    {
        [$where, $params] = $this->filterCari($q);

        return (int) Database::fetchColumn("SELECT COUNT(*) FROM {$this->def['tabel']}{$where}", $params);
    }

    public function satu(int $id): ?array
    {
        return Database::fetch("SELECT * FROM {$this->def['tabel']} WHERE {$this->def['pk']} = ?", [$id]);
    }

    /** Pilihan untuk field bertipe pilihan: statis atau hasil query 'sumber'. @return array<string, string> */
    public function pilihan(string $field): array
    {
        if (!isset($this->pilihan[$field])) {
            $f = $this->def['field'][$field];
            if (isset($f['sumber'])) {
                $hasil = [];
                foreach (Database::fetchAll($f['sumber']) as $baris) {
                    $nilai = array_values($baris);
                    $hasil[(string) $nilai[0]] = (string) $nilai[1];
                }
                $this->pilihan[$field] = $hasil;
            } else {
                $this->pilihan[$field] = array_map('strval', $f['pilihan'] ?? []);
            }
        }

        return $this->pilihan[$field];
    }

    /** INSERT (id null) atau UPDATE; hanya kolom yang dikenal definisi. Mengembalikan id. */
    public function simpan(array $data, ?int $id): int
    {
        $boleh = array_keys($this->def['field']);
        foreach ($this->def['field'] as $f) {
            foreach (['kolom_w', 'kolom_h'] as $k) {
                if (isset($f[$k])) {
                    $boleh[] = $f[$k];
                }
            }
        }
        $boleh = [...$boleh, ...$this->hook()->kolomTambahan()];

        $data = array_intersect_key($data, array_flip($boleh));
        foreach (array_keys($data) as $kolom) {
            self::cekNama($kolom);
        }

        $tabel = $this->def['tabel'];
        if ($id === null) {
            $kolom = implode(', ', array_keys($data));
            $tanda = implode(', ', array_fill(0, count($data), '?'));
            Database::execute("INSERT INTO {$tabel} ({$kolom}) VALUES ({$tanda})", array_values($data));

            return Database::lastInsertId();
        }

        if ($data !== []) {
            $set = implode(', ', array_map(static fn (string $k): string => "{$k} = ?", array_keys($data)));
            Database::execute("UPDATE {$tabel} SET {$set} WHERE {$this->def['pk']} = ?", [...array_values($data), $id]);
        }

        return $id;
    }

    public function hapus(int $id): void
    {
        Database::execute("DELETE FROM {$this->def['tabel']} WHERE {$this->def['pk']} = ?", [$id]);
    }

    /** Hapus cache halaman publik yang memakai konten ini. */
    public function lupakanCache(): void
    {
        foreach ($this->def['cache'] ?? [] as $kunci) {
            Cache::forget($kunci);
        }
    }

    /** @return array{0: string, 1: list<string>} */
    private function filterCari(string $q): array
    {
        $q = trim($q);
        if ($q === '' || empty($this->def['cari'])) {
            return ['', []];
        }
        $pola = '%' . addcslashes($q, '%_\\') . '%';
        $kondisi = array_map(static function (string $k): string {
            self::cekNama($k);
            return "{$k} LIKE ?";
        }, $this->def['cari']);

        return [' WHERE ' . implode(' OR ', $kondisi), array_fill(0, count($kondisi), $pola)];
    }

    private static function cekNama(string $nama): void
    {
        if (!preg_match('/^[a-z_][a-z0-9_]*$/', $nama)) {
            throw new InvalidArgumentException("Nama tabel/kolom tidak valid: {$nama}");
        }
    }
}
