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
  SAPI_IDS=(); HTTP_SAPI=(); CLI_SAPI=()
  declare -gA SAPI_TYPE SAPI_SERVICE SAPI_DOCROOT SAPI_PORT SAPI_ENV_B64

  # ...
  while IFS=$'\t' read -r id typ root port env_b64; do
    [[ -n "$id" ]] || continue
    SAPI_IDS+=("$id")
    SAPI_TYPE["$id"]="${typ:-cli}"
    SAPI_SERVICE["$id"]="$id"

    if [[ "${typ:-cli}" == "http" ]]; then
      HTTP_SAPI+=("$id")
      root="${root#/}"; root="${root%/}"
      SAPI_DOCROOT["$id"]="/home/charcoal/${root}"
      [[ -n "${port:-}" && "$port" != "0" ]] && SAPI_PORT["$id"]="$port" || true
    else
      CLI_SAPI+=("$id")
    fi

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
      env_b64="${SAPI_ENV_B64[$id]:-e30=}"   # e30= is '{}' base64
      if [[ -n "$env_b64" ]]; then
        # decode to JSON (fallback to {} on any error)
        env_json="$(printf '%s' "$env_b64" | base64 -d 2>/dev/null || printf '{}')"
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
