#!/usr/bin/env bash
# Deploy update website di VPS (sebagai `deploy`), setelah PR di-merge ke main:
#   pull → backup database → build image → migrasi → container baru → cek lewat Nginx edge.
#
#   scripts/deploy-website.sh           # hanya jalan bila ada commit baru
#   scripts/deploy-website.sh --force   # build & deploy ulang walau tidak ada commit baru
#
# Deploy PERTAMA (buat .env, APP_KEY, akun admin) tidak lewat skrip ini — apps/website/README.md § Deploy.
# Build di VPS lambat (disk lambat untuk fsync); layer yang tidak berubah diambil dari cache.
set -euo pipefail

REPO=${REPO:-/opt/csirt}
APP_DIR=$REPO/apps/website
SITE=${SITE:-csirt.pcr.ac.id}
EDGE_CHECK=${EDGE_CHECK:-1} # 0 = lewati cek lewat Nginx edge (mis. uji di luar VPS)

log() { printf '[%s] %s\n' "$(date -u '+%F %T UTC')" "$*"; }
fail() { log "GAGAL: $*"; exit 1; }

main() {
    local force=false before after
    case "${1:-}" in
        "") ;;
        --force) force=true ;;
        *) echo "Pemakaian: $0 [--force]" >&2; exit 2 ;;
    esac

    [ -f "$APP_DIR/.env" ] || fail "$APP_DIR/.env belum ada — deploy pertama ikuti apps/website/README.md § Deploy"

    cd "$REPO"
    before=$(git rev-parse HEAD)
    git pull --ff-only --prune -q
    after=$(git rev-parse HEAD)
    log "commit ${before:0:7} → ${after:0:7}"
    git log --oneline "$before..$after" -- apps/website | sed 's/^/  /'
    if [ "$before" = "$after" ] && ! $force; then
        log "tidak ada commit baru — tidak ada yang di-deploy (--force untuk build ulang)"
        exit 0
    fi
    if [ "$before" != "$after" ] && git diff --quiet "$before" "$after" -- apps/website && ! $force; then
        log "commit baru tidak menyentuh apps/website — tidak perlu deploy ulang (--force untuk memaksa)"
        exit 0
    fi

    cd "$APP_DIR"
    if docker compose exec -T website true 2>/dev/null; then
        log "backup database sebelum migrasi"
        "$REPO/scripts/backup-website.sh" --db-only
    else
        log "PERINGATAN: container website tidak berjalan — backup database dilewati"
    fi

    log "build image (bisa lama di VPS ini)"
    local start=$SECONDS
    docker compose build --progress=quiet
    log "build selesai dalam $((SECONDS - start)) detik"

    # Migrasi dengan image baru di container sekali-jalan; container lama masih melayani
    # pengunjung sampai `up -d` di bawah. Aman untuk migrasi yang hanya menambah tabel/kolom.
    log "migrasi database"
    docker compose run --rm --no-deps website php artisan migrate --force

    log "ganti container"
    docker compose up -d website
    wait_healthy

    if [ "$EDGE_CHECK" = 1 ]; then
        check_edge
    fi
    log "SELESAI — ${after:0:7} live. Rollback: git -C $REPO checkout ${before:0:7} && (cd $APP_DIR && docker compose up -d --build);"
    log "  database sebelum migrasi ada di ~/backups/website/db/ (scripts/restore-website.sh db <file>)"
}

wait_healthy() {
    local id status
    id=$(docker compose ps -q website)
    for _ in $(seq 1 100); do
        status=$(docker inspect -f '{{.State.Health.Status}}' "$id")
        [ "$status" = healthy ] && { log "container healthy"; return 0; }
        [ "$status" = unhealthy ] && break
        sleep 3
    done
    docker compose logs --tail 40 website
    fail "container tidak healthy ($status)"
}

check_edge() {
    local path code
    for path in /up / /berita /admin/login; do
        code=$(curl -s -o /dev/null -w '%{http_code}' --resolve "$SITE:443:127.0.0.1" "https://$SITE$path")
        printf '  https://%s%-14s %s\n' "$SITE" "$path" "$code"
        [ "$code" = 200 ] || fail "https://$SITE$path membalas $code"
    done
}

main "$@"
