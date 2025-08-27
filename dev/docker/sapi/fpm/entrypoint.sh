#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

set -euo pipefail
umask 0027

# Defaults (can be overridden via env)
: "${NGINX_LISTEN:=6000}"
: "${SERVER_NAME:=_}"

# Require vendor (dev: run ./charcoal.sh build app)
if [[ ! -f /home/charcoal/vendor/autoload.php ]]; then
  echo "vendor/ missing. Run: ./charcoal.sh build app" >&2
  exit 1
fi

if [[ -f /etc/nginx/nginx.template.conf ]]; then
  export NGINX_LISTEN SERVER_NAME
  envsubst '${NGINX_LISTEN} ${SERVER_NAME}' \
    < /etc/nginx/nginx.template.conf \
    > /etc/nginx/nginx.conf
fi

mkdir -p /home/charcoal/{log,tmp,shared,storage}
exec /usr/bin/supervisord -c /etc/supervisord.conf