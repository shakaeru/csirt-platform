#!/usr/bin/env bash
# Perpanjang sertifikat Let's Encrypt (certbot hanya memperpanjang yang mendekati
# kedaluwarsa), lalu uji config dan reload Nginx. Reload bersifat graceful — koneksi
# yang sedang berjalan tidak diputus; kalau `nginx -t` gagal, config lama tetap dipakai.
# Dijalankan cron user deploy 2x sehari — lihat docs/ARCHITECTURE.md § 5.
set -euo pipefail
cd "$(dirname "$(readlink -f "$0")")/../infra"

echo "== $(date -Is) renew =="
docker compose run --rm -T certbot renew --quiet </dev/null
docker compose exec -T nginx nginx -t
docker compose exec -T nginx nginx -s reload
echo "== selesai =="
