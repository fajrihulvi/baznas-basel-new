<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Pemetaan URL lama → baru (301). */
final class Redirect
{
    /** @return array{id: int, ke: string}|null */
    public static function cari(string $dari): ?array
    {
        return Database::fetch('SELECT id, ke FROM redirect WHERE dari = ?', [$dari]);
    }

    public static function catatHit(int $id): void
    {
        Database::execute('UPDATE redirect SET hit = hit + 1 WHERE id = ?', [$id]);
    }
}
