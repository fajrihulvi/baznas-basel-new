<?php

declare(strict_types=1);

namespace App\Admin;

use App\Controllers\Admin\ResourceController;
use App\Core\Validator;
use App\Models\Donasi;
use App\Services\PembayaranService;

/** Donasi Bantuan: kode acak untuk data tambahan admin, waktu verifikasi & petugas dicatat saat status berubah. */
final class DonasiHook extends Hook
{
    public function kolomTambahan(): array
    {
        return ['kode', 'diverifikasi', 'petugas', 'bank_tujuan', 'norek_tujuan'];
    }

    public function sebelumSimpan(array $data, ?array $lama, Validator $v): array
    {
        if (isset($data['no_hp'])) {
            $data['no_hp'] = PembayaranService::normalisasiHp((string) $data['no_hp']);
            if (!preg_match('/^08\d{7,12}$/', $data['no_hp'])) {
                $v->tambah('no_hp', 'Format Nomor WhatsApp tidak valid.');
            }
        }
        if ($lama === null) {
            do {
                $kode = 'A' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 7));
            } while (Donasi::kodeAda($kode));
            $data['kode'] = $kode;
        }
        $statusLama = $lama['status'] ?? null;
        if (($data['status'] ?? null) !== $statusLama) {
            $data['diverifikasi'] = ($data['status'] ?? '') === 'terverifikasi' ? date('Y-m-d H:i:s') : null;
            $data['petugas'] = ResourceController::namaAdmin() ?: null;
        }
        // Rekening dipilih admin → salin nama bank & nomor (sama seperti formulir publik)
        if (($data['id_rekening'] ?? null) !== null && (string) $data['id_rekening'] !== (string) ($lama['id_rekening'] ?? '')) {
            foreach (\App\Services\SiteService::rekening() as $r) {
                if ((string) $r['id'] === (string) $data['id_rekening']) {
                    $data['bank_tujuan'] = trim($r['nama_bank']);
                    $data['norek_tujuan'] = $r['nomor_rekening'];
                }
            }
        }

        return $data;
    }
}
