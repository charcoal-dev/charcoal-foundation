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
touch /home/charcoal/var/log/composer.log
touch /home/charcoal/var/log/build.out.log

exec /usr/bin/supervisord -c /etc/supervisord.conf