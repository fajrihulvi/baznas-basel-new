<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Validator;
use App\Models\PenerimaManfaat;
use App\Models\Pengumpulan;

/** Penerima manfaat: satu data per bulan, bulan tidak di masa depan, minimal satu pilar terisi; total dihitung di server. */
final class PenerimaManfaatHook extends Hook
{
    public function sebelumSimpan(array $data, ?array $lama, Validator $v): array
    {
        $periode = (string) ($data['periode'] ?? '');
        if (!isset($v->errors()['periode'])) {
            if ($periode > date('Y-m-01')) {
                $v->tambah('periode', 'Bulan laporan belum berjalan.');
            } elseif (PenerimaManfaat::adaPeriode($periode, $lama !== null ? (int) $lama['id'] : null)) {
                $v->tambah('periode', 'Data ' . Pengumpulan::namaPeriode($periode) . ' sudah ada. Ubah data bulan itu dari daftar.');
            }
        }

        $total = 0;
        foreach (array_keys(PenerimaManfaat::PROGRAM) as $k) {
            $total += (int) ($data[$k] ?? 0);
        }
        if ($total <= 0 && array_intersect_key($v->errors(), PenerimaManfaat::PROGRAM) === []) {
            $v->tambah('peduli', 'Isi jumlah penerima minimal untuk satu pilar.');
        }
        $data['total'] = max(0, $total);

        return $data;
    }
}
