#!/usr/bin/env bash
# Exercises deploy/remote.sh against a throwaway directory with stub `php` and
# `mysqldump` binaries. Runs in CI (.github/workflows/deploy-script.yml) and
# locally: bash deploy/tests/remote.test.sh
set -uo pipefail

SCRIPT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/remote.sh"
T="$(mktemp -d)"
trap 'rm -rf "$T"' EXIT
export DEPLOY_PATH="$T/staging" KEEP_RELEASES=3 KEEP_BACKUPS=2 PHP_BIN=php

mkdir -p "$T/bin"
cat > "$T/bin/php" <<'STUB'
#!/usr/bin/env bash
echo "php $*" >> "$DEPLOY_PATH/../php.log"
[[ "${FAIL_MIGRATE:-}" == 1 && "$*" == *migrate* ]] && exit 1
exit 0
STUB
cat > "$T/bin/mysqldump" <<'STUB'
#!/usr/bin/env bash
echo "dump args=$* pwd=$MYSQL_PWD" >&2
echo "-- dump"
STUB
chmod +x "$T/bin/"*
export PATH="$T/bin:$PATH"

FAILS=0
ok() { echo "  ✓ $*"; }
bad() { echo "  ✗ $*"; FAILS=$((FAILS + 1)); }
deploy() { bash "$SCRIPT" "$@"; }
B="$DEPLOY_PATH/backend"
C="$B/current"

# Mimic rsync: write a new file and rename it (new inode), never edit in place.
upload_backend() {
  mkdir -p "$B/upload/public/uploads/ads" "$B/upload/storage" "$B/upload/bootstrap"
  echo "installed" > "$B/upload/storage/installed"
  echo "from-git" > "$B/upload/public/uploads/ads/.git-ad.tmp" && mv -f "$B/upload/public/uploads/ads/.git-ad.tmp" "$B/upload/public/uploads/ads/git-ad.jpg"
  echo "v$1" > "$B/upload/.app.tmp" && mv -f "$B/upload/.app.tmp" "$B/upload/app.txt"
}
upload_web() {
  echo "$1" > "$DEPLOY_PATH/web/upload/.tmp" && mv -f "$DEPLOY_PATH/web/upload/.tmp" "$DEPLOY_PATH/web/upload/index.html"
}

echo "init"
deploy init > /dev/null && [[ -d "$B/shared/storage/framework/views" && -d "$DEPLOY_PATH/web/upload" ]] && ok "layout created" || bad "layout"

echo "backend deploy"
mkdir -p "$C/public"   # what cPanel leaves behind when the subdomain is created first
upload_backend 1
deploy backend 20261019-100000-aaaaaaa > /dev/null 2>&1 && bad "deployed without shared/.env" || ok "refuses to deploy without shared/.env"
printf 'DB_HOST=127.0.0.1\nDB_PORT=3306\nDB_DATABASE=sami_db\nDB_USERNAME=sami\nDB_PASSWORD="p@ss w0rd"\r\n' > "$B/shared/.env"
deploy backend 20261019-100000-aaaaaaa > /dev/null && ok "first deploy" || bad "first deploy"
[[ "$(readlink "$C")" == releases/20261019-100000-aaaaaaa ]] && ok "current is a relative link (replaced cPanel's empty folder)" || bad "current=$(readlink "$C")"
[[ "$(cat "$C/RELEASE")" == 20261019-100000-aaaaaaa ]] && ok "RELEASE file written" || bad "RELEASE"
[[ "$(readlink -f "$C/.env")" == "$(readlink -f "$B/shared/.env")" ]] && ok ".env -> shared/.env" || bad ".env link"
[[ "$(readlink -f "$C/storage")" == "$(readlink -f "$B/shared/storage")" ]] && ok "storage -> shared/storage" || bad "storage link"
[[ "$(readlink -f "$C/public/storage")" == "$(readlink -f "$B/shared/storage/app/public")" ]] && ok "public/storage resolves to shared uploads" || bad "public/storage"
grep -q "migrate --force" "$T/php.log" && grep -q "config:cache" "$T/php.log" && grep -q "route:cache" "$T/php.log" && ! grep -q "cache:clear" "$T/php.log" \
  && ok "migrates, caches config and routes; never cache:clear" || bad "artisan calls: $(tr '\n' ';' < "$T/php.log")"

[[ -f "$C/storage/installed" ]] && ok "storage/installed seeded into shared storage" || bad "storage/installed missing (app would redirect to /install)"
[[ -L "$C/public/uploads" && -f "$C/public/uploads/ads/git-ad.jpg" ]] && ok "public/uploads -> shared, seeded with files from git" || bad "public/uploads"
[[ -L "$C/public/profile_images" && -L "$C/public/media" ]] && ok "profile_images and media -> shared" || bad "profile_images/media"
echo "user-upload" > "$C/public/uploads/ads/user.jpg"          # app saves an upload
echo "edited-live" > "$B/shared/public/uploads/ads/git-ad.jpg"  # admin replaced a seeded image

echo "second deploy with database backup"
upload_backend 2
deploy backend 20261019-110000-bbbbbbb --backup-db > /dev/null 2> "$T/err" && ok "deployed" || { bad "deploy"; cat "$T/err"; }
[[ "$(cat "$C/public/uploads/ads/user.jpg" 2>/dev/null)" == user-upload ]] && ok "user upload survives the next release" || bad "user upload lost"
[[ "$(cat "$C/public/uploads/ads/git-ad.jpg")" == edited-live ]] && ok "live file never overwritten by git copy" || bad "live file overwritten"
[[ "$(cat "$B/releases/20261019-100000-aaaaaaa/app.txt")" == v1 ]] && ok "previous release untouched by new upload" || bad "previous release changed"
grep -q "pwd=p@ss w0rd" "$T/err" && grep -q "sami_db" "$T/err" && ok "dump uses shared/.env credentials" || bad "dump credentials"
[[ -f "$B/shared/backups/db-20261019-110000-bbbbbbb.sql.gz" ]] && ok "backup file written" || bad "backup file"

echo "failed migration"
upload_backend 3
FAIL_MIGRATE=1 deploy backend 20261019-120000-ccccccc > /dev/null 2>&1 && bad "should have failed" || ok "deploy fails"
[[ "$(readlink "$C")" == releases/20261019-110000-bbbbbbb ]] && ok "live release unchanged" || bad "current moved to $(readlink "$C")"
[[ ! -e "$B/releases/20261019-120000-ccccccc" ]] && ok "failed release deleted" || bad "failed release left behind"

echo "pruning"
for h in 13 14 15; do upload_backend "$h"; deploy backend "20261019-${h}0000-ddddd$h" > /dev/null 2>&1; done
[[ "$(ls "$B/releases" | wc -l)" -eq 3 ]] && ok "keeps KEEP_RELEASES=3" || bad "releases: $(ls "$B/releases" | tr '\n' ' ')"
for h in 16 17 18; do upload_backend "$h"; deploy backend "20261019-${h}0000-eeeeee$h" --backup-db > /dev/null 2>&1; done
[[ "$(ls "$B/shared/backups" | wc -l)" -eq 2 ]] && ok "keeps KEEP_BACKUPS=2" || bad "backups: $(ls "$B/shared/backups" | tr '\n' ' ')"

echo "rollback"
deploy rollback backend > /dev/null && [[ "$(readlink "$C")" == releases/20261019-170000-eeeeee17 ]] && ok "back to previous release" || bad "rollback: $(readlink "$C")"
[[ ! -e "$B/releases/20261019-180000-eeeeee18" ]] && ok "abandoned release deleted" || bad "abandoned release kept"
deploy rollback backend > /dev/null && [[ "$(readlink "$C")" == releases/20261019-160000-eeeeee16 ]] && ok "second rollback goes further back" || bad "second rollback: $(readlink "$C")"
deploy rollback backend > /dev/null 2>&1 && bad "rolled back past the oldest release" || ok "stops at the oldest release"

echo "web"
upload_web w1; deploy web 20261019-100000-aaaaaaa > /dev/null
upload_web w2; deploy web 20261019-110000-bbbbbbb > /dev/null
[[ "$(cat "$DEPLOY_PATH/web/current/index.html")" == w2 ]] && ok "serves latest release" || bad "web current"
deploy rollback web > /dev/null && [[ "$(cat "$DEPLOY_PATH/web/current/index.html")" == w1 ]] && ok "rollback" || bad "web rollback"

echo "document root holding real files"
mkdir -p "$T/other/web/upload" "$T/other/web/releases" "$T/other/web/current" && echo live > "$T/other/web/current/index.html"
DEPLOY_PATH="$T/other" deploy web 20261019-100000-aaaaaaa > /dev/null 2>&1 && bad "replaced a folder with files" || ok "refuses to replace a folder with files"
[[ -f "$T/other/web/current/index.html" ]] && ok "existing files untouched" || bad "files deleted"

echo "input validation"
deploy backend '../../etc' > /dev/null 2>&1 && bad "accepted a path as release id" || ok "rejects invalid release id"

echo
[[ "$FAILS" -eq 0 ]] && echo "all checks passed" || echo "$FAILS check(s) failed"
exit "$FAILS"
