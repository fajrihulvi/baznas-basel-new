#!/bin/sh
# Paket rilis untuk diunggah ke cPanel (lihat docs/fase-8/DEPLOY.md).
#   ./build-rilis.sh            → rilis/baznas-basel-YYYYMMDD-HHMM.zip (kode saja)
#   ./build-rilis.sh --uploads  → + rilis/uploads-YYYYMMDD-HHMM.zip (isi public/uploads)
# Tidak pernah memuat .env, dump .sql, storage/bukti, log, atau cache.
set -eu
cd "$(dirname "$0")"

WAKTU=$(date +%Y%m%d-%H%M)
NAMA="baznas-basel-$WAKTU"
TMP="rilis/.tmp/baznas-basel"

./build-css.sh
composer dump-autoload --optimize --no-dev --quiet

rm -rf rilis/.tmp
mkdir -p "$TMP"
rsync -a \
    --include='/.htaccess' --include='/.env.production.example' --include='/composer.json' \
    --include='/app/***' --include='/config/***' --include='/routes/***' --include='/vendor/***' \
    --include='/database/' --include='/database/migrations/***' --include='/database/scripts/***' \
    --include='/public/' --include='/public/uploads/' --include='/public/uploads/.htaccess' --include='/public/uploads/.gitkeep' \
    --exclude='/public/uploads/*' --include='/public/***' \
    --include='/docs/' --include='/docs/fase-8/***' \
    --exclude='.DS_Store' --exclude='*' \
    ./ "$TMP/"

# Folder runtime kosong (PHP harus bisa menulis ke sini)
for d in storage/cache storage/logs storage/bukti; do
    mkdir -p "$TMP/$d"
    touch "$TMP/$d/.gitkeep"
done

# Pengaman: tidak boleh ada rahasia / data pribadi di paket
if find "$TMP" \( -name '.env' -o -name '*.sql' -o -name '*.sql.gz' -o -name '*.log' \) | grep -v 'database/migrations/' | grep -q .; then
    echo "BATAL: paket berisi .env / .sql / .log" >&2
    exit 1
fi

# Manifest integritas dibuat dari isi paket (lihat database/scripts/cek_integritas.php)
php "$TMP/database/scripts/cek_integritas.php" --buat

(cd rilis/.tmp && zip -qr -9 "../$NAMA.zip" baznas-basel)
cp "$TMP/integritas.json" "rilis/$NAMA.integritas.json"
rm -rf rilis/.tmp
echo "Paket: rilis/$NAMA.zip ($(du -h "rilis/$NAMA.zip" | cut -f1))"

if [ "${1:-}" = "--uploads" ]; then
    (cd public && zip -qr "../rilis/uploads-$WAKTU.zip" uploads -x 'uploads/.htaccess' -x 'uploads/.gitkeep' -x '*.DS_Store')
    echo "Upload: rilis/uploads-$WAKTU.zip ($(du -h "rilis/uploads-$WAKTU.zip" | cut -f1))"
fi
