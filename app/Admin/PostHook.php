<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Database;
use App\Core\Validator;

/** Kabar: ringkasan otomatis; slug berubah → redirect 301 dari alamat lama. */
final class PostHook extends Hook
{
    public function sebelumSimpan(array $data, ?array $lama, Validator $v): array
    {
        if (($data['ringkasan'] ?? null) === null && isset($data['konten'])) {
            $data['ringkasan'] = mb_substr(ringkas((string) $data['konten'], 160), 0, 300);
        }

        return $data;
    }

    public function setelahSimpan(int $id, array $data, ?array $lama): void
    {
        if ($lama === null || ($data['slug'] ?? $lama['slug']) === $lama['slug']) {
            return;
        }

        $dari = '/berita/' . $lama['slug'];
        $ke   = '/berita/' . $data['slug'];
        // Alihkan alamat lama, dan perbarui redirect lain yang menuju alamat lama (hindari rantai)
        Database::execute('INSERT INTO redirect (dari, ke) VALUES (?, ?) ON DUPLICATE KEY UPDATE ke = VALUES(ke)', [$dari, $ke]);
        Database::execute('UPDATE redirect SET ke = ? WHERE ke = ?', [$ke, $dari]);
        Database::execute('DELETE FROM redirect WHERE dari = ?', [$ke]);
    }
}
