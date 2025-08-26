#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

declare -A SVC=(
  [engine]=engine
  [web]=web
  [db]=db
  [redis]=redis
)

# Helper: resolve a key to service name (falls back to key)
svc() { local k="$1"; echo "${SVC[$k]:-$k}"; }