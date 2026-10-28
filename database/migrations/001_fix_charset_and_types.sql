-- 001 — Seragamkan charset & perbaiki tipe kolom tabel lama.
-- Dicek Fase 2: tabel latin1/utf8mb3 tidak berisi byte non-ASCII, jadi CONVERT aman (tidak ada mojibake).
-- Semua perintah aman diulang.

ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE artikel CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE banner CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE berita CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE berkah_zakat CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE deskripsi_zakat CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE event CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE icon CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE jenis_zakat CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE kategori_perundangan CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE kategori_program CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE kategori_zakat CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE konfig CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE konfirmasi_pembayaran CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE laporan_keuangan CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE level CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE log CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE menu CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE mitra CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE mustahik CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE pembayaran CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE pengambilan_beras CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE perundangan CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE program CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE rekening CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sisa_beras CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE sub_program CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE user CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE video CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Bug: card_number INT overflow (semua tercatat 2147483647). Nilai lama tetap rusak; yang baru tersimpan benar.
ALTER TABLE pengambilan_beras MODIFY card_number VARCHAR(20) NOT NULL;

-- Banner: link tujuan, gambar khusus mobile, urutan
ALTER TABLE banner
  ADD COLUMN IF NOT EXISTS link VARCHAR(255) NULL AFTER foto,
  ADD COLUMN IF NOT EXISTS foto_mobile VARCHAR(255) NULL AFTER foto,
  ADD COLUMN IF NOT EXISTS urutan SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  ADD INDEX IF NOT EXISTS idx_banner_pub (publish, urutan);

-- Kategori program → 5 pilar "Basel Peduli/Cerdas/Sehat/Taqwa/Makmur" (data diisi di 004)
ALTER TABLE kategori_program
  ADD COLUMN IF NOT EXISTS slug VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS label VARCHAR(100) NULL,
  ADD COLUMN IF NOT EXISTS deskripsi VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS ikon VARCHAR(255) NULL,
  ADD COLUMN IF NOT EXISTS urutan TINYINT UNSIGNED NOT NULL DEFAULT 0,
  ADD UNIQUE KEY IF NOT EXISTS uq_kategori_program_slug (slug);

-- Pembayaran: kode lebih panjang & unik (dicek: tidak ada kode ganda), relasi ke konfirmasi
ALTER TABLE pembayaran
  MODIFY kode_pembayaran VARCHAR(12) NOT NULL,
  ADD UNIQUE KEY IF NOT EXISTS uq_kode (kode_pembayaran);

ALTER TABLE konfirmasi_pembayaran
  MODIFY kode_pembayaran VARCHAR(12) NOT NULL,
  ADD COLUMN IF NOT EXISTS id_pembayaran INT NULL AFTER id_konfirmasi_pembayaran,
  ADD COLUMN IF NOT EXISTS status ENUM('baru','diverifikasi','ditolak') NOT NULL DEFAULT 'baru',
  ADD INDEX IF NOT EXISTS idx_konfirmasi_pembayaran (id_pembayaran);

-- Hubungkan konfirmasi lama ke pembayaran lewat kode; yang pembayarannya sudah SELESAI dianggap diverifikasi
UPDATE konfirmasi_pembayaran k
  JOIN pembayaran p ON p.kode_pembayaran = k.kode_pembayaran
   SET k.id_pembayaran = p.id_pembayaran,
       k.status = IF(p.status = '1', 'diverifikasi', k.status)
 WHERE k.id_pembayaran IS NULL;
