#!/bin/bash
cd "$SCRIPT_PATH/../docker" || exit

docker_status=$(docker compose -f docker-compose.yml ps -q engine)

cd - > /dev/null || exit

if [ -z "$docker_status" ]; then
  echo -e "\033[90mEngine service is NOT running\e[0m"
  exit 0
fi
