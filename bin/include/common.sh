#!/bin/bash

# User and Group Variables
HOST_UID=`id -u`
HOST_GID=`id -g`

# Path Variables
MAIN_SCRIPT=$(realpath "$1")
SCRIPT_PATH=$(dirname "$MAIN_SCRIPT")

# ANSI Escape Sequence
RED="\033[31m"
BLUE="\033[34m"
MAGENTA="\033[35m"
GREEN="\033[32m"
GRAY="\033[90m"
YELLOW="\033[33m"
CYAN="\033[36m"
RESET="\033[0m"