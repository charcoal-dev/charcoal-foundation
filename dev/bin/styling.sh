#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

# --- styling ---
ICON_OK="${ICON_OK:-✅}"; ICON_INFO="${ICON_INFO:-💡}"; ICON_WARN="${ICON_WARN:-⚠️}"; ICON_ERR="${ICON_ERR:-❌}"
#ICON_OK="${ICON_OK:-✔}"; ICON_INFO="${ICON_INFO:-💡}"; ICON_WARN="${ICON_WARN:-⚠}"; ICON_ERR="${ICON_ERR:-✖}"

# ---- Color enable/disable ----
if [[ -t 1 && "${NO_COLOR:-0}" != "1" ]]; then
  CLR_RESET=$'\e[0m'
  CLR_OK=$'\e[32m'; CLR_INFO=$'\e[36m'; CLR_WARN=$'\e[33m'; CLR_ERR=$'\e[31m'
  declare -A CLR=(
    [reset]=$'\e[0m' [red]=$'\e[31m' [green]=$'\e[32m' [yellow]=$'\e[33m'
    [blue]=$'\e[34m' [magenta]=$'\e[35m' [cyan]=$'\e[36m' [grey]=$'\e[90m' [bold]=$'\e[1m'
  )
else
  CLR_RESET=""; CLR_OK=""; CLR_INFO=""; CLR_WARN=""; CLR_ERR="";
  declare -A CLR=([reset]="" [red]="" [green]="" [yellow]="" [blue]="" [magenta]="" [cyan]="" [grey]="" [bold]="")
fi

# ---- Inline color formatter: replaces {color} ... {/} ----
colorize() {
  local text="$*"
  # Replace {/} → actual reset sequence
  text="${text//\{\/\}/${CLR[reset]}}"

  # Apply other tags
  local order=(red green yellow blue magenta cyan grey bold reset)
  local k
  for k in "${order[@]}"; do
    text="${text//\{$k\}/${CLR[$k]}}"
  done

  printf '%s' "$text"
}

# ---- Core emitter: usage: _emit <icon> <color> [-n] <message...> ----
_emit() {
  local icon="$1" color="$2"; shift 2
  local newline=1
  if [[ "${1-}" == "-n" ]]; then newline=0; shift; fi

  local msg; msg="$(colorize "$*")"
  local prefix="${CLR[reset]}$color"

  if [[ -n "$icon" ]]; then
    # always one space after icon
    msg="$icon  $msg"
  fi

  if (( newline )); then
    printf '%s%s%s\n' "$prefix" "$msg" "$CLR_RESET"
  else
    printf '%s%s%s'   "$prefix" "$msg" "$CLR_RESET"
  fi
}

ok()     { _emit "$ICON_OK"   "$CLR_OK"   "$@"; }
info()   { _emit "$ICON_INFO" "$CLR_INFO" "$@"; }
warn()   { _emit "$ICON_WARN" "$CLR_WARN" "$@"; }
err2() { _emit "$ICON_ERR" "$CLR_WARN" "$@"; }
err() { { _emit "$ICON_ERR" "$CLR_ERR" "$@"; } 1>&2; exit 1; }
normal() { _emit ""           "${CLR[reset]:-$CLR_RESET}" "$@"; }
blank(){ printf '%s\n' "${CLR[reset]:-$CLR_RESET}"; }