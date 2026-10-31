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
        ['label' => 'Layanan', 'aktif' => ['/zakat', '/kalkulator-zakat', '/bayar-zakat', '/konfirmasi', '/rekening'], 'anak' => [
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
    'bawah' => [
        ['label' => 'Beranda', 'url' => '/', 'ikon' => 'home', 'svg' => 'beranda', 'aktif' => ['/'], 'persis' => true],
        ['label' => 'Program', 'url' => '/program', 'ikon' => 'grid', 'svg' => 'program', 'aktif' => ['/program']],
        ['label' => 'Bayar Zakat', 'url' => '/bayar-zakat', 'ikon' => 'heart', 'svg' => 'bayar-zakat', 'aktif' => ['/bayar-zakat'], 'tengah' => true],
        ['label' => 'Kabar', 'url' => '/berita', 'ikon' => 'newspaper', 'svg' => 'kabar', 'aktif' => ['/berita']],
        ['label' => 'Menu', 'url' => null, 'ikon' => 'menu', 'svg' => 'menu'],
    ],

    // Isi bottom sheet "Menu"
    'sheet' => [
        ['judul' => 'Layanan Zakat', 'item' => [
            ['label' => 'Kalkulator', 'url' => '/kalkulator-zakat', 'ikon' => 'calculator'],
            ['label' => 'Konfirmasi', 'url' => '/konfirmasi', 'ikon' => 'upload'],
            ['label' => 'Rekening', 'url' => '/rekening', 'ikon' => 'bank'],
            ['label' => 'Penjelasan Zakat', 'url' => '/zakat', 'ikon' => 'book'],
        ]],
        ['judul' => 'Informasi', 'item' => [
            ['label' => 'Regulasi', 'url' => '/regulasi', 'ikon' => 'scale'],
            ['label' => 'Laporan', 'url' => '/laporan', 'ikon' => 'chart'],
            ['label' => 'Video', 'url' => '/video', 'ikon' => 'video'],
            ['label' => 'Galeri', 'url' => '/galeri', 'ikon' => 'image'],
        ]],
        ['judul' => 'Tentang Kami', 'item' => [
            ['dinamis' => 'halaman:tentang', 'ikon' => 'info'],
            ['label' => 'Amil', 'url' => '/amil', 'ikon' => 'users'],
            ['label' => 'Testimoni', 'url' => '/testimoni', 'ikon' => 'star'],
            ['label' => 'Kontak', 'url' => '#kontak', 'ikon' => 'phone'],
        ]],
    ],
];
