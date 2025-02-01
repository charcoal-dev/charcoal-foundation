#!/bin/bash
cd "$SCRIPT_PATH/../docker" || exit

echo -e "${RED}Shutdown sequence initiated...${RESET}"

running_services=()

echo -e "Configured running processes: ${BLUE}[${services[*]}]${RESET}"

for service in "${services[@]}"; do
  service_pid=$(docker compose -f docker-compose.yml exec engine ps aux | grep "[${service:0:1}]${service:1}" | grep 'charcoal.php' | awk '{print $2}')

  if [ -n "$service_pid" ]; then
    echo -e "${MAGENTA}${service}${RESET}: ${GREEN}Running${RESET} (PID: ${YELLOW}$service_pid${RESET})"
    running_services+=("$service")
    docker compose -f docker-compose.yml exec engine kill -SIGTERM "$service_pid"
  else
    echo -e "${MAGENTA}${service}${RESET}: ${GRAY}Not Running${RESET}"
  fi
done

if [ ${#running_services[@]} -eq 0 ]; then
  exit 0
fi

sleep 2

total_iterations=$((20 * ${#running_services[@]}))
for i in $(seq 1 $total_iterations); do
  all_stopped=true

  updated_running_services=()

  for service in "${running_services[@]}"; do
    service_pid=$(docker compose -f docker-compose.yml exec engine ps aux | grep "[${service:0:1}]${service:1}" | grep 'charcoal.php' | awk '{print $2}')

    if [ -n "$service_pid" ]; then
      docker compose -f docker-compose.yml exec engine kill -SIGTERM "$service_pid"
      all_stopped=false
      updated_running_services+=("$service")
    else
      echo -e "${MAGENTA}${service}${RESET}: ${GRAY}Stopped${RESET}"
    fi
  done

  running_services=("${updated_running_services[@]}")

  if $all_stopped; then
    echo -e "${GREEN}Shutdown sequence SUCCESSFUL${RESET}"
    break
  fi

  sleep 2
done

if ! $all_stopped; then
  echo -e "${RED}Shutdown sequence FAILED after $total_iterations attempts${RESET}"
  exit 0
fi