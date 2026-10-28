<?php

declare(strict_types=1);

namespace App\Admin;

use App\Core\Validator;
use App\Services\HtmlSanitizer;

/** Video: ID YouTube diambil dari tautan (untuk thumbnail & iframe youtube-nocookie). */
final class VideoHook extends Hook
{
    public function kolomTambahan(): array
    {
        return ['youtube_id'];
    }

    public function sebelumSimpan(array $data, ?array $lama, Validator $v): array
    {
        $id = HtmlSanitizer::youtubeId((string) ($data['link'] ?? ''));
        if ($id === null) {
            $v->tambah('link', 'Tautan YouTube tidak dikenali. Salin alamat video dari tombol "Bagikan" di YouTube.');
        }
        $data['youtube_id'] = $id;

        return $data;
    }
}
