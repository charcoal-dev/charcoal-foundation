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
ROOT="$(cd "$(dirname "$0")" && pwd)"
ENV_FILE="$ROOT/dev/.env"
DB_INIT_JSON="$ROOT/dev/db.manifest.json"
DB_INIT_OUT="$ROOT/dev/docker/utils/db/init/01-init-dbs.sql"
SEMAPHORE_DIR="$ROOT/var/shared/semaphore"
LOGS_SEMA_DIR="$SEMAPHORE_DIR/logs"
OVR_PORTS="$ROOT/dev/docker/compose.ports.yml"
MOUNTS_DEV="$ROOT/dev/docker/compose/mounts.dev.yml"
MOUNTS_PROD="$ROOT/dev/docker/compose/mounts.prod.yml"

SERVICES_FILE="$ROOT/dev/bin/services.sh"
[[ -f "$SERVICES_FILE" ]] && . "$SERVICES_FILE" || svc(){ echo "$1"; }

# Load App Manifest
load_manifest

# Colors and Styling
STYLING_FILE="$ROOT/dev/bin/styling.sh"
if [ -r "$STYLING_FILE" ]; then
  . "$STYLING_FILE"
else
  printf 'Missing styling file: %s\n' "$STYLING_FILE" >&2
  exit 1
fi


require_env() {
  [[ -f "$ENV_FILE" ]] || err2 "Error:{/} Environment configuration file {yellow}[dev/.env]{/} not found."
  info "Charcoal Diagnostics:{/}{grey} Contact vendor for package specific environments configuration file."
  exit 1;
  set -a; # export vars when sourcing
  # shellcheck disable=SC1090
  . "$ENV_FILE"
  set +a
}

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
  docker compose --env-file "$ENV_FILE" $(compose_flags) "$@"
}

has_profile() {
  [[ ",${COMPOSE_PROFILES:-}," == *",$1,"* ]]
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

generate_db_init_sql() {
  [[ -f "$DB_INIT_JSON" ]] || { info "No db.init.json, skipping DB bootstrap."; return 0; }
  has_profile mysql || { info "Profile 'mysql' disabled, skipping DB bootstrap."; return 0; }

  info "Generating MySQL init SQL from dev/docker/db.init.json …"

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
  for id in "${HTTP_SAPIS[@]}"; do
    doc="${SAPI_DOCROOT[$id]:-}"
    [[ -z "$doc" ]] && continue
    cat >> "$HTTP_ENV_OVR" <<YML
  $(svc "$id"):
    environment:
      - NGINX_DOCROOT=$doc
YML
    wrote=1
  done
  [[ $wrote -eq 1 ]] || rm -f "$HTTP_ENV_OVR"
}


gen_sapi_df() {
  local id="$1" base="$2" extras="$3" out="$ROOT/dev/docker/sapi/$id/Dockerfile"
  mkdir -p "$(dirname "$out")"
  sed -e "s/__SAPI_BASE__/$base/g" \
      -e "s/__DEPS__/$extras/g" \
      -e "s/__SAPI_ID__/$id/g" \
      "$ROOT/dev/docker/sapi/Dockerfile.stub" > "$out"
  ok "Dockerfile for $id → dev/docker/sapi/$id/Dockerfile"
}

cmd_build_docker() {
  require_env
  gen_sapi_df engine cli "mariadb-client"
  gen_sapi_df web    fpm "nginx gettext-base iputils-ping"
  ensure_runtime_dirs
  write_manifest_overrides
  generate_db_init_sql
  info "Compose up (profiles: ${COMPOSE_PROFILES:-none}) …"
  local UIDGID=(--build-arg CHARCOAL_UID="$(id -u)" --build-arg CHARCOAL_GID="$(id -g)")
  compose build "${UIDGID[@]}"
  compose up -d
  if engine_healthy 60; then
    ok "Engine is healthy."
  else
    err "Engine did not become healthy in time."
  fi
}

cmd_build_app() {
  require_env
  local reset="${1:-}"
  if ! engine_healthy 1; then err "Engine is not healthy; run: ./charcoal.sh build docker"; fi
  if [[ "$reset" == "--reset" ]]; then
    info "Reset requested — purging previous serialized state under var/shared …"
    rm -rf "$ROOT/var/shared/"*
  fi
  info "Triggering app build inside engine …"
  compose exec -T "$(svc engine)" php /home/charcoal/build.php
  ok "App build completed."
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

tail_bg() { # $1=file $2=pidfile
  local file="$1" pidfile="$2"
  nohup tail -F "$file" >/dev/stdout 2>&1 &
  local pid=$!
  echo "$pid" > "$pidfile"
  ok "Tailing $(basename "$file") in background (PID $pid)"
}

cmd_logs() {
  require_env
  local sapi="${1:-}"; local kind="${2:-all}"
  [[ -n "$sapi" ]] || err "Usage: ./charcoal.sh logs <sapi> [error|access|all]"
  local base="$ROOT/var/log/$sapi"
  [[ -d "$base" ]] || err "Unknown SAPI '$sapi' or log dir missing: var/log/$sapi"

  install -d -m 0750 "$LOGS_SEMA_DIR"

  local tailed=0
  if [[ "$kind" == "error" || "$kind" == "all" ]]; then
    local f="$base/error.log" p="$LOGS_SEMA_DIR/${sapi}.error.pid"
    if [[ -f "$f" ]]; then tail_bg "$f" "$p"; tailed=1; fi
  fi
  if [[ "$kind" == "access" || "$kind" == "all" ]]; then
    local f="$base/access.log" p="$LOGS_SEMA_DIR/${sapi}.access.pid"
    if [[ -f "$f" ]]; then tail_bg "$f" "$p"; tailed=1; fi
  fi

  if [[ $tailed -eq 0 ]]; then
    info "No log files found; falling back to: docker compose logs -f ${sapi}"
    compose logs -f "$(svc "$sapi")"
  fi
}

usage() {
  cat <<'USAGE'
Usage:
  ./charcoal.sh build docker
  ./charcoal.sh build app [--reset]
  ./charcoal.sh engine inspect
  ./charcoal.sh engine stop [all|name]
  ./charcoal.sh engine restart [all|name]
  ./charcoal.sh engine exec <script> [args...]
  ./charcoal.sh docker <args...>
  ./charcoal.sh logs <sapi> [error|access|all]
USAGE
}

main() {
  local ns="${1:-}"; shift || true
  case "$ns" in
    build)
      case "${1:-}" in
        docker) shift; cmd_build_docker "$@";;
        app)    shift; cmd_build_app "$@";;
        *) usage; exit 1;;
      esac
      ;;
    engine) shift || true; cmd_engine "$@";;
    docker) shift || true; cmd_docker "$@";;
    logs)   shift || true; cmd_logs "$@";;
    *) usage; exit 1;;
  esac
}

main "$@"
