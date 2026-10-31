<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Validator;
use App\Models\Pengumpulan;

/** Pengumpulan: minimal satu jenis bernominal; total = zakat + infaq + dskl (dihitung di server, bukan dari form). */
final class PengumpulanHook extends Hook
{
    public function sebelumSimpan(array $data, ?array $lama, Validator $v): array
    {
        $total = 0;
        foreach (array_keys(Pengumpulan::JENIS) as $k) {
            $total += is_int($data[$k] ?? null) ? $data[$k] : 0;
        }
        // Bila salah satu nominal sudah salah format, pesan itu yang tampil (tambah() tidak menimpa)
        if ($total <= 0 && array_intersect_key($v->errors(), Pengumpulan::JENIS) === []) {
            $v->tambah('zakat', 'Isi minimal satu nominal: Zakat, Infaq/Sedekah, atau DSKL.');
        }
        $data['total'] = $total;

        return $data;
    }
}
