#!/usr/bin/env bash
# Runs on the cPanel server over SSH; called by .github/workflows/deploy-cpanel.yml
# and rollback-cpanel.yml. One directory tree under $DEPLOY_PATH:
#
#   backend/upload/          rsync target, updated incrementally on each deploy
#   backend/releases/<id>/   hard-link copy of upload, one per deploy
#   backend/shared/.env      real configuration (created by hand, never in git)
#   backend/shared/storage/  uploads, logs, sessions: survives every release
#   backend/shared/public/   public/uploads, profile_images, media (the app writes
#                            user uploads straight into public/): survives releases
#   backend/shared/backups/  database dumps taken before production migrations
#   backend/current       -> releases/<id>   (document root: current/public)
#   web/upload/  web/releases/<id>/  web/current  (document root: current)
#
# Usage:
#   remote.sh init
#   remote.sh backend <release-id> [--backup-db]
#   remote.sh web <release-id>
#   remote.sh rollback <backend|web>
#   remote.sh status
#
# Environment: DEPLOY_PATH (required), PHP_BIN (default: php),
#              KEEP_RELEASES (default: 5), KEEP_BACKUPS (default: 10)

set -Eeuo pipefail
export LC_ALL=C # stable glob/sort order for release ids

: "${DEPLOY_PATH:?DEPLOY_PATH is required}"
PHP_BIN="${PHP_BIN:-php}"
KEEP_RELEASES="${KEEP_RELEASES:-5}"
KEEP_BACKUPS="${KEEP_BACKUPS:-10}"

# Folders under backend/public/ that the app writes uploads into (image->move(public_path(...)),
# the "media" disk). They are linked to shared/public/ so uploads survive each release.
SHARED_PUBLIC_DIRS=(uploads profile_images media)

BACKEND="$DEPLOY_PATH/backend"
WEB="$DEPLOY_PATH/web"

log() { printf '==> %s\n' "$*"; }
die() { printf 'ERROR: %s\n' "$*" >&2; exit 1; }

valid_release_id() {
  [[ "$1" =~ ^[0-9]{8}-[0-9]{6}-[0-9a-f]{7,40}$ ]] || die "invalid release id: $1"
}

# Point <app>/current at releases/<id> atomically (rename over the old link).
switch_current() {
  # Creating a subdomain in cPanel with document root .../current/public makes
  # `current` a real (empty) directory; replace it, but never one holding files.
  if [[ -d "$1/current" && ! -L "$1/current" ]]; then
    [[ -z "$(find "$1/current" -type f -print -quit)" ]] \
      || die "$1/current is a real directory containing files; move it away first (docs/deploy.md)"
    rm -rf "${1:?}/current"
  fi
  ln -sfn "releases/$2" "$1/current.next"
  mv -Tf "$1/current.next" "$1/current"
  log "$(basename "$1"): current -> releases/$2"
}

# Release ids start with a UTC timestamp, so name order is deploy order.
release_ids() {
  local dir
  for dir in "$1"/releases/*/; do
    [[ -d "$dir" ]] && basename "$dir"
  done
  return 0
}

prune_releases() {
  local app="$1" current
  current="$(readlink "$app/current" 2>/dev/null || true)"
  release_ids "$app" | head -n -"$KEEP_RELEASES" | while read -r old; do
    [[ "releases/$old" == "$current" ]] && continue
    # Never fail a deploy that is already live over cleanup: files created by another
    # user (e.g. .well-known from a certificate request by root) cannot be removed.
    if rm -rf "${app:?}/releases/${old:?}" 2>/dev/null; then
      log "$(basename "$app"): removed old release $old"
    else
      # Move what is left out of releases/ so a rollback can never land on it.
      mkdir -p "$app/trash"
      mv "$app/releases/$old" "$app/trash/$old-$(date +%s)" 2>/dev/null || true
      log "WARNING: $(basename "$app"): old release $old has files owned by another user; moved to $(basename "$app")/trash, delete it as root"
    fi
  done
}

# Copy the uploaded build into a new release. Hard links make this instant and
# cost no extra disk; rsync replaces changed files with new inodes (no --inplace),
# so later uploads never modify files inside an existing release.
new_release() {
  local app="$1" id="$2"
  [[ -d "$app/upload" ]] || die "$app/upload missing: run 'remote.sh init' first"
  [[ ! -e "$app/releases/$id" ]] || die "release $id already exists in $app"
  cp -al "$app/upload" "$app/releases/$id"
}

env_value() { # <key>: read a value from shared/.env, without surrounding quotes
  local v
  v="$(grep -E "^$1=" "$BACKEND/shared/.env" | tail -n1 | cut -d= -f2-)"
  v="${v%$'\r'}"; v="${v#\"}"; v="${v%\"}"; v="${v#\'}"; v="${v%\'}"
  printf '%s' "$v"
}

backup_database() {
  local id="$1" file="$BACKEND/shared/backups/db-$1.sql.gz"
  command -v mysqldump >/dev/null || die "mysqldump not found; cannot back up before migrating"
  log "backing up database to shared/backups/db-$id.sql.gz"
  MYSQL_PWD="$(env_value DB_PASSWORD)" mysqldump \
    --single-transaction --quick --no-tablespaces --routines \
    -h "$(env_value DB_HOST)" -P "$(env_value DB_PORT)" -u "$(env_value DB_USERNAME)" \
    "$(env_value DB_DATABASE)" | gzip > "$file.tmp"
  mv "$file.tmp" "$file"
  for old in "$BACKEND"/shared/backups/db-*.sql.gz; do
    [[ -f "$old" ]] && printf '%s\n' "$old"
  done | head -n -"$KEEP_BACKUPS" | while read -r old; do
    rm -f "$old"
  done
}

cmd_init() {
  mkdir -p "$BACKEND"/{upload,releases} \
           "$BACKEND"/shared/storage/{app/public,framework/{cache/data,sessions,views,testing},logs} \
           "$BACKEND"/shared/backups \
           "$BACKEND"/shared/public \
           "$WEB"/{upload,releases}
  [[ -f "$BACKEND/shared/.env" ]] || log "NOTE: create $BACKEND/shared/.env before the first backend deploy"
  log "layout ready under $DEPLOY_PATH"
}

cmd_backend() {
  local id="$1" backup="${2:-}" release
  valid_release_id "$id"
  [[ -f "$BACKEND/shared/.env" ]] || die "$BACKEND/shared/.env is missing (see docs/deploy.md)"
  release="$BACKEND/releases/$id"

  log "backend: preparing release $id"
  new_release "$BACKEND" "$id"
  # A release that fails before going live is deleted, so rollback can never pick it.
  trap 'rm -rf "$release"; log "backend: release $id removed after failure"' ERR
  echo "$id" > "$release/RELEASE"
  ln -sfn ../../shared/.env "$release/.env"
  # Seed files committed under storage/ (notably storage/installed, without which the
  # Installer module redirects every page to /install). Never overwrite shared files.
  [[ -d "$release/storage" ]] && cp -an "$release/storage/." "$BACKEND/shared/storage/"
  rm -rf "$release/storage"
  ln -sfn ../../shared/storage "$release/storage"
  ln -sfn ../storage/app/public "$release/public/storage"
  local dir
  for dir in "${SHARED_PUBLIC_DIRS[@]}"; do
    mkdir -p "$BACKEND/shared/public/$dir"
    if [[ -d "$release/public/$dir" && ! -L "$release/public/$dir" ]]; then
      # Seed files committed in git (e.g. uploads/ads) without overwriting live uploads.
      cp -an "$release/public/$dir/." "$BACKEND/shared/public/$dir/"
      rm -rf "${release:?}/public/${dir:?}"
    fi
    ln -sfn "../../../shared/public/$dir" "$release/public/$dir"
  done
  mkdir -p "$release/bootstrap/cache"

  cd "$release"
  "$PHP_BIN" artisan package:discover --ansi
  [[ "$backup" == "--backup-db" ]] && backup_database "$id"
  log "backend: running migrations"
  "$PHP_BIN" artisan migrate --force
  # Code reads .env only through config/, so the config can be cached per release.
  # route:cache fails on a duplicated route name, which aborts the release before it
  # goes live. cache:clear is not used: it would drop OTP codes and rate limits
  # stored in the cache.
  "$PHP_BIN" artisan config:cache
  "$PHP_BIN" artisan event:cache
  "$PHP_BIN" artisan route:cache
  "$PHP_BIN" artisan view:clear

  switch_current "$BACKEND" "$id"
  trap - ERR
  "$PHP_BIN" artisan queue:restart || true
  prune_releases "$BACKEND"
}

cmd_web() {
  local id="$1"
  valid_release_id "$id"
  log "web: preparing release $id"
  new_release "$WEB" "$id"
  switch_current "$WEB" "$id"
  prune_releases "$WEB"
}

# Switch back to the release before the current one and delete the abandoned
# release, so a later rollback never lands on it again (redeploy to roll forward).
# Database migrations are not reverted: restore shared/backups/db-<id>.sql.gz by
# hand if a migration broke data.
cmd_rollback() {
  local app current previous
  case "${1:-}" in
    backend) app="$BACKEND" ;;
    web) app="$WEB" ;;
    *) die "usage: remote.sh rollback <backend|web>" ;;
  esac
  current="$(basename "$(readlink "$app/current" 2>/dev/null)" 2>/dev/null || true)"
  [[ -n "$current" ]] || die "$1 has no current release"
  previous="$(release_ids "$app" | grep -B1 -Fx "$current" | head -n1)"
  [[ -n "$previous" && "$previous" != "$current" ]] || die "$1: no release before $current"
  switch_current "$app" "$previous"
  rm -rf "${app:?}/releases/${current:?}"
  log "$1: removed abandoned release $current"
  if [[ "$1" == backend ]]; then
    # Releases from before route:cache was enabled have duplicate route names, so fall back to uncached routes.
    (cd "$app/current" && "$PHP_BIN" artisan config:cache && "$PHP_BIN" artisan event:cache && { "$PHP_BIN" artisan route:cache || "$PHP_BIN" artisan route:clear; } && "$PHP_BIN" artisan view:clear)
  fi
}

cmd_status() {
  local app
  for app in "$BACKEND" "$WEB"; do
    printf '%s: current=%s releases=[%s]\n' "$(basename "$app")" \
      "$(basename "$(readlink "$app/current" 2>/dev/null || echo none)")" \
      "$(release_ids "$app" | tr '\n' ' ' | sed 's/ $//')"
  done
}

case "${1:-}" in
  init) cmd_init ;;
  backend) cmd_backend "${2:?release id required}" "${3:-}" ;;
  web) cmd_web "${2:?release id required}" ;;
  rollback) cmd_rollback "${2:-}" ;;
  status) cmd_status ;;
  *) die "usage: remote.sh init | backend <id> [--backup-db] | web <id> | rollback <backend|web> | status" ;;
esac
