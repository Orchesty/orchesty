if [ "$CRON_LIMITS" == "1" ]; then
 	sh -c "/var/www/bin/console orchesty:limits:tick --once"
else
fi
