<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Banner
{
    /**
     * Banner terbit untuk hero beranda. Banner donasi (migrasi 021) selalu menuju /donasi/{slug}.
     * SELECT * agar tetap jalan sebelum migrasi 021 (kolom donasi belum ada).
     */
    public static function terbit(int $limit): array
    {
        $baris = Database::fetchAll(
            "SELECT * FROM banner WHERE publish = 'Y' ORDER BY urutan, tgl DESC, id_banner DESC LIMIT ?",
            [$limit]
        );

        return array_map(static fn (array $b): array => [
            'id'          => (int) $b['id_banner'],
            'judul'       => (string) $b['judul'],
            'foto'        => (string) $b['foto'],
            'foto_mobile' => $b['foto_mobile'] ?: null,
            'link'        => !empty($b['donasi']) && !empty($b['donasi_slug']) ? '/donasi/' . $b['donasi_slug'] : ($b['link'] ?: null),
        ], $baris);
    }
}
