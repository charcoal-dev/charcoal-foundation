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

  # verify each enabled HTTP SAPI has config/http/{sapi}.sapi.json
  local _missing=0
  for _id in "${HTTP_SAPI[@]}"; do
    _f="$ROOT/config/http/${_id}.sapi.json"
    [[ -f "$_f" ]] || { err2 "Missing HTTP config for SAPI \"${_id}\": expected config/http/${_id}.sapi.json."; _missing=1; }
  done
  [[ $_missing -eq 0 ]] || exit 1
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
      echo "      CHARCOAL_APP_ENV: \"${CHARCOAL_APP_ENV:-dev}\""

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

# usage: collect_tls_inventory_for_sapi "web" | jq -s . > "$ROOT/dev/docker/nginx/web/tls-inventory.json"
collect_tls_inventory_for_sapi() {
  local sapi="$1"
  local cfg="$ROOT/config/http/${sapi}.sapi.json"
  local storage="$ROOT/var/storage"

  command -v jq >/dev/null 2>&1 || { err2 "jq is required."; exit 1; }
  [[ -f "$cfg" ]] || { err2 "HTTP config for SAPI \"$sapi\" not found at config/http/${sapi}.sapi.json."; exit 1; }
  [[ -d "$storage" ]] || { err2 "Missing var/storage/ directory at $storage."; exit 1; }

  _normalize_identity() {
    local host="$1"
    [[ "$host" == \*.* ]] && printf ".%s" "${host#*.}" || printf "%s" "$host"
  }

  _reject_bad_relpath() {
    local rel="$1"
    [[ "$rel" == /* ]] && { err2 "Absolute path not allowed: $rel (must be relative to var/storage)."; exit 1; }
    [[ "$rel" == *"../"* || "$rel" == "./"* ]] && { err2 "Invalid relative path: $rel (no ../ or ./ allowed)."; exit 1; }
  }

  _resolve_inside_storage() {
    local rel="$1"
    local abs="$storage/$rel"
    [[ -e "$abs" ]] || { err2 "File not found under var/storage: $rel"; exit 1; }
    # resolve symlinks; ensure final path stays inside var/storage
    local real
    real="$(readlink -f -- "$abs" 2>/dev/null || realpath -- "$abs")" || { err2 "Failed to resolve path: $rel"; exit 1; }
    [[ "$real" == "$storage"* ]] || { err2 "Path escapes var/storage: $rel → $real"; exit 1; }
    printf "%s" "$real"
  }

  _owner_uid() {
    # Linux: stat -c %u; macOS: stat -f %u
    stat -c %u -- "$1" 2>/dev/null || stat -f %u -- "$1"
  }

  _check_ownership() {
    local p="$1"
    local want; want="$(id -u)"
    local have; have="$(_owner_uid "$p")" || { err2 "Cannot read owner for: $p"; exit 1; }
    [[ "$have" == "$want" ]] || { err2 "Owner mismatch for $p (uid $have), expected current user (uid $want)."; exit 1; }
  }

  _check_ext_cert() { [[ "$1" == *.crt || "$1" == *.pem ]]; }
  _check_ext_key()  { [[ "$1" == *.key || "$1" == *.pem ]]; }

  # iterate hosts
  while IFS= read -r row; do
    # skip hosts without tls object
    local has_tls; has_tls="$(jq -r 'has("tls") and (.tls|type=="object")' <<< "$row")"
    [[ "$has_tls" != "true" ]] && continue

    local host crt key
    host="$(jq -r '.hostname' <<< "$row")"
    crt="$(jq -r '.tls.cert' <<< "$row")"
    key="$(jq -r '.tls.key'  <<< "$row")"

    [[ -n "$host" && -n "$crt" && -n "$key" ]] || { err2 "Host entry missing hostname/crt/key."; exit 1; }

    # normalize identity (wildcard "*.example.com" → ".example.com")
    local ident; ident="$(_normalize_identity "$host")"

    # path policy (relative to var/storage; no ../ or ./)
    _reject_bad_relpath "$crt"
    _reject_bad_relpath "$key"

    # extensions
    _check_ext_cert "$crt" || { err2 "Unexpected cert extension for: $crt"; exit 1; }
    _check_ext_key  "$key" || { err2 "Unexpected key extension for: $key"; exit 1; }

    # resolve to absolute inside var/storage (symlinks allowed if they stay inside)
    local crt_abs key_abs
    crt_abs="$(_resolve_inside_storage "$crt")"
    key_abs="$(_resolve_inside_storage "$key")"

    # ownership
    _check_ownership "$crt_abs"
    _check_ownership "$key_abs"

    # quiet perms tighten (do not fail if chmod cannot change)
    [[ -f "$key_abs" ]] && chmod 0400 "$key_abs" >/dev/null 2>&1 || true
    [[ -f "$crt_abs" ]] && chmod 0444 "$crt_abs" >/dev/null 2>&1 || true

    # emit one JSON object per line (combine with jq -s later)
    jq -n --arg id "$ident" --arg crt "$crt_abs" --arg key "$key_abs" '{identity:$id, crt:$crt, key:$key}'
  done < <(jq -c --arg sapi "$sapi" '.http.sapi[$sapi].hosts[]? // empty' "$cfg")
}

svc() {
  local k="${1:?service id required}"
  if [[ -n "${SAPI_SERVICE[$k]:-}" ]]; then
    printf '%s\n' "${SAPI_SERVICE[$k]}"
  else
    printf '%s\n' "$k"
  fi
}