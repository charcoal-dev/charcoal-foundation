#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

set -euo pipefail
: "${MYSQL_PASSWORD:?}"

cat > /docker-entrypoint-initdb.d/02-users.sql <<'SQL'
CREATE USER IF NOT EXISTS 'charcoal'@'%' IDENTIFIED BY '__APP_PW__';
GRANT ALL PRIVILEGES ON charcoal\_%.* TO 'charcoal'@'%';
FLUSH PRIVILEGES;
SQL

# substitute password safely
sed -i "s#__APP_PW__#${MYSQL_PASSWORD//\//\\/}#g" /docker-entrypoint-initdb.d/02-users.sql