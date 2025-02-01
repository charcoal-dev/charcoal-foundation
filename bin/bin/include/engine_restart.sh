#!/bin/bash
cd "$SCRIPT_PATH/../docker" || exit
docker compose -f docker-compose.yml exec engine supervisorctl restart all