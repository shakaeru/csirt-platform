#!/usr/bin/env bash
# Pulihkan website dari backup scripts/backup-website.sh. Dijalankan sebagai `deploy`.
#
#   scripts/restore-website.sh db      <file website-….sqlite.gz>
#   scripts/restore-website.sh storage <file website-storage-….tar>
#
# db:      database saat ini di-backup dulu, container dihentikan (pengunjung melihat
#          "Segera Hadir"), file diganti, lalu container dinyalakan lagi.
# storage: isi storage/app/public diganti isi arsip (container tetap jalan).
# Meminta konfirmasi; tambahkan --yes untuk melewatinya (mis. latihan pemulihan terskrip).
set -euo pipefail

APP_DIR=${APP_DIR:-/opt/csirt/apps/website}
SCRIPTS_DIR=$(cd "$(dirname "$0")" && pwd)

log() { printf '[%s] %s\n' "$(date -u '+%F %T UTC')" "$*"; }
fail() { log "GAGAL: $*"; exit 1; }

main() {
    local what=${1:-} file=${2:-} yes=${3:-}
    if [ -z "$what" ] || [ -z "$file" ] || { [ -n "$yes" ] && [ "$yes" != --yes ]; }; then
        echo "Pemakaian: $0 db|storage <file backup> [--yes]" >&2
        exit 2
    fi
    [ -f "$file" ] || fail "file tidak ada: $file"
    file=$(cd "$(dirname "$file")" && pwd)/$(basename "$file")
    cd "$APP_DIR"

    case "$what" in
        db)
            gzip -t "$file" || fail "arsip gzip rusak: $file"
            confirm "Ganti database website dengan $(basename "$file")? Situs mati sebentar." "$yes"
            restore_db "$file"
            ;;
        storage)
            tar -tf "$file" > /dev/null || fail "arsip tar rusak: $file"
            tar -tf "$file" | grep -qv '^public/' && fail "arsip bukan backup storage (isi harus di bawah public/)"
            confirm "Ganti SEMUA file upload website dengan isi $(basename "$file")?" "$yes"
            restore_storage "$file"
            ;;
        *) fail "jenis pemulihan tidak dikenal: $what (db|storage)" ;;
    esac
}

confirm() {
    [ "$2" = --yes ] && return
    local answer
    read -r -p "$1 Ketik 'pulihkan' untuk lanjut: " answer
    [ "$answer" = pulihkan ] || fail "dibatalkan — tidak ada yang diubah"
}

wait_healthy() {
    local id status
    id=$(docker compose ps -q website)
    for _ in $(seq 1 100); do
        status=$(docker inspect -f '{{.State.Health.Status}}' "$id")
        [ "$status" = healthy ] && return 0
        [ "$status" = unhealthy ] && break
        sleep 3
    done
    fail "container tidak healthy ($status) — cek: docker compose logs --tail 50 website"
}

restore_db() {
    local file=$1
    if docker compose exec -T website true 2>/dev/null; then
        log "backup database saat ini dulu (untuk jaga-jaga)"
        "$SCRIPTS_DIR/backup-website.sh" --db-only
    fi

    log "hentikan container website"
    docker compose stop website

    # Container sekali-jalan tanpa entrypoint image (tidak menyalakan Nginx/PHP-FPM, tidak join
    # sebagai `website`). File -wal/-shm lama WAJIB dihapus: isinya milik database lama.
    log "ganti file database"
    gunzip -c "$file" | docker compose run --rm --no-deps -T --entrypoint sh website -c '
        set -e
        d=/var/www/html/database/sqlite
        cat > "$d/restore.tmp"
        check=$(php -r "echo (new PDO(\"sqlite:\" . \$argv[1]))->query(\"PRAGMA integrity_check\")->fetchColumn();" "$d/restore.tmp")
        if [ "$check" != ok ]; then rm -f "$d/restore.tmp"; echo "integrity_check: $check" >&2; exit 1; fi
        rm -f "$d/database.sqlite-wal" "$d/database.sqlite-shm"
        mv "$d/restore.tmp" "$d/database.sqlite"'

    log "nyalakan container website"
    docker compose up -d website
    wait_healthy
    log "database dipulihkan dari $(basename "$file")"
}

restore_storage() {
    local file=$1
    docker compose exec -T website true 2>/dev/null || fail "container website tidak berjalan"
    # Ekstrak ke folder sementara dulu, baru ditukar — kalau ekstrak gagal, upload lama utuh.
    docker compose exec -T website sh -c '
        set -e
        cd /var/www/html/storage/app
        rm -rf restore.tmp public.old
        mkdir restore.tmp
        tar -C restore.tmp -xf -
        mv public public.old
        mv restore.tmp/public public
        rm -rf public.old restore.tmp' < "$file"
    log "upload dipulihkan dari $(basename "$file") ($(tar -tf "$file" | grep -vc '/$') file)"
}

main "$@"
