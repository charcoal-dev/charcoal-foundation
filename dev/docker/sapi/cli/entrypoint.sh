#!/usr/bin/env bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

test -f /home/charcoal/vendor/autoload.php || {
  echo "vendor/ missing. Run: ./charcoal.sh build app"; exit 1; }

exec /usr/bin/supervisord -c /etc/supervisord.conf