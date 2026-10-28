<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Validator;

/**
 * Aturan khusus satu jenis konten di admin. Turunkan & timpa yang perlu.
 */
class Hook
{
    /** Kolom tambahan (di luar field form) yang boleh ditulis hook ke tabel. @return list<string> */
    public function kolomTambahan(): array
    {
        return [];
    }

    /** Ubah/lengkapi data sebelum disimpan; tambahkan error lewat $v->tambah(). */
    public function sebelumSimpan(array $data, ?array $lama, Validator $v): array
    {
        return $data;
    }

    public function setelahSimpan(int $id, array $data, ?array $lama): void
    {
    }
}
