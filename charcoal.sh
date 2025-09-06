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

require_http_roots() {
  [[ -d "$ROOT/config" ]] || { err2 "Missing config/ directory."; exit 1; }
  [[ -f "$ROOT/config/charcoal.json" ]] || { err2 "Missing config/charcoal.json."; exit 1; }
  [[ -d "$ROOT/config/http" ]] || { err2 "Missing config/http/ directory."; exit 1; }
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
  if ! compose ps --services 2>/dev/null | grep -qx "$(svc engine)"; then
    info "Engine service missing → building docker (engine)…"
    compose build "$(svc engine)" || err "Docker build failed for engine"
    compose up -d --no-deps "$(svc engine)" || err "Failed to start engine"
  fi
}

export COMPOSE_PROFILES="${COMPOSE_PROFILES:-${CHARCOAL_DOCKER:-engine,web}}"

generate_db_init_sql() {
  [[ -f "$DB_INIT_JSON" ]] || { info "No dev/db.manifest.json, skipping DB bootstrap."; return 0; }
  has_profile mysql || { info "Profile 'mysql' disabled, skipping DB bootstrap."; return 0; }

  local tmp out dir owner
  out="$DB_INIT_OUT"
  dir="$(dirname "$out")"
  owner="charcoal"

  info "Generating MySQL init SQL from dev/db.manifest.json …"
  install -d -m 0755 "$dir"

  # read schemas[]
  local schemas=()
  if command -v jq >/dev/null 2>&1; then
    mapfile -t schemas < <(jq -r '.schemas[]? // empty' "$DB_INIT_JSON")
  elif command -v python3 >/dev/null 2>&1; then
    mapfile -t schemas < <(python3 - <<'PY' "$DB_INIT_JSON"
import json,sys
j=json.load(open(sys.argv[1]))
for s in j.get("schemas",[]):
    if isinstance(s,str) and s.strip(): print(s)
PY
)
  else
    err "Need 'jq' or 'python3' to parse dev/db.manifest.json"
  fi

  # nothing to write?
  if ((${#schemas[@]}==0)); then
    info "No schemas in manifest; skipping SQL render."
    return 0
  fi

  # render to temp, replace only if changed
  tmp="$(mktemp "${TMPDIR:-/tmp}/init.sql.XXXXXX")"
  {
    echo "-- Auto-generated. Runs only on fresh MySQL volume."
    echo "SET @@session.sql_log_bin=0;"
    for db in "${schemas[@]}"; do
      [[ -z "$db" ]] && continue
      printf "CREATE DATABASE IF NOT EXISTS \`%s\` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;\n" "$db"
      printf "GRANT ALL PRIVILEGES ON \`%s\`.* TO '%s'@'%%';\n" "$db" "$owner"
    done
    echo "FLUSH PRIVILEGES;"
  } >"$tmp"

  # normalize perms; only replace if different
  chmod 0644 "$tmp"
  if [[ -f "$out" ]] && cmp -s "$tmp" "$out"; then
    rm -f "$tmp"
    info "MySQL init unchanged (skipped)."
  else
    mv -f "$tmp" "$out"
    ok "Wrote $(realpath --relative-to="$ROOT" "$out")"
  fi
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

ensure_service_up() {
  require_env
  local sapi="${1:?sapi required}"
  compose up -d --no-deps "$(svc "$sapi")" >/dev/null
}

ensure_db_init_ready() {
  local d="dev/docker/utils/db/init"
  umask 022
  mkdir -p "$d"

  # normalize line endings on scripts (safe if already LF)
  find "$d" -type f -name '*.sh' -print0 | xargs -0 -r sed -i 's/\r$//'

  chmod 755 "$d"
  find "$d" -type f -name '*.sh'  -exec chmod 755 {} \;
  find "$d" -type f -name '*.sql' -exec chmod 644 {} \;
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

select_nginx_base_template() {
  local sapi="$1"
  local app_base="$ROOT/dev/docker/sapi/app/$sapi/nginx.conf"
  local fpm_base="$ROOT/dev/docker/sapi/fpm/nginx.conf"
  [[ -f "$app_base" ]] && { printf "%s" "$app_base"; return 0; }
  printf "%s" "$fpm_base"
}

generate_nginx_scaffold() {
  local sapi="$1"
  local outdir="$ROOT/dev/docker/sapi/app/$sapi"
  mkdir -p "$outdir"
  local base; base="$(select_nginx_base_template "$sapi")" || { err2 "No base nginx.conf for $sapi"; exit 1; }
  cp -f "$base" "$outdir/nginx.generated.conf"
  ok "Scaffolded nginx.generated.conf for $sapi (from $(basename "$base"))."
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

  # Build TLS Config Inventory
  while IFS= read -r id; do
    outdir="$ROOT/dev/docker/sapi/app/$id"
    mkdir -p "$outdir"
    collect_tls_inventory_for_sapi "$id" | jq -s . > "$outdir/tls.manifest.json"
    generate_nginx_scaffold "$id"
  done < <(
    jq -r '.charcoal.sapi[]
           | select(.type=="http" and (.enabled==null or .enabled==true) and (.nginxGenerateConfig==true))
           | .id' "$MANIFEST"
  )

  info "Compose up (profiles: ${EFFECTIVE:-none}) …"
  local UIDGID=(--build-arg CHARCOAL_UID="$(id -u)" --build-arg CHARCOAL_GID="$(id -g)")
  [[ "${CHARCOAL_DRYRUN:-0}" = "1" ]] && { ok "Dry-run: skipping docker."; return 0; }

  # 3) Build and start your stack as before
  compose build "${UIDGID[@]}"
  compose up -d

  # 4) Health check
  if engine_healthy 60; then
    ok "Engine is healthy."
  else
    err "Engine did not become healthy in time."
  fi
}

# run_in_engine <display_name> <stdout_log> <stderr_log|-> <cmd...>
run_in_engine() {
  local name="$1" out="$2" err="$3"; shift 3
  local svc; svc="$(svc engine)"

  compose exec -T "$svc" bash -s -- "$name" "$out" "$err" "$@" <<'BASH'
set -euo pipefail
NAME="$1"; OUT="$2"; ERR="$3"; shift 3

mkdir -p "$(dirname "$OUT")"; : > "$OUT" || true

if [ "$ERR" != "-" ] && [ -n "$ERR" ]; then
  mkdir -p "$(dirname "$ERR")"; : > "$ERR" || true
  # stdout → console+OUT, stderr → ERR (not echoed)
  "$@" > >(tee -a "$OUT") 2>>"$ERR"
else
  # merged stdout+stderr → console+OUT
  "$@" 2>&1 | tee -a "$OUT"
fi
BASH
}

cmd_build_app() {
  require_env
  ensure_engine_up

  info "Checking dependencies…"
  run_in_engine "composer update" \
    /home/charcoal/var/log/composer.log \
    - \
    /usr/local/bin/composer -d /home/charcoal/dev/composer update --no-interaction -o \
  || exit 1

  info "Initializing Charcoal App…"
  run_in_engine "php build.php" \
    /home/charcoal/var/log/build.log \
    /home/charcoal/var/log/error.log \
    /usr/local/bin/php \
      -d log_errors=1 \
      -d error_log=/home/charcoal/var/log/error.log \
      -d display_errors=0 \
      /home/charcoal/engine/build.php \
  || exit 1
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
      if [ -t 1 ]; then
        COMPOSE_TTY="";  TTY_VAL=1   # interactive → allow TTY, enable color
      else
        COMPOSE_TTY="-T"; TTY_VAL=0  # non-interactive → no TTY
      fi

      [[ $# -ge 1 ]] || err "Usage: ./charcoal.sh engine exec <script> [args...]"

      compose exec $COMPOSE_TTY "$(svc engine)" \
        bash /home/charcoal/engine/charcoal.sh "$@" --ansi --tty="${TTY_VAL}"
      ;;
    *)
      err "Usage: ./charcoal.sh engine {inspect|stop [all|name]|restart [all|name]|exec <script> [args...]}"
      ;;
  esac
}

cmd_docker() {
  require_env
  ensure_db_init_ready
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

  local sapi="${1-}"
  local name="${2-}"
  [[ -n "$sapi" ]] || err "Usage: ./charcoal.sh logs <sapi> [logname]"

  local service
  if ! service="$(svc "$sapi")"; then err "Unknown SAPI '$sapi'"; fi

  local dir="$ROOT/var/log/$sapi"

  if [[ -n "$name" ]]; then
    # ensure .log and keep basename only
    local base="${name##*/}"
    base="${base%.log}.log"
    local file="$dir/$base"

    [[ -f "$file" ]] || err "No such log: var/log/$sapi/$base"
    info "Tailing var/log/$sapi/$base (Ctrl-C to stop)…"
    exec tail -n 200 -F -- "$file"
  else
    info "No log specified; streaming container logs for $service (Ctrl-C to stop)…"
    exec compose logs -f "$service"
  fi
}

cmd_services() {
  require_env
  # use JSON output to list service keys reliably
  compose config --format json | jq -r '.services | keys[]'
}

cmd_ssh() {
  require_env
  local sapi="${1-}"; shift || true
  [[ -n "$sapi" ]] || err "Usage: ./charcoal.sh ssh <sapi> [shell [args...]]"

  local service; service="$(svc "$sapi")" || err "Unknown SAPI '$sapi'"

  ensure_service_up "$sapi"
  local shell="${1:-bash}"; shift || true

  if [[ "$shell" == "bash" ]]; then
    if ! compose exec -T "$service" sh -lc 'command -v bash >/dev/null 2>&1'; then
      shell="sh"
    fi
  fi

  if [[ -t 0 && -t 1 ]]; then
    compose exec -ti "$service" "$shell" "$@"
  else
    compose exec -T  "$service" "$shell" "$@"
  fi
}

cmd_update() {
  # refuse if tracked changes exist (keeps ignored configs)
  git diff --quiet && git diff --cached --quiet \
    || err "Working tree has tracked changes. Move local edits to ignored files."

  before="$(git rev-parse HEAD)"
  git pull --ff-only --prune || err "git pull failed"
  after="$(git rev-parse HEAD)"

  # if changed → build app
  [[ "$after" != "$before" ]] && cmd_build_app || info "Already up to date."
}

usage() {
  info -n "Usage:"
  normal "
  {yellow}./charcoal.sh{/} {cyan}build{/} docker
  {yellow}./charcoal.sh{/} {cyan}build{/} {grey}[app]{/} {grey}[--reset]{/}
  {yellow}./charcoal.sh{/} {cyan}update{/} {grey}[--force] [--no-changelog]{/}
  {yellow}./charcoal.sh{/} {cyan}pull{/} {grey}alias of \"update\"{/}
  {yellow}./charcoal.sh{/} {cyan}logs{/} {magenta}<sapi>{/} {grey}[error|access|all]{/}
  {yellow}./charcoal.sh{/} {cyan}engine{/} inspect
  {yellow}./charcoal.sh{/} {cyan}engine{/} stop {grey}[all|name]{/}
  {yellow}./charcoal.sh{/} {cyan}engine{/} restart {grey}[all|name]{/}
  {yellow}./charcoal.sh{/} {cyan}engine{/} exec {magenta}<script>{/} {grey}[args...]{/}
  {yellow}./charcoal.sh{/} {cyan}ssh{/} {magenta}<sapi>{/} {grey}[shell]{/}
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
        ""|app) cmd_build_app "$@";;
        * )     err "Unknown 'build' subcommand: ${sub}"; usage; exit 1;;
      esac
      ;;

    update|pull)   cmd_update "$@";;
    engine)   cmd_engine "$@";;
    docker)   cmd_docker "$@";;
    ssh)      cmd_ssh "$@";;

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
