#!/bin/bash
cd /home/charcoal
composer update
chown -R charcoal:charcoal /home/charcoal/vendor
cd ~
/usr/bin/supervisord -c /etc/supervisord.conf
