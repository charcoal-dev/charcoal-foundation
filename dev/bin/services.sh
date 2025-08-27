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
  [[ -f "$MANIFEST" ]] || { warn "Manifest not found: $(realpath "$MANIFEST" 2>/dev/null || echo "$MANIFEST")"; return 0; }

  local base_docroot="/home/charcoal"
  local line id typ root port env_json

  if command -v jq >/dev/null 2>&1; then
    # id, type, root, port, env(JSON)
    while IFS=$'\t' read -r id typ root port env_json; do
      [[ -n "$id" ]] || continue
      SAPI_IDS+=("$id")
      SAPI_TYPE["$id"]="${typ:-cli}"
      SAPI_SERVICE["$id"]="$id"  # 1:1 mapping (service name = id)

      if [[ "${typ:-cli}" == "http" ]]; then
        HTTP_SAPI+=("$id")
        # build absolute docroot from suffix
        root="$(trim_slashes "${root:-}")"
        SAPI_DOCROOT["$id"]="$base_docroot/${root}"
        # optional port (0/empty => no publish)
        [[ -n "${port:-}" && "$port" != "0" ]] && SAPI_PORT["$id"]="$port" || true
      else
        CLI_SAPI+=("$id")
      fi

      # compact JSON object for env bag
      SAPI_ENV_JSON["$id"]="${env_json:-{}}"
    done < <(
      jq -rc '.charcoal.sapi[]
              | select(.enabled != false)
              | [ .id
                , (.type // "cli")
                , (.root // "")
                , ( (try .port) // 0 )
                , (.env // {} )
                ]
              | @tsv' "$MANIFEST"
    )
  else
    # minimal python fallback -> same TSV contract as above
    while IFS=$'\t' read -r id typ root port env_json; do
      [[ -n "$id" ]] || continue
      SAPI_IDS+=("$id")
      SAPI_TYPE["$id"]="${typ:-cli}"
      SAPI_SERVICE["$id"]="$id"
      if [[ "${typ:-cli}" == "http" ]]; then
        HTTP_SAPI+=("$id")
        root="$(trim_slashes "${root:-}")"
        SAPI_DOCROOT["$id"]="$base_docroot/${root}"
        [[ -n "${port:-}" && "$port" != "0" ]] && SAPI_PORT["$id"]="$port" || true
      else
        CLI_SAPI+=("$id")
      fi
      SAPI_ENV_JSON["$id"]="${env_json:-{}}"
    done < <(
      python3 - "$MANIFEST" <<'PY'
import json,sys
mf=sys.argv[1]
j=json.load(open(mf))
for it in j.get("charcoal",{}).get("sapi",[]):
  if it.get("enabled", True):
    _id = it.get("id","")
    _typ= it.get("type","cli")
    _root=it.get("root","")
    _port=it.get("port",0) or 0
    _env = it.get("env",{})
    # emit: id \t type \t root \t port \t compact-json
    print("%s\t%s\t%s\t%s\t%s" % (_id, _typ, _root, _port, json.dumps(_env, separators=(',',':'))))
PY
    )
  fi
}

write_manifest_overrides() {
  mkdir -p "$(dirname "$MAN_OVR")"
  {
    echo "services:"
    for id in "${SAPI_IDS[@]}"; do
      local svc="${SAPI_SERVICE[$id]}"
      echo "  $svc:"
      echo "    environment:"
      # docroot for http
      if [[ "${SAPI_TYPE[$id]}" == "http" && -n "${SAPI_DOCROOT[$id]:-}" ]]; then
        echo "      CHARCOAL_SAPI_ROOT: \"${SAPI_DOCROOT[$id]}\""
      fi
      # arbitrary env from manifest
      if [[ -n "${SAPI_ENV_JSON[$id]:-}" && "${SAPI_ENV_JSON[$id]}" != "{}" ]]; then
        jq -r 'to_entries[] | "      \(.key): \"\(.value|tostring)\""' <<<"${SAPI_ENV_JSON[$id]}"
      fi
      # host port mapping (optional)
      if [[ "${SAPI_TYPE[$id]}" == "http" && -n "${SAPI_PORT[$id]:-}" ]]; then
        echo "    ports:"
        echo "      - \"${SAPI_PORT[$id]}:6000\""
      fi
    done
  } > "$MAN_OVR"

  if command -v realpath >/dev/null 2>&1; then
    ok "Manifest overrides → $(realpath --relative-to="$ROOT" "$MAN_OVR")"
  else
    ok "Manifest overrides → $MAN_OVR"
  fi
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
