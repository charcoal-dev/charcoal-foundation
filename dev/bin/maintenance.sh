#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

set -euo pipefail

MAINTENANCE_FILE="$ROOT/var/shared/maintenance"

cmd_suspend() {
  local ts; ts=$(date +%s)
  echo "on;$ts" > "$MAINTENANCE_FILE"
  ok "Maintenance mode enabled (on;$ts)."
}

cmd_resume() {
  local ts; ts=$(date +%s)
  echo "off;$ts" > "$MAINTENANCE_FILE"
  ok "Maintenance mode disabled (off;$ts)."
}
