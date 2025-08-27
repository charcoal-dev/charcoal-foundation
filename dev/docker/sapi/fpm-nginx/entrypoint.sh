#!/bin/bash
#
# Part of the "charcoal-dev/charcoal-foundation" package.
# @link https://github.com/charcoal-dev/charcoal-foundation
#

export ESC='$'
envsubst < /etc/nginx/nginx.template.conf > /etc/nginx/nginx.conf
cd /home/charcoal
composer update
chown -R charcoal:charcoal /home/charcoal/vendor
cd ~
/usr/bin/supervisord -c /etc/supervisord.conf
