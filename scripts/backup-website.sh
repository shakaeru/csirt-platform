#!/usr/bin/env bash
# Backup website (csirt.pcr.ac.id): database SQLite + file upload (volume `storage`).
# Dijalankan sebagai `deploy`: cron harian, dan otomatis oleh deploy-website.sh sebelum migrasi.
#
#   scripts/backup-website.sh             # database + upload
#   scripts/backup-website.sh --db-only   # database saja
#
# Hasil di $BACKUP_DIR (default ~/backups/website, mode 700):
#   db/website-<UTC>.sqlite.gz        salinan konsisten (VACUUM INTO) — aman walau situs sedang dipakai
#   storage/website-storage-<UTC>.tar isi storage/app/public; hanya dibuat bila ada perubahan
# Retensi: database $DB_KEEP_DAYS hari, upload $STORAGE_KEEP salinan terakhir.
# Backup ini masih di disk VPS yang sama — salin ke luar VPS berkala (apps/website/README.md § Backup).
# Pemulihan: scripts/restore-website.sh.
set -euo pipefail

APP_DIR=${APP_DIR:-/opt/csirt/apps/website}
BACKUP_DIR=${BACKUP_DIR:-$HOME/backups/website}
DB_KEEP_DAYS=${DB_KEEP_DAYS:-30}
STORAGE_KEEP=${STORAGE_KEEP:-7}
DB_PATH=/var/www/html/database/sqlite/database.sqlite # di dalam container (DB_DATABASE di .env)

log() { printf '[%s] %s\n' "$(date -u '+%F %T UTC')" "$*"; }
fail() { log "GAGAL: $*"; exit 1; }

main() {
    local db_only=false
    case "${1:-}" in
        "") ;;
        --db-only) db_only=true ;;
        *) echo "Pemakaian: $0 [--db-only]" >&2; exit 2 ;;
    esac

    umask 077
    mkdir -p "$BACKUP_DIR/db" "$BACKUP_DIR/storage"
    exec 9>"$BACKUP_DIR/.lock"
    flock -n 9 || fail "backup lain sedang berjalan"
    trap 'rm -f "$BACKUP_DIR"/db/*.part "$BACKUP_DIR"/storage/*.part' EXIT

    cd "$APP_DIR"
    docker compose exec -T website true 2>/dev/null || fail "container website tidak berjalan"

    backup_db
    if ! $db_only; then
        backup_storage
    fi
}

backup_db() {
    local ts tmp check out
    ts=$(date -u +%Y%m%d-%H%M%S)
    tmp=/tmp/backup-$ts.sqlite

    # VACUUM INTO menulis salinan utuh dan konsisten (termasuk isi WAL) tanpa mengunci pembaca.
    docker compose exec -T website php -r '
        $pdo = new PDO("sqlite:" . $argv[1]);
        $pdo->exec("VACUUM INTO " . $pdo->quote($argv[2]));' "$DB_PATH" "$tmp"

    check=$(docker compose exec -T website php -r '
        echo (new PDO("sqlite:" . $argv[1]))->query("PRAGMA integrity_check")->fetchColumn();' "$tmp")
    if [ "$check" != ok ]; then
        docker compose exec -T website rm -f "$tmp"
        fail "integrity_check salinan database: $check"
    fi

    out=$BACKUP_DIR/db/website-$ts.sqlite.gz
    docker compose exec -T website sh -c 'cat "$1" && rm -f "$1"' sh "$tmp" | gzip -c > "$out.part"
    gzip -t "$out.part"
    mv "$out.part" "$out"
    log "database: $(basename "$out") ($(du -h "$out" | cut -f1))"

    find "$BACKUP_DIR/db" -name 'website-*.sqlite.gz' -mtime +"$DB_KEEP_DAYS" -print -delete |
        sed 's|^|  dihapus (retensi): |'
}

backup_storage() {
    local ts manifest last out
    ts=$(date -u +%Y%m%d-%H%M%S)

    # Sidik jari isi upload (nama, ukuran, waktu ubah). Sama dengan backup terakhir → tidak disalin ulang.
    manifest=$(docker compose exec -T website sh -c \
        'cd /var/www/html/storage/app && find public -type f -printf "%P %s %T@\n" | sort | sha256sum' | cut -d' ' -f1)
    last=$(cat "$BACKUP_DIR/storage/.last-manifest" 2>/dev/null || true)
    if [ "$manifest" = "$last" ] && compgen -G "$BACKUP_DIR/storage/website-storage-*.tar" > /dev/null; then
        log "upload: tidak berubah sejak backup terakhir — dilewati"
        return
    fi

    # Tanpa kompresi: isinya hampir semua JPEG yang sudah terkompresi.
    out=$BACKUP_DIR/storage/website-storage-$ts.tar
    docker compose exec -T website tar -C /var/www/html/storage/app -cf - public > "$out.part"
    tar -tf "$out.part" > /dev/null
    mv "$out.part" "$out"
    echo "$manifest" > "$BACKUP_DIR/storage/.last-manifest"
    log "upload: $(basename "$out") ($(du -h "$out" | cut -f1), $(tar -tf "$out" | grep -vc '/$') file)"

    find "$BACKUP_DIR/storage" -name 'website-storage-*.tar' -printf '%T@ %p\n' | sort -rn |
        tail -n +"$((STORAGE_KEEP + 1))" | cut -d' ' -f2- |
        while read -r old; do rm -f "$old" && echo "  dihapus (retensi): $old"; done
}

# Semua logika di dalam fungsi: bash sudah mem-parse seluruh file sebelum mulai berjalan,
# jadi `git pull` yang mengubah file ini di tengah jalan tidak mengacaukan eksekusi.
main "$@"
