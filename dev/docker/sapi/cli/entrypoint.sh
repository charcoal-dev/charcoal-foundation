#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

mkdir -p /home/charcoal/var/log \
 /home/charcoal/var/tmp \
 /home/charcoal/var/tmp/semaphore \
 /home/charcoal/var/shared \
 /home/charcoal/var/shared/semaphore \
 /home/charcoal/var/storage

touch /home/charcoal/var/log/error.log

find /home/charcoal/var/log -type f -name "*.log" -exec sh -c '> "$1"' _ {} \; 2>/dev/null || true

test -f /home/charcoal/dev/composer/vendor/autoload.php || {
  echo "vendor/ missing. Run: ./charcoal.sh build app"; exit 1; }

exec /usr/bin/supervisord -c /etc/supervisord.conf