#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

# charcoal.sh — root orchestrator (Foundation)
set -euo pipefail

if locale -a 2>/dev/null | grep -qi 'en_US\.utf-8'; then
  export LANG=en_US.UTF-8 LC_ALL=en_US.UTF-8
else
  export LANG=C.UTF-8 LC_ALL=C.UTF-8
fi

# Paths Configuration
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
#ROOT="${ROOT:-$SCRIPT_DIR/..}"
ROOT="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$ROOT/dev/.env"
DB_INIT_JSON="$ROOT/dev/db.manifest.json"
DB_INIT_OUT="$ROOT/dev/docker/utils/db/init/01-init-dbs.sql"
SEMAPHORE_DIR="$ROOT/var/shared/semaphore"
LOGS_SEMA_DIR="$SEMAPHORE_DIR/logs"
OVR_PORTS="$ROOT/dev/docker/compose.ports.yml"
MOUNTS_DEV="$ROOT/dev/docker/compose/mounts.dev.yml"
MOUNTS_PROD="$ROOT/dev/docker/compose/mounts.prod.yml"
HTTP_ENV_OVR="$ROOT/dev/docker/compose/http.env.yml"

# Colors and Styling (defines ok/info/warn/err used by helpers)
STYLING_FILE="$ROOT/dev/bin/styling.sh"
if [ -r "$STYLING_FILE" ]; then
  . "$STYLING_FILE"
else
  printf 'Missing styling file: %s\n' "$STYLING_FILE" >&2
  exit 1
fi

# Services helpers (manifest loader, overrides)
SERVICES_FILE="$ROOT/dev/bin/services.sh"
if [ -r "$SERVICES_FILE" ]; then
  . "$SERVICES_FILE"
else
  svc(){ echo "$1"; }
fi

# Load App Manifest
load_manifest

# Require Env Configuration
require_env() {
  [[ -f "$ENV_FILE" ]] || {
    err2 "Error:{/} Environment configuration file {yellow}[dev/.env]{/} not found."
    info "Charcoal Diagnostics:{/}{grey} Contact vendor for package specific environments configuration file."
    exit 1
  }
  set -a
  # shellcheck disable=SC1090
  . "$ENV_FILE"
  set +a
}

CHARCOAL_PROJECT="${CHARCOAL_PROJECT:-foundation-app}"
CHARCOAL_DOCKER="${CHARCOAL_DOCKER:-engine,web,mysql,redis}"

# Gate profiles: keep infra as requested; drop disabled SAPIs
resolve_profiles() {
  local req enabled_sapis all_sapis p EFFECT=""
  req="${CHARCOAL_DOCKER//[[:space:]]/}"

  if command -v jq >/dev/null 2>&1 && [[ -f "$MANIFEST" ]]; then
    all_sapis="$(jq -r '.charcoal.sapi[].id' "$MANIFEST" 2>/dev/null || true)"
    enabled_sapis="$(jq -r '.charcoal.sapi[] | select(.enabled!=false) | .id' "$MANIFEST" 2>/dev/null || true)"
  else
    # Fallback: we only know enabled SAPIs from services.sh
    all_sapis="${SAPI_IDS[*]}"
    enabled_sapis="${SAPI_IDS[*]}"
  fi

  IFS=',' read -ra arr <<<"$req"
  for p in "${arr[@]}"; do
    if [[ " $all_sapis " == *" $p "* ]]; then
      # it's a SAPI → include only if enabled
      [[ " $enabled_sapis " == *" $p "* ]] && EFFECT+="${EFFECT:+,}$p"
    else
      # infra (mysql/redis/pma/etc) → keep as requested
      EFFECT+="${EFFECT:+,}$p"
    fi
  done
  export EFFECTIVE="$EFFECT"
}

# `has_profile` should check EFFECTIVE (falls back to requested)
has_profile() { [[ ",${EFFECTIVE:-$CHARCOAL_DOCKER}," == *",$1,"* ]]; }

# turn COMPOSE_FILES="a.yml:b.yml" into "-f dev/docker/a.yml -f dev/docker/b.yml"
compose_flags() {
  local out=() IFS=':'; read -ra files <<< "${COMPOSE_FILES:-docker-compose.yml}"
  for f in "${files[@]}"; do out+=(-f "$COMPOSE_DIR/$f"); done
  echo "${out[@]}"
}

# turn COMPOSE_PROFILES="engine,web,db" into "--profile engine --profile web --profile db"
profile_flags() {
  local out=() IFS=','; read -ra profs <<< "${COMPOSE_PROFILES:-}"
  for p in "${profs[@]}"; do [[ -n "$p" ]] && out+=(--profile "$p"); done
  echo "${out[@]}"
}

compose() {
  # decide mounts by env (Option 2)
  local mounts="dev/docker/compose/mounts.dev.yml"
  [[ "${CHARCOAL_ENV:-dev}" == "prod" ]] && mounts="dev/docker/compose/mounts.prod.yml"

  # prefer resolve_profiles → COMPOSE_PROFILES → default
  local profiles="${EFFECTIVE:-${COMPOSE_PROFILES:-${CHARCOAL_DOCKER:-engine,web}}}"

  DOCKER_BUILDKIT=1 \
  COMPOSE_DOCKER_CLI_BUILD=1 \
  COMPOSE_IGNORE_ORPHANS=1 \
  COMPOSE_PROJECT_NAME="charcoal-$CHARCOAL_PROJECT" \
  COMPOSE_PROFILES="$profiles" \
  docker compose \
    -f "$ROOT/dev/docker/docker-compose.yml" \
    -f "$ROOT/$mounts" \
    -f "$ROOT/dev/docker/compose/manifest.overrides.yml" \
    "$@"
}

ensure_runtime_dirs() {
  umask 0027
  [[ ${#SAPI_IDS[@]} -eq 0 ]] && SAPI_IDS=(engine web)
  for id in "${SAPI_IDS[@]}"; do
    install -d -m 0750 "$ROOT/var/log/$id" "$ROOT/var/tmp/$id"
  done
  install -d -m 0750 "$ROOT/var/shared" "$ROOT/var/storage"
  install -d -m 0750 "$SEMAPHORE_DIR" "$LOGS_SEMA_DIR"
}

engine_healthy() {
  local tries=${1:-60}
  for ((i=1;i<=tries;i++)); do
    if compose ps "$(svc engine)" >/dev/null 2>&1 \
       && compose exec -T "$(svc engine)" php -v >/dev/null 2>&1; then
      return 0
    fi
    sleep 1
  done
  return 1
}

ensure_engine_up() {
  compose up -d --no-deps "$(svc engine)" >/dev/null
}

export COMPOSE_PROFILES="${COMPOSE_PROFILES:-${CHARCOAL_DOCKER:-engine,web}}"

generate_db_init_sql() {
  [[ -f "$DB_INIT_JSON" ]] || { info "No db.init.json, skipping DB bootstrap."; return 0; }
  has_profile mysql || { info "Profile 'mysql' disabled, skipping DB bootstrap."; return 0; }

  info "Generating MySQL init SQL from dev/db.manifest.json …"

  # parse schemas only
  if command -v jq >/dev/null 2>&1; then
    mapfile -t schemas < <(jq -r '.schemas[]' "$DB_INIT_JSON")
  elif command -v python3 >/dev/null 2>&1; then
    readarray -t schemas < <(python3 - <<'PY'
import json,sys
j=json.load(open(sys.argv[1]))
for s in j.get("schemas",[]): print(s)
PY
"$DB_INIT_JSON")
  else
    err "Need either 'jq' or 'python3' to parse dev/docker/db.init.json"
  fi

  # hardcode owner
  local owner="charcoal"

  install -d -m 0755 "$(dirname "$DB_INIT_OUT")"
  {
    echo "-- Auto-generated. Runs only on fresh MySQL volume."
    echo "SET @@session.sql_log_bin=0;"
    for db in "${schemas[@]}"; do
      [[ -z "$db" ]] && continue
      printf "CREATE DATABASE IF NOT EXISTS \`%s\` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;\n" "$db"
      printf "GRANT ALL PRIVILEGES ON \`%s\`.* TO '%s'@'%%';\n" "$db" "$owner"
    done
    echo "FLUSH PRIVILEGES;"
  } > "$DB_INIT_OUT"

  ok "Wrote $(realpath --relative-to="$ROOT" "$DB_INIT_OUT")"
}

ensure_http_env_overrides() {
  rm -f "$HTTP_ENV_OVR"; mkdir -p "$(dirname "$HTTP_ENV_OVR")"
  { echo "services:"; } > "$HTTP_ENV_OVR"
  local wrote=0
  for id in "${HTTP_SAPI[@]}"; do
    doc="${SAPI_DOCROOT[$id]:-}"
    [[ -z "$doc" ]] && continue
    cat >> "$HTTP_ENV_OVR" <<YML
  $(svc "$id"):
    environment:
      - CHARCOAL_SAPI_ROOT=$doc
YML
    wrote=1
  done
  [[ $wrote -eq 1 ]] || rm -f "$HTTP_ENV_OVR"
}

gen_sapi_df() {
  local id="$1" base="$2" extras="$3"
  local out="$ROOT/dev/docker/sapi/app/$id/Dockerfile"
  mkdir -p "$(dirname "$out")"
  sed -e "s/__SAPI_BASE__/$base/g" \
      -e "s/__DEPS__/$extras/g" \
      -e "s/__SAPI_ID__/$id/g" \
      "$ROOT/dev/docker/sapi/Dockerfile" > "$out"
  ok "Dockerfile for $id → dev/docker/sapi/app/$id/Dockerfile"
}

cmd_build_docker() {
  require_env

  # 1) Generate per-SAPI Dockerfiles from your templates
  gen_sapi_df engine cli "curl mariadb-client"
  gen_sapi_df web    fpm "curl nginx gettext-base"

  # 2) Runtime/manifest prep (unchanged)
  ensure_runtime_dirs
  write_manifest_overrides
  generate_db_init_sql
  resolve_profiles

  info "Building Composer deps (builder stage)…"
  # Use the engine Dockerfile (has the builder target with Composer)
  docker build \
    -f dev/docker/sapi/app/engine/Dockerfile \
    --target builder \
    -t charcoal/builder:latest \
    .

  # 3) Seed host vendors for DEV mounts so engine/web start healthy
  #    (safe no-op if already present)
  if [[ ! -f "dev/composer/vendor/autoload.php" ]]; then
    info "Seeding host dev/composer/vendor from builder image…"
    local _cid; _cid="$(docker create charcoal/builder:latest)"
    mkdir -p dev/composer/vendor
    docker cp "${_cid}":/home/charcoal/dev/composer/vendor/. dev/composer/vendor/
    docker rm -f "${_cid}" >/dev/null
    ok "Host vendors ready."
  else
    info "Host vendors already present; skipping seed."
  fi

  info "Compose up (profiles: ${EFFECTIVE:-none}) …"
  local UIDGID=(--build-arg CHARCOAL_UID="$(id -u)" --build-arg CHARCOAL_GID="$(id -g)")
  [[ "${CHARCOAL_DRYRUN:-0}" = "1" ]] && { ok "Dry-run: skipping docker."; return 0; }

  # 4) Build and start your stack as before
  compose build "${UIDGID[@]}"
  compose up -d

  # 5) Health check
  if engine_healthy 60; then
    ok "Engine is healthy."
  else
    err "Engine did not become healthy in time."
  fi
}

cmd_build_app() {
  require_env
  ensure_engine_up
  local do_composer="${1-}"  # use --composer to run a local install
  info "Checking dependencies…"

  # Composer — always update (DEV flow)
  if [[ -d "dev/composer" ]]; then
    info "Composer: updating dependencies in dev/composer …"
    if ! command -v composer >/dev/null 2>&1; then
      err "Composer not found on host. Install Composer or run './charcoal.sh build docker --dev-vendor'."
    fi
    ( cd dev/composer && composer update --no-interaction -o ) || err "Composer update failed"
    ok "Composer update complete."
  else
    warn "dev/composer directory not found; skipping composer."
  fi

  # CharcoalApp Builder
  if has_profile engine; then
    info "Initializing…"
    normal ""
    compose exec -T "$(svc engine)" bash -lc "${ENGINE_SNAPSHOT_CMD:-php -f /home/charcoal/engine/build.php}"
  else
    info "Engine profile disabled; skipping snapshot."
  fi
}

cmd_engine() {
  require_env
  local action="${1:-}"; shift || true
  case "$action" in
    inspect)
      compose exec -T "$(svc engine)" supervisorctl status || true
      ;;
    stop)
      local target="${1:-all}"
      compose exec -T "$(svc engine)" supervisorctl stop "$target" || true
      ;;
    restart)
      local target="${1:-all}"
      compose exec -T "$(svc engine)" supervisorctl restart "$target"
      ;;
    exec)
      [[ $# -ge 1 ]] || err "Usage: ./charcoal.sh engine exec <script> [args...]"
      compose exec -T "$(svc engine)" php /home/charcoal/charcoal.php "$@"
      ;;
    *)
      err "Usage: ./charcoal.sh engine {inspect|stop [all|name]|restart [all|name]|exec <script> [args...]}"
      ;;
  esac
}

cmd_docker() {
  require_env
  compose "$@"
}

tail_bg() {
  local file="$1" pidfile="$2"

  # already tailing?
  if [[ -f "$pidfile" ]] && kill -0 "$(cat "$pidfile")" 2>/dev/null; then
    info "Already tailing $(basename "$file") (PID $(cat "$pidfile"))."
    return 0
  fi

  # line-buffered tail, inherit parent's stdout/stderr
  ( stdbuf -oL -eL tail -n 200 -F -- "$file" ) &
  echo $! > "$pidfile"
  ok "Tailing $(basename "$file") in background (PID $!)"
}

cmd_logs() {
  require_env

  # modes: default=foreground, --bg to background, --stop to kill running tails
  local mode="fg"
  case "${1-}" in
    --stop)  shift; mode="stop" ;;
    --bg)    shift; mode="bg"   ;;
  esac

  if [[ "$mode" == "stop" ]]; then
    local target="${1-}"  # <sapi> or "all"
    [[ -n "$target" ]] || err "Usage: ./charcoal.sh logs --stop <sapi|all>"
    # kill matching pidfiles
    shopt -s nullglob
    for pf in "$LOGS_SEMA_DIR"/*.pid; do
      [[ -f "$pf" ]] || continue
      base="$(basename "$pf")"        # e.g. web.error.pid
      if [[ "$target" == "all" || "$base" == "$target."* ]]; then
        pid="$(cat "$pf" 2>/dev/null || echo)"
        if [[ -n "$pid" ]] && kill -0 "$pid" 2>/dev/null; then
          kill "$pid" || true
        fi
        rm -f "$pf"
        ok "Stopped tail: $base"
      fi
    done
    return 0
  fi

  local sapi="${1-}"
  local kind="${2-all}"

  [[ -n "$sapi" ]] || err "Usage: ./charcoal.sh logs [--bg|--stop] <sapi> [error|access|all]"
  case "${kind:-all}" in error|access|all) ;; *) err "Use one of: error | access | all" ;; esac

  local service
  if ! service="$(svc "$sapi")"; then err "Unknown SAPI '$sapi'"; fi

  local base="$ROOT/var/log/$sapi"
  install -d -m 0750 "$LOGS_SEMA_DIR"

  # collect files
  local files=()
  if [[ -d "$base" ]]; then
    [[ "$kind" == "error"  || "$kind" == "all" ]] && [[ -f "$base/error.log"  ]] && files+=("$base/error.log")
    [[ "$kind" == "access" || "$kind" == "all" ]] && [[ -f "$base/access.log" ]] && files+=("$base/access.log")
  fi

  if ((${#files[@]})); then
    if [[ "$mode" == "bg" ]]; then
      # spawn background tails with pidfiles
      for f in "${files[@]}"; do
        local tag="$(basename "$f" .log)"
        local pf="$LOGS_SEMA_DIR/${sapi}.${tag}.pid"
        tail_bg "$f" "$pf"
      done
      ok "Background tail(s) started for $sapi ($kind). Use: ./charcoal.sh logs --stop $sapi"
    else
      # foreground: attach and exit on Ctrl-C
      info "Tailing ${kind} logs for ${sapi} (Ctrl-C to stop)…"
      exec tail -n 200 -F -- "${files[@]}"
    fi
  else
    info "No local log files under var/log/$sapi; falling back to: docker compose logs -f $service"
    compose logs -f "$service"
  fi
}

cmd_services() {
  require_env
  # use JSON output to list service keys reliably
  compose config --format json | jq -r '.services | keys[]'
}

usage() {
  info -n "Usage:"
  normal "
  {yellow}./charcoal.sh{/} {cyan}build{/} docker
  {yellow}./charcoal.sh{/} {cyan}build{/} app {grey}[--reset]{/}
  {yellow}./charcoal.sh{/} {cyan}logs{/} {magenta}<sapi>{/} {grey}[error|access|all]{/}
  {yellow}./charcoal.sh{/} {cyan}engine{/} inspect
  {yellow}./charcoal.sh{/} {cyan}engine{/} stop {grey}[all|name]{/}
  {yellow}./charcoal.sh{/} {cyan}engine{/} restart {grey}[all|name]{/}
  {yellow}./charcoal.sh{/} {cyan}engine{/} exec {magenta}<script>{/} {grey}[args...]{/}
  {yellow}./charcoal.sh{/} {cyan}docker{/} {grey}<args...>{/}
"
}

main() {
  local ns="${1-}"; shift || true

  case "${ns-}" in
    build)
      local sub="${1-}"; shift || true
      case "${sub-}" in
        docker) cmd_build_docker "$@";;
        app)    cmd_build_app "$@";;
        "" )    usage; exit 1;;
        * )     err "Unknown 'build' subcommand: ${sub}"; usage; exit 1;;
      esac
      ;;

    engine)   cmd_engine "$@";;
    docker)   cmd_docker "$@";;

    logs)
      # Enforce: logs <sapi> [error|access|all]
      if [[ -z "${1-}" ]]; then
        err "Usage: ./charcoal.sh logs <sapi> [error|access|all]"
        exit 1
      fi
      cmd_logs "${1-}" "${2-}"
      ;;

    services) cmd_services ;;

    "" )      usage; exit 1;;
    * )       err "Unknown command: ${ns}"; usage; exit 1;;
  esac
}

main "$@"
