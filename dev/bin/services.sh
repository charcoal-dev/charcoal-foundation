#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

: "${ROOT:?ROOT must be set}"
MANIFEST="$ROOT/dev/sapi.manifest.json"
MAN_OVR="${MAN_OVR:-$ROOT/dev/docker/compose/manifest.overrides.yml}"

# registries
# was: declare -A SAPI_SERVICE SAPI_TYPE SAPI_DOCROOT SAPI_ENV_JSON SAPI_PORT
declare -A SAPI_SERVICE SAPI_TYPE SAPI_DOCROOT SAPI_ENV_B64 SAPI_PORT
SAPI_IDS=(); HTTP_SAPI=(); CLI_SAPI=()

# ...

load_manifest() {
  [[ -f "$MANIFEST" ]] || { warn "Manifest not found: $MANIFEST"; return 0; }

  # require jq
  command -v jq >/dev/null 2>&1 || err "jq is required to load $MANIFEST"

  # registries
  SAPI_IDS=(); HTTP_SAPI=(); CLI_SAPI=()
  declare -gA SAPI_TYPE SAPI_SERVICE SAPI_DOCROOT SAPI_PORT SAPI_ENV_B64

  while IFS=$'\t' read -r id typ root port env_b64; do
    [[ -n "$id" ]] || continue

    # normalize basics
    typ="${typ:-cli}"
    root="${root#/}"; root="${root%/}"   # strip leading/trailing slash
    port="${port:-0}"

    # registries
    SAPI_IDS+=("$id")
    SAPI_TYPE["$id"]="$typ"
    SAPI_SERVICE["$id"]="$id"

    # docroot for ANY sapi that provided one
    if [[ -n "$root" ]]; then
      SAPI_DOCROOT["$id"]="/home/charcoal/${root}"
    fi

    # classify + optional port for HTTP sapis
    if [[ "$typ" == "http" ]]; then
      HTTP_SAPI+=("$id")
      [[ -n "$port" && "$port" != "0" ]] && SAPI_PORT["$id"]="$port" || true
    else
      CLI_SAPI+=("$id")
    fi

    # env payload (base64 of JSON)
    SAPI_ENV_B64["$id"]="$env_b64"

  done < <(
    jq -rc '.charcoal.sapi[]
            | select(.enabled != false)
            | [ (.id|tostring),
                (.type // "cli"),
                (.root // ""),
                ((.port // 0)|tostring),
                ((.env // {}) | @json | @base64)
              ]
            | @tsv' "$MANIFEST"
  )
}

# sanitize_json() is unused — keep or delete

write_manifest_overrides() {
  rm -f "$MAN_OVR"; mkdir -p "$(dirname "$MAN_OVR")"
  {
    echo "services:"
    for id in "${SAPI_IDS[@]}"; do
      svc="${SAPI_SERVICE[$id]}"
      echo "  $svc:"
      echo "    environment:"
      if [[ -n "${SAPI_DOCROOT[$id]:-}" ]]; then
        echo "      CHARCOAL_SAPI_ROOT: \"${SAPI_DOCROOT[$id]}\""
      fi

      env_b64="${SAPI_ENV_B64[$id]:-e30=}"   # e30= is '{}' in base64
      if [[ -n "$env_b64" ]]; then
        env_json="$(printf '%s' "$env_b64" | base64 -d 2>/dev/null || printf '{}')"
        jq -rn --argjson env "$env_json" '
          $env | to_entries[] | "      \(.key): \"\(.value|tostring)\""
        '
      fi

      if [[ -n "${SAPI_PORT[$id]:-}" && "${SAPI_TYPE[$id]}" == "http" ]]; then
        echo "    ports:"
        echo "      - \"${SAPI_PORT[$id]}:6000\""
      fi
    done
  } > "$MAN_OVR"

  # best-effort relative path print
  if command -v realpath >/dev/null 2>&1; then
    ok "Manifest overrides → $(realpath --relative-to="$ROOT" "$MAN_OVR" 2>/dev/null || echo "$MAN_OVR")"
  else
    ok "Manifest overrides → $MAN_OVR"
  fi
}

svc() {
  local k="${1:?service id required}"
  if [[ -n "${SAPI_SERVICE[$k]:-}" ]]; then
    printf '%s\n' "${SAPI_SERVICE[$k]}"
  else
    printf '%s\n' "$k"
  fi
}