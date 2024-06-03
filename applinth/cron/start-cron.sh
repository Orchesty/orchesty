#!/bin/bash

crond /var/spool/cron/crontabs -f -L /var/log/cron/cron.log
exec "/usr/local/bin/php-w-nginx.sh"
