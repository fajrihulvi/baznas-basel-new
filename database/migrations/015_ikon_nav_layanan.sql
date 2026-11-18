-- 015 — Bilah navigasi bawah: slot ikon 'program' kini menu Layanan (/layanan), slot ikon 'kabar' kini menu Program.
-- Ikon tetap di slotnya; hanya label yang ikut berubah. Label yang sudah diubah admin tidak disentuh.

UPDATE ikon_nav SET label = 'Layanan' WHERE kunci = 'program' AND label = 'Program';
UPDATE ikon_nav SET label = 'Program' WHERE kunci = 'kabar' AND label = 'Kabar';
