#!/bin/sh
if [ "$CRON_LIMITS" = "1" ]; then
    /var/www/bin/console orchesty:limits:tick --once
fi
