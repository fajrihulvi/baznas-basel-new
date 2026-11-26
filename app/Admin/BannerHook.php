<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Validator;
use App\Services\DonasiService;

/** Banner donasi: detail program wajib lengkap bila dicentang "Banner program donasi". */
final class BannerHook extends Hook
{
    public function sebelumSimpan(array $data, ?array $lama, Validator $v): array
    {
        if (empty($data['donasi'])) {
            return $data;
        }
        $wajib = ['donasi_nama' => 'Nama program donasi', 'donasi_mulai' => 'Tanggal dibuka', 'donasi_selesai' => 'Tanggal ditutup', 'donasi_target' => 'Target terkumpul'];
        foreach ($wajib as $k => $label) {
            if (($data[$k] ?? null) === null || $data[$k] === '') {
                $v->tambah($k, $label . ' wajib diisi untuk banner donasi.');
            }
        }
        if (!empty($data['donasi_mulai']) && !empty($data['donasi_selesai']) && $data['donasi_selesai'] < $data['donasi_mulai']) {
            $v->tambah('donasi_selesai', 'Tanggal ditutup tidak boleh sebelum tanggal dibuka.');
        }
        $data['donasi_minimal'] ??= DonasiService::MIN_BAWAAN;
        if (is_int($data['donasi_target'] ?? null) && $data['donasi_target'] < $data['donasi_minimal']) {
            $v->tambah('donasi_target', 'Target terkumpul harus lebih besar dari nominal minimal.');
        }

        return $data;
    }
}
