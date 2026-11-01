<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Validator;
use App\Models\PerangkatDaerah;

/** Perangkat Daerah: nama dirapikan & tidak boleh kembar (kolom UNIQUE). */
final class PerangkatDaerahHook extends Hook
{
    public function sebelumSimpan(array $data, ?array $lama, Validator $v): array
    {
        if (!isset($data['nama'])) {
            return $data;
        }
        $data['nama'] = PerangkatDaerah::rapikan((string) $data['nama']);
        $ada = PerangkatDaerah::idDenganNama($data['nama']);
        if ($ada !== null && $ada !== (int) ($lama['id'] ?? 0)) {
            $v->tambah('nama', 'Perangkat daerah dengan nama ini sudah ada.');
        }

        return $data;
    }
}
