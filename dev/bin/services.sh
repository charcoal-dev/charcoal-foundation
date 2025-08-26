#!/usr/bin/env bash
declare -A SVC=(
  [engine]=engine
  [web]=web
  [db]=db
  [redis]=redis
)

# Helper: resolve a key to service name (falls back to key)
svc() { local k="$1"; echo "${SVC[$k]:-$k}"; }