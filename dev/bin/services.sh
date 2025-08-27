#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

MANIFEST="$ROOT/dev/sapi.manifest.json"

# registries
declare -A SAPI_SERVICE SAPI_TYPE
SAPI_IDS=(); HTTP_SAPI=(); CLI_SAPI=()

load_manifest() {
  [[ -f "$MANIFEST" ]] || return 0
  if command -v jq >/dev/null 2>&1; then
    mapfile -t SAPI_IDS < <(jq -r '.interfaces[] | select(.enabled!=false) | .id' "$MANIFEST")
    while IFS=$'\t' read -r id typ svc; do
      SAPI_TYPE["$id"]="$typ"
      SAPI_SERVICE["$id"]="${svc:-$id}"
      [[ "$typ" == "http" ]] && HTTP_SAPI+=("$id") || CLI_SAPI+=("$id")
    done < <(jq -r '.interfaces[] | select(.enabled!=false) | [.id,.type,.service//.id] | @tsv' "$MANIFEST")
  else
    # minimal python fallback
    python3 - <<'PY' "$MANIFEST"
import json,sys
j=json.load(open(sys.argv[1]))
for it in j.get("interfaces",[]):
  if it.get("enabled",True):
    print(it["id"], it.get("type","cli"), it.get("service",it["id"]))
PY
  fi >/dev/null 2>&1 || true
}

write_manifest_overrides() {
  rm -f "$MAN_OVR"; mkdir -p "$(dirname "$MAN_OVR")"
  {
    echo "services:"
    for id in "${SAPI_IDS[@]}"; do
      svc="${SAPI_SERVICE[$id]}"
      echo "  $svc:"
      # environment map
      echo "    environment:"
      # docroot (http only) → enforce from manifest
      if [[ -n "${SAPI_DOCROOT[$id]:-}" ]]; then
        echo "      NGINX_DOCROOT: \"${SAPI_DOCROOT[$id]}\""
      fi
      # additional env from manifest
      if [[ -n "${SAPI_ENV_JSON[$id]:-}" ]]; then
        jq -r 'to_entries[] | "      \(.key): \"\(.value)\""' <<<"${SAPI_ENV_JSON[$id]}"
      fi
      # ports (http only, optional)
      if [[ -n "${SAPI_PORT[$id]:-}" && "${SAPI_TYPE[$id]}" == "http" ]]; then
        echo "    ports:"
        echo "      - \"${SAPI_PORT[$id]}:6000\""
      fi
    done
  } > "$MAN_OVR"
  ok "Manifest overrides → $(realpath --relative-to="$ROOT" "$MAN_OVR")"
}

# svc resolver prefers manifest → dev/bin/services.sh → key
svc() { local k="$1"; [[ -n "${SAPI_SERVICE[$k]:-}" ]] && echo "${SAPI_SERVICE[$k]}" || ( type -t svc >/dev/null 2>&1 && command svc "$k" || echo "$k" ); }