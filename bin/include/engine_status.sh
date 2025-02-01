#!/bin/bash
cd "$SCRIPT_PATH/../docker" || exit

echo -e "Configured processes: ${BLUE}[${services[*]}]${RESET}"

for service in "${services[@]}"; do
  service_pid=$(docker compose -f docker-compose.yml exec engine ps aux | grep "[${service:0:1}]${service:1}" | grep 'charcoal.php' | awk '{print $2}')

  if [ -n "$service_pid" ]; then
    echo -e "${MAGENTA}${service}${RESET}: ${GREEN}Running${RESET} (PID: ${YELLOW}$service_pid${RESET})"
  else
    echo -e "${MAGENTA}${service}${RESET}: ${GRAY}Not Running${RESET}"
  fi
done