#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

mkdir -p /home/charcoal/log \
 /home/charcoal/tmp \
 /home/charcoal/tmp/semaphore \
 /home/charcoal/shared \
 /home/charcoal/shared/semaphore \
 /home/charcoal/storage

touch /home/charcoal/log/error.log

test -f /home/charcoal/dev/composer/vendor/autoload.php || {
  echo "vendor/ missing. Run: ./charcoal.sh build app"; exit 1; }

exec /usr/bin/supervisord -c /etc/supervisord.conf