<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Setting
{
    /** @return array<string, string|null> kunci => nilai */
    public static function all(): array
    {
        $rows = Database::fetchAll('SELECT kunci, nilai FROM setting');

        return array_column($rows, 'nilai', 'kunci');
    }
}
