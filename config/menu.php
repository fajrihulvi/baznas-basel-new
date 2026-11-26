<?php

declare(strict_types=1);

/*
 * Menu statis situs publik. Bagian bertanda 'dinamis' diisi App\Services\Navigation
 * dari database: 'pilar' (5 pilar program) dan 'halaman:{grup}' (halaman terbit).
 * 'aktif' = awalan path yang menandai menu sebagai aktif.
 */
return [
    // Header desktop (≥ lg): menu dropdown
    'utama' => [
        ['label' => 'Tentang', 'aktif' => ['/tentang', '/amil', '/testimoni'], 'anak' => [
            ['dinamis' => 'halaman:tentang'],
            ['label' => 'Amil BAZNAS', 'url' => '/amil'],
            ['label' => 'Testimoni', 'url' => '/testimoni'],
            ['label' => 'Kontak', 'url' => '#kontak'],
        ]],
        ['label' => 'Layanan', 'aktif' => ['/layanan', '/zakat', '/kalkulator-zakat', '/bayar-zakat', '/konfirmasi', '/rekening'], 'anak' => [
            ['label' => 'Bayar Zakat', 'url' => '/bayar-zakat'],
            ['label' => 'Kalkulator Zakat', 'url' => '/kalkulator-zakat'],
            ['label' => 'Konfirmasi Pembayaran', 'url' => '/konfirmasi'],
            ['label' => 'Rekening Resmi', 'url' => '/rekening'],
            ['label' => 'Penjelasan Zakat', 'url' => '/zakat'],
        ]],
        ['label' => 'Program', 'aktif' => ['/program'], 'anak' => [
            ['dinamis' => 'pilar'],
            ['label' => 'Semua Program', 'url' => '/program'],
        ]],
        ['label' => 'Kabar', 'aktif' => ['/berita', '/video', '/galeri'], 'anak' => [
            ['label' => 'Semua Kabar', 'url' => '/berita'],
            ['label' => 'Berita', 'url' => '/berita?jenis=berita'],
            ['label' => 'Artikel', 'url' => '/berita?jenis=artikel'],
            ['label' => 'Berkah Zakat', 'url' => '/berita?jenis=berkah_zakat'],
            ['label' => 'Video', 'url' => '/video'],
            ['label' => 'Galeri', 'url' => '/galeri'],
        ]],
        ['label' => 'Regulasi', 'aktif' => ['/regulasi', '/laporan'], 'anak' => [
            ['label' => 'Regulasi Zakat', 'url' => '/regulasi'],
            ['label' => 'Laporan Keuangan', 'url' => '/laporan'],
        ]],
    ],

    // Bottom navigation (< lg). 'tengah' = tombol Bayar Zakat yang menonjol; url null = buka menu sheet.
    // 'svg' = ikon slot (partials/ikon-nav) sekaligus kunci tabel ikon_nav (admin → Ikon Navigasi: label & gambar).
    // Ikon tetap di slotnya: slot 2 (ikon kotak 'program') = Layanan, slot 4 (ikon 'kabar') = Program.
    'bawah' => [
        ['label' => 'Beranda', 'url' => '/', 'ikon' => 'home', 'svg' => 'beranda', 'aktif' => ['/'], 'persis' => true],
        ['label' => 'Layanan', 'url' => '/layanan', 'ikon' => 'grid', 'svg' => 'program', 'aktif' => ['/layanan']],
        ['label' => 'Bayar Zakat', 'url' => '/bayar-zakat', 'ikon' => 'heart', 'svg' => 'bayar-zakat', 'aktif' => ['/bayar-zakat'], 'tengah' => true],
        ['label' => 'Program', 'url' => '/program', 'ikon' => 'newspaper', 'svg' => 'kabar', 'aktif' => ['/program']],
        ['label' => 'Menu', 'url' => null, 'ikon' => 'menu', 'svg' => 'menu'],
    ],

    // Panel menu HP (partials/menu-sheet), pola situs BAZNAS kabupaten.
    // 'url' = tautan langsung (opsional 'aktif' = awalan path); 'dari' = ambil sub-menu & penanda aktif dari item 'utama' berlabel sama (satu sumber dengan menu desktop).
    'sheet' => [
        ['label' => 'Home', 'url' => '/'],
        // Tanpa dropdown: langsung ke halaman Profil Kami (kartu tiap item menu Tentang); aktif juga di sub-halamannya
        ['label' => 'Tentang Kami', 'url' => '/tentang', 'aktif' => ['/tentang', '/amil', '/testimoni']],
        // Tanpa dropdown: /layanan (ikon layanan + kartu Tunaikan Zakat + Kegiatan BAZNAS), /program (5 pilar + Kegiatan BAZNAS)
        ['label' => 'Layanan', 'url' => '/layanan', 'aktif' => ['/layanan']],
        ['label' => 'Program', 'url' => '/program', 'aktif' => ['/program']],
        // Tanpa dropdown: /arsip (daftar galeri + cari judul + Ayo Tunaikan Zakat)
        ['label' => 'Arsip', 'url' => '/arsip', 'aktif' => ['/arsip']],
    ],
];
