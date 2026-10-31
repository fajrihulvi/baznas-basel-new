<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Validator;

/** Distribusi: dana zakat wajib mencantumkan asnaf penerima (dasar pelaporan BAZNAS). */
final class DistribusiHook extends Hook
{
    public function sebelumSimpan(array $data, ?array $lama, Validator $v): array
    {
        if (($data['sumber_dana'] ?? null) === 'zakat' && empty($data['asnaf'])) {
            $v->tambah('asnaf', 'Asnaf wajib diisi untuk penyaluran dana zakat.');
        }

        return $data;
    }
}
