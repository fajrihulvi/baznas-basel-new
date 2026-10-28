-- 007 — Kolom tambahan alur bayar zakat (Fase 6). Aman untuk admin lama (hanya menambah/memperlebar).

ALTER TABLE pembayaran
  MODIFY nominal BIGINT UNSIGNED NOT NULL,              -- INT maks ±Rp2,1 miliar; zakat perusahaan bisa lebih
  ADD COLUMN IF NOT EXISTS jml_jiwa SMALLINT UNSIGNED NULL AFTER jml_hari,   -- zakat fitrah
  ADD INDEX IF NOT EXISTS idx_pembayaran_status (status, waktu);

ALTER TABLE konfirmasi_pembayaran
  ADD COLUMN IF NOT EXISTS nominal_transfer BIGINT UNSIGNED NULL AFTER tgl_transfer,
  ADD COLUMN IF NOT EXISTS dibuat DATETIME NULL;
