#!/bin/bash
source "$(dirname "$0")/include/common.sh" "$0"
source "$SCRIPT_PATH/include/check_root.sh"

cd $SCRIPT_PATH/../

if [ -z "$1" ];
  then
  echo -e "\e[33mWhich docker configuration to build?\e[0m (\e[36mfull\e[0m|\e[36mlite\e[0m)"
  read -p "configuration: " DOCKER_COMPOSE
fi

APP_CONFIG_DIR="config/"
if [[ ! -d "$APP_CONFIG_DIR" ]]; then
  echo -e "\e[31mERROR:\e[0m Config directory \"\e[36m${APP_CONFIG_DIR}\e[0m\" does not exist";
  exit
fi

APP_TMP_DIR="tmp/"
if [[ ! -d "$APP_TMP_DIR" ]]; then
  mkdir "tmp"
  chmod -R 777 tmp
fi

DOCKER_ENV_FILE=".env";
if [[ ! -f "$DOCKER_ENV_FILE" ]]; then
  echo -e "\e[31mERROR:\e[0m Environment configuration file \"\e[36m${DOCKER_ENV_FILE}\e[0m\" does not exist";
  exit
fi

if [[ -z "$DOCKER_COMPOSE" ]]; then
  DOCKER_COMPOSE=$1
fi

./bin/services.sh down

find log/crontab -name '*.log' -exec truncate -s 0 {} \;
find log/engine -name '*.log' -exec truncate -s 0 {} \;
find log/web -name '*.log' -exec truncate -s 0 {} \;

touch log/engine/error.log
touch log/web/access.log
touch log/web/error.log

rm -rf docker/containers/engine/vendor
rm -rf docker/containers/web/vendor
rm charcoal.sh

cp .env docker/.env
cd docker/
DOCKER_COMPOSE_FILE="docker-compose.$DOCKER_COMPOSE.yml";

if [[ ! -f "$DOCKER_COMPOSE_FILE" ]]; then
  cd ../
  echo -e "\e[31mERROR:\e[0m Docker compose file \"\e[36m${DOCKER_COMPOSE}\e[0m\" does not exist";
  exit
fi

docker compose -f docker-compose.yml -f ${DOCKER_COMPOSE_FILE} build --build-arg HOST_UID=${HOST_UID} --build-arg HOST_GID=${HOST_GID}
docker compose -f docker-compose.yml -f ${DOCKER_COMPOSE_FILE} up -d

cd ../
echo -e '#!/bin/bash
DOCKER_FLAG="-it"
TTY="yes"

while getopts ":T" opt; do
 case ${opt} in
   T )
     DOCKER_FLAG="-T"
     TTY="no"
     ;;
   \? )
     echo "Usage: $0 [-T]"
     exit 1
     ;;
 esac
done

shift $((OPTIND - 1))

SCRIPT=$(realpath "$0")
SCRIPT_PATH=$(dirname "$SCRIPT")

if ! cd "$SCRIPT_PATH/docker"; then
 echo "Error: Could not change to directory $SCRIPT_PATH/docker"
 exit 1
fi

DOCKER_STATUS=$(docker compose -f docker-compose.yml ps -q engine)
if [ -z "$DOCKER_STATUS" ]; then
 echo -e "\033[90mEngine service is NOT running\e[0m"
 exit 1
fi

EXEC_CMD="/home/charcoal/charcoal.sh $@ --ansi -tty=$TTY"
docker compose exec $DOCKER_FLAG engine /bin/su charcoal -c "/bin/bash $EXEC_CMD"

cd - &> /dev/null' > charcoal.sh

chmod +x charcoal.sh
./bin/services.sh ps

echo -e "\e[33m";
echo -n "Waiting for services to come online ";

SERVICE_ENGINE_ID=`./bin/services.sh ps -q engine`
SERVICE_WEB_ID=`./bin/services.sh ps -q web`

while [ "`docker inspect -f {{.State.Running}} $SERVICE_ENGINE_ID`" != "true" ]; do     sleep 1; done
echo -n ".";
while [ "`docker inspect -f {{.State.Running}} $SERVICE_WEB_ID`" != "true" ]; do     sleep 1; done
echo -n ".";
echo -e "\e[0m";
echo -e "";

echo -en "Bootstrapping Charcoal app in \e[35m\e[7m 10 \e[0m";

# Countdown
for i in {10..0}; do
  echo -en "\e[4D\e[J\e[35m\e[7m "
  if [ $i -lt 10 ]; then
    echo -en "0$i"
  else
    echo -en "$i"
  fi

    echo -en " \e[0m";
  sleep 1
done

echo -e "\e[8D\e[J\e[0m...";
docker compose exec -T engine /bin/su charcoal -c "php -f /home/charcoal/build.php"

if [ "$2" = "dev" ]; then
    ./bin/services.sh up -d phpmyadmin
fi

#./charcoal.sh install
#./charcoal.sh default_config
