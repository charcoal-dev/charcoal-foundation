#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

: "${ROOT:?ROOT must be set}"
MANIFEST="$ROOT/dev/sapi.manifest.json"
MAN_OVR="${MAN_OVR:-$ROOT/dev/docker/compose/manifest.overrides.yml}"

# registries
declare -A SAPI_SERVICE SAPI_TYPE SAPI_DOCROOT SAPI_ENV_JSON SAPI_PORT
SAPI_IDS=(); HTTP_SAPI=(); CLI_SAPI=()

trim_slashes() { local s="${1:-}"; s="${s#/}"; s="${s%/}"; printf '%s' "$s"; }

load_manifest() {
  [[ -f "$MANIFEST" ]] || { warn "Manifest not found: $MANIFEST"; return 0; }

  # registries
  SAPI_IDS=(); HTTP_SAPI=(); CLI_SAPI=(); declare -gA SAPI_TYPE SAPI_SERVICE SAPI_DOCROOT SAPI_PORT SAPI_ENV_JSON
  local base_docroot="/home/charcoal"

  if command -v jq >/dev/null 2>&1; then
    while IFS=$'\t' read -r id typ root port env_json; do
      [[ -n "$id" ]] || continue
      SAPI_IDS+=("$id")
      SAPI_TYPE["$id"]="${typ:-cli}"
      SAPI_SERVICE["$id"]="$id"

      if [[ "${typ:-cli}" == "http" ]]; then
        HTTP_SAPI+=("$id")
        root="${root#/}"; root="${root%/}"
        SAPI_DOCROOT["$id"]="$base_docroot/${root}"
        [[ -n "${port:-}" && "$port" != "0" ]] && SAPI_PORT["$id"]="$port" || true
      else
        CLI_SAPI+=("$id")
      fi

      # keep raw JSON string for later
      SAPI_ENV_JSON["$id"]="${env_json:-{}}"
    done < <(
      jq -rc '.charcoal.sapi[]
              | select(.enabled != false)
              | [ (.id|tostring),
                  (.type // "cli"),
                  (.root // ""),
                  ((.port // 0)|tostring),
                  ((.env // {}) | @json)
                ]
              | @tsv' "$MANIFEST"
    )
  elif command -v python3 >/dev/null 2>&1; then
    while IFS=$'\t' read -r id typ root port env_json; do
      [[ -n "$id" ]] || continue
      SAPI_IDS+=("$id")
      SAPI_TYPE["$id"]="${typ:-cli}"
      SAPI_SERVICE["$id"]="$id"
      if [[ "${typ:-cli}" == "http" ]]; then
        HTTP_SAPI+=("$id")
        root="${root#/}"; root="${root%/}"
        SAPI_DOCROOT["$id"]="$base_docroot/${root}"
        [[ -n "${port:-}" && "$port" != "0" ]] && SAPI_PORT["$id"]="$port" || true
      else
        CLI_SAPI+=("$id")
      fi
      SAPI_ENV_JSON["$id"]="${env_json:-{}}"
    done < <(python3 - "$MANIFEST" <<'PY'
import json,sys
j=json.load(open(sys.argv[1]))
for it in j.get("charcoal",{}).get("sapi",[]):
  if it.get("enabled", True):
    _id   = str(it.get("id",""))
    _typ  = it.get("type","cli")
    _root = it.get("root","")
    _port = str(it.get("port",0) or 0)
    _env  = json.dumps(it.get("env",{}), separators=(',',':'))
    print("\t".join([_id,_typ,_root,_port,_env]))
PY
    )
  else
    warn "Neither jq nor python3 found; skipping manifest load (no overrides/ports)."
    return 0
  fi
}

sanitize_json() {
  # strip CR/LF and validate; return {} if invalid/empty
  local s="${1//$'\r'/}"; s="${s//$'\n'/}"
  if [[ -z "$s" ]]; then
    printf '{}'
  elif jq -e . >/dev/null 2>&1 <<<"$s"; then
    printf '%s' "$s"
  else
    warn "Invalid env JSON in manifest; falling back to {} → [$s]"
    printf '{}'
  fi
}

write_manifest_overrides() {
  rm -f "$MAN_OVR"; mkdir -p "$(dirname "$MAN_OVR")"
  {
    echo "services:"
    for id in "${SAPI_IDS[@]}"; do
      svc="${SAPI_SERVICE[$id]}"
      echo "  $svc:"
      echo "    environment:"
      # docroot for http sapis
      if [[ -n "${SAPI_DOCROOT[$id]:-}" ]]; then
        echo "      CHARCOAL_SAPI_ROOT: \"${SAPI_DOCROOT[$id]}\""
      fi
      # additional env from manifest (robust)
      env_json="$(sanitize_json "${SAPI_ENV_JSON[$id]:-}")"
      if [[ "$env_json" != "{}" ]]; then
        jq -rn --argjson env "$env_json" '
          $env
          | to_entries[]
          | "      \(.key): \"\(.value|tostring)\""
        '
      fi
      # host port for http sapis (optional)
      if [[ -n "${SAPI_PORT[$id]:-}" && "${SAPI_TYPE[$id]}" == "http" ]]; then
        echo "    ports:"
        echo "      - \"${SAPI_PORT[$id]}:6000\""
      fi
    done
  } > "$MAN_OVR"
  ok "Manifest overrides → $(realpath --relative-to="$ROOT" "$MAN_OVR")"
}


# resolve service name (manifest mapping or identity)
svc() {
  local k="${1:?service id required}"
  if [[ -n "${SAPI_SERVICE[$k]:-}" ]]; then
    printf '%s\n' "${SAPI_SERVICE[$k]}"
  else
    printf '%s\n' "$k"
  fi
}
