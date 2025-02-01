#!/bin/bash
source "$(dirname "$0")/include/common.sh" "$0"
source "$SCRIPT_PATH/include/check_root.sh"
source "$SCRIPT_PATH/include/check_engine.sh"

SCRIPT_ARG="Use ( ${CYAN}status${RESET} | ${CYAN}restart${RESET} | ${CYAN}stop${RESET} )"
services=()

if [ -z "$1" ]; then
    echo -e "${RED}Error: No argument provided.${RESET} $SCRIPT_ARG"
    exit 1
fi

case "$1" in
    status)
        source "$SCRIPT_PATH/include/engine_status.sh"
        ;;
    restart)
        source "$SCRIPT_PATH/include/engine_restart.sh"
        ;;
    stop)
        source "$SCRIPT_PATH/include/engine_stop.sh"
        ;;
    *)
        echo -e "${RED}Error: Invalid argument.${RESET} $SCRIPT_ARG"
        exit 1
        ;;
esac