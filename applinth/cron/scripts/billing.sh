if [ "$CRON_BILLING" == "1" ]; then
 	sh -c "/var/www/bin/console usage_stats:send-events"
else
fi
