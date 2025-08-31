#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

#!/usr/bin/env bash
set -euo pipefail
: "${MYSQL_PASSWORD:?}"

OUT="dev/docker/utils/db/init/02-users.sql"

umask 022
mkdir -p "$(dirname "$OUT")"

cat > "$OUT" <<'SQL'
CREATE USER IF NOT EXISTS 'charcoal'@'%' IDENTIFIED WITH mysql_native_password BY '__APP_PW__';
GRANT ALL PRIVILEGES ON `charcoal\_%`.* TO 'charcoal'@'%';
FLUSH PRIVILEGES;
SQL

# substitute password (escape single quotes)
PW_ESC=${MYSQL_PASSWORD//\'/\'\'}
sed -i "s#__APP_PW__#${PW_ESC}#g" "$OUT"

chmod 0644 "$OUT"