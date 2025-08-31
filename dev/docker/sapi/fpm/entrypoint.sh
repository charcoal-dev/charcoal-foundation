#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

set -euo pipefail
umask 0027

: "${SERVER_NAME:=_}"
: "${CHARCOAL_SAPI_ROOT:?CHARCOAL_SAPI_ROOT not set (check dev/sapi.manifest.json)}"

mkdir -p /home/charcoal/dev/composer/vendor || true

# vendor guard
[[ -f /home/charcoal/dev/composer/vendor/autoload.php ]] || {
  echo "vendor/ missing. Run: ./charcoal.sh build app" >&2; exit 1; }

# docroot guards
[[ -d "$CHARCOAL_SAPI_ROOT" ]] || { echo "SAPI root not a directory: $CHARCOAL_SAPI_ROOT" >&2; exit 1; }
[[ -r "$CHARCOAL_SAPI_ROOT" ]] || { echo "SAPI root not readable: $CHARCOAL_SAPI_ROOT" >&2; exit 1; }

# render nginx.conf (user-writable location)
if [[ -f /etc/nginx/nginx.template.conf ]]; then
  mkdir -p /home/charcoal/nginx
  envsubst '${CHARCOAL_SAPI_ROOT} ${SERVER_NAME}' \
    < /etc/nginx/nginx.template.conf \
    > /home/charcoal/nginx/nginx.conf
fi

mkdir -p /home/charcoal/var/log \
 /home/charcoal/var/tmp \
 /home/charcoal/var/tmp/semaphore \
 /home/charcoal/var/shared \
 /home/charcoal/var/shared/semaphore \
 /home/charcoal/var/storage

touch /home/charcoal/var/log/error.log \
  /home/charcoal/var/log/access.log

find /home/charcoal/var/log -type f -name "*.log" -exec sh -c '> "$1"' _ {} \; 2>/dev/null || true

install -d -m 0750 \
  /home/charcoal/var/tmp/nginx \
  /home/charcoal/var/tmp/nginx/body \
  /home/charcoal/var/tmp/nginx/proxy \
  /home/charcoal/var/tmp/nginx/fastcgi \
  /home/charcoal/var/tmp/nginx/uwsgi \
  /home/charcoal/var/tmp/nginx/scgi

test -f /home/charcoal/dev/composer/vendor/autoload.php || {
  echo "vendor/ missing. Run: ./charcoal.sh build app"; exit 1; }

exec /usr/bin/supervisord -c /etc/supervisord.conf
