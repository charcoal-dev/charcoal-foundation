#!/bin/bash
source "$(dirname "$0")/include/common.sh" "$0"
source "$SCRIPT_PATH/include/check_root.sh"

if [[ "$1" == "down" || "$1" == "stop" ]]; then
    "$SCRIPT_PATH/engine.sh" stop
fi

cd "$SCRIPT_PATH/../docker" || exit

docker compose -f docker-compose.yml \
               -f docker-compose.full.yml \
               -f docker-compose.aux.yml \
               "$@"

cd - > /dev/null || exit