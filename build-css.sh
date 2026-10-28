#!/bin/sh
# Build CSS Tailwind v4 (standalone CLI, tanpa Node.js).
#   ./build-css.sh          → build minified sekali
#   ./build-css.sh --watch  → rebuild otomatis saat file View berubah (dev)
set -eu
cd "$(dirname "$0")"

BIN=./bin/tailwindcss
IN=resources/css/app.css
OUT=public/assets/css/app.css

if [ ! -x "$BIN" ]; then
    echo "Tailwind CLI tidak ditemukan di $BIN."
    echo "Unduh: https://github.com/tailwindlabs/tailwindcss/releases (tailwindcss-macos-x64 / -arm64), simpan sebagai $BIN, lalu chmod +x."
    exit 1
fi

if [ "${1:-}" = "--watch" ]; then
    exec "$BIN" -i "$IN" -o "$OUT" --watch
fi

"$BIN" -i "$IN" -o "$OUT" --minify

RAW=$(wc -c < "$OUT" | tr -d ' ')
GZ=$(gzip -9 -c "$OUT" | wc -c | tr -d ' ')
echo "app.css: $((RAW / 1024)) KB mentah, $((GZ / 1024)) KB gzip (budget: < 30 KB gzip)"
