<?php

declare(strict_types=1);

namespace App\Models;

/**
 * Distribusi/penyaluran dana ke sub-program (tabel `distribusi`, CRUD lewat config/admin.php).
 * Pilihan di bawah dipakai bersama oleh form admin & statistik dasbor. Data internal — bukan untuk halaman publik.
 */
final class Distribusi
{
    public const SUMBER = [
        'zakat' => 'Zakat',
        'infaq' => 'Infak/Sedekah',
        'dskl'  => 'DSKL (dana sosial keagamaan lainnya)',
    ];

    /** 8 golongan penerima zakat (QS. At-Taubah: 60) */
    public const ASNAF = [
        'fakir'        => 'Fakir',
        'miskin'       => 'Miskin',
        'amil'         => 'Amil',
        'mualaf'       => 'Mualaf',
        'riqab'        => 'Riqab',
        'gharimin'     => 'Gharimin',
        'fisabilillah' => 'Fisabilillah',
        'ibnu_sabil'   => 'Ibnu Sabil',
    ];

    public const SIFAT = [
        'konsumtif' => 'Konsumtif (pendistribusian)',
        'produktif' => 'Produktif (pendayagunaan)',
    ];

    public const KECAMATAN = [
        'Toboali'          => 'Toboali',
        'Air Gegas'        => 'Air Gegas',
        'Payung'           => 'Payung',
        'Simpang Rimba'    => 'Simpang Rimba',
        'Tukak Sadai'      => 'Tukak Sadai',
        'Pulau Besar'      => 'Pulau Besar',
        'Lepar Pongok'     => 'Lepar Pongok',
        'Kepulauan Pongok' => 'Kepulauan Pongok',
        'Lintas kecamatan' => 'Lintas kecamatan',
    ];
}
