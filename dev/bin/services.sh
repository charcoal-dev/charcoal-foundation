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
declare -A SAPI_SERVICE SAPI_TYPE SAPI_DOCROOT SAPI_ENV_B64
declare -A SAPI_DNAT_TLS SAPI_DNAT_INSECURE
SAPI_IDS=(); HTTP_SAPI=(); CLI_SAPI=()

# ...

load_manifest() {
  [[ -f "$MANIFEST" ]] || { warn "Manifest not found: $MANIFEST"; return 0; }

  # require jq
  command -v jq >/dev/null 2>&1 || err "jq is required to load $MANIFEST"

  # reset registries
  SAPI_IDS=(); HTTP_SAPI=(); CLI_SAPI=()
  declare -gA SAPI_TYPE SAPI_SERVICE SAPI_DOCROOT SAPI_ENV_B64 SAPI_DNAT_INSECURE SAPI_DNAT_TLS

  while IFS=$'\t' read -r id typ root dnat_insecure dnat_tls env_b64; do
    [[ -n "$id" ]] || continue

    # normalize basics
    typ="${typ:-cli}"
    root="${root#/}"; root="${root%/}"   # strip leading/trailing slash

    # registries
    SAPI_IDS+=("$id")
    SAPI_TYPE["$id"]="$typ"
    SAPI_SERVICE["$id"]="$id"

    # docroot for ANY sapi that provided one
    if [[ -n "$root" ]]; then
      SAPI_DOCROOT["$id"]="/home/charcoal/${root}"
    fi

    # classify + optional DNAT for HTTP sapis
    if [[ "$typ" == "http" ]]; then
      HTTP_SAPI+=("$id")
      [[ -n "$dnat_insecure" && "$dnat_insecure" != "0" ]] && SAPI_DNAT_INSECURE["$id"]="$dnat_insecure"
      [[ -n "$dnat_tls"      && "$dnat_tls"      != "0" ]] && SAPI_DNAT_TLS["$id"]="$dnat_tls"
    else
      CLI_SAPI+=("$id")
    fi

    # env payload (base64 of JSON)
    SAPI_ENV_B64["$id"]="$env_b64"

  done < <(
    jq -rc '
      .charcoal.sapi[]
      | select(.enabled != false)
      | [
          (.id|tostring),
          (.type // "cli"),
          (.root // ""),
          ((.dnat.insecure // 0)|tostring),
          ((.dnat.tls      // 0)|tostring),
          ((.env // {}) | @json | @base64)
        ]
      | @tsv
    ' "$MANIFEST"
  )

  # verify each enabled HTTP SAPI has config/http/{sapi}.sapi.json
  local _missing=0 _id _f
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

      if [[ "${SAPI_TYPE[$id]}" == "http" ]]; then
        # Emit ports only when at least one DNAT is provided
        if [[ -n "${SAPI_DNAT_INSECURE[$id]:-}" || -n "${SAPI_DNAT_TLS[$id]:-}" ]]; then
          echo "    ports:"
          # HTTP (insecure) host → container 6002
          if [[ -n "${SAPI_DNAT_INSECURE[$id]:-}" ]]; then
            echo "      - \"${SAPI_DNAT_INSECURE[$id]}:6002\""
          fi
          # TLS host → container 6001
          if [[ -n "${SAPI_DNAT_TLS[$id]:-}" ]]; then
            echo "      - \"${SAPI_DNAT_TLS[$id]}:6001\""
          fi
        fi
      fi
    done
  } > "$MAN_OVR"

  # best-effort relative path print
  if command -v realpath >/dev


# usage: collect_tls_inventory_for_sapi "web" | jq -s . > "$ROOT/dev/docker/nginx/web/tls-inventory.json"
# --- Step 2: TLS inventory (validates + emits abs + rel paths) ---
collect_tls_inventory_for_sapi() {
  local sapi="$1"
  local cfg="$ROOT/config/http/${sapi}.sapi.json"
  local storage="$ROOT/var/storage"
  local errors=0

  command -v jq >/dev/null 2>&1 || { err2 "jq is required."; return 1; }
  [[ -f "$cfg" ]]     || { err2 "HTTP config for SAPI \"$sapi\" not found: config/http/${sapi}.sapi.json"; return 1; }
  [[ -d "$storage" ]] || { err2 "Missing var/storage/ directory at $storage."; return 1; }

  _normalize_identity() {
    local host="$1"
    [[ "$host" == \*.* ]] && printf ".%s" "${host#*.}" || printf "%s" "$host"
  }
  _reject_bad_relpath() {
    local rel="$1"
    if [[ "$rel" == /* ]]; then err2 "[$sapi] Absolute path not allowed: $rel (must be relative to var/storage/)"; return 1; fi
    if [[ "$rel" == *"../"* || "$rel" == "./"* ]]; then err2 "[$sapi] Invalid relative path: $rel (no ../ or ./)"; return 1; fi
    return 0
  }
  _resolve_inside_storage() {
    local rel="$1"
    local abs="$storage/$rel"
    [[ -e "$abs" ]] || { err2 "[$sapi] File not found: var/storage/$rel"; return 1; }
    local dir base physdir real
    dir="$(dirname -- "$rel")"; base="$(basename -- "$rel")"
    physdir="$(cd "$storage/$dir" 2>/dev/null && pwd -P)" || { err2 "[$sapi] Failed to resolve: var/storage/$rel"; return 1; }
    real="$physdir/$base"
    case "$real" in "$storage"/*) ;; *) err2 "[$sapi] Path escapes var/storage/: $rel → $real"; return 1;; esac
    [[ -e "$real" ]] || { err2 "[$sapi] File vanished during resolve: var/storage/$rel"; return 1; }
    printf "%s" "$real"
    return 0
  }
  _owner_uid() { stat -c %u -- "$1" 2>/dev/null || stat -f %u -- "$1"; }
  _check_ownership() {
    local p="$1" want have
    want="$(id -u)"; have="$(_owner_uid "$p")" || { err2 "[$sapi] Cannot read owner: $p"; return 1; }
    [[ "$have" == "$want" ]] || { err2 "[$sapi] Owner mismatch: $p (uid $have), expected uid $want"; return 1; }
    return 0
  }
  _check_ext_cert() { [[ "$1" == *.crt || "$1" == *.pem ]]; }
  _check_ext_key()  { [[ "$1" == *.key || "$1" == *.pem ]]; }

  local saw_tls=0
  while IFS= read -r row; do
    local has_tls; has_tls="$(jq -r 'has("tls") and (.tls|type=="object")' <<< "$row")"
    [[ "$has_tls" != "true" ]] && continue
    saw_tls=1

    local host crt key ident crt_abs key_abs ok=1
    host="$(jq -r '.hostname' <<< "$row")"
    crt="$(jq -r '.tls.cert' <<< "$row")"
    key="$(jq -r '.tls.key'  <<< "$row")"

    if [[ -z "$host" || -z "$crt" || -z "$key" ]]; then
      err2 "[$sapi] Host entry missing hostname/cert/key"; errors=$((errors+1)); continue
    fi

    ident="$(_normalize_identity "$host")"

    _check_ext_cert "$crt" || { err2 "[$sapi][$host] Unexpected cert extension: $crt"; ok=0; }
    _check_ext_key  "$key" || { err2 "[$sapi][$host] Unexpected key extension:  $key"; ok=0; }

    _reject_bad_relpath "$crt" || ok=0
    _reject_bad_relpath "$key" || ok=0

    if [[ $ok -eq 1 ]]; then
      crt_abs="$(_resolve_inside_storage "$crt")" || ok=0
      key_abs="$(_resolve_inside_storage "$key")" || ok=0
    fi
    if [[ $ok -eq 1 ]]; then
      _check_ownership "$crt_abs" || ok=0
      _check_ownership "$key_abs" || ok=0
    fi
    if [[ $ok -eq 1 ]]; then
      chmod 0444 "$crt_abs" >/dev/null 2>&1 || true
      chmod 0400 "$key_abs" >/dev/null 2>&1 || true
      # emit abs + rel so renderer can build container paths
      jq -n \
        --arg id "$ident" \
        --arg crt "$crt_abs" \
        --arg key "$key_abs" \
        --arg crt_rel "$crt" \
        --arg key_rel "$key" \
        '{identity:$id, crt:$crt, key:$key, crt_rel:$crt_rel, key_rel:$key_rel}'
    else
      errors=$((errors+1))
      err2 "[$sapi][$host] TLS validation failed; expected cert/key under var/storage/"
    fi
  done < <(jq -c '.hosts[]? // empty' "$cfg")

  if [[ $saw_tls -eq 1 && $errors -gt 0 ]]; then
    err2 "[$sapi] One or more TLS entries invalid. Fix the above issues under var/storage/ and retry."
    return 1
  fi
  return 0
}

svc() {
  local k="${1:?service id required}"
  if [[ -n "${SAPI_SERVICE[$k]:-}" ]]; then
    printf '%s\n' "${SAPI_SERVICE[$k]}"
  else
    printf '%s\n' "$k"
  fi
}