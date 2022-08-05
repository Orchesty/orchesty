#!/bin/sh

 if [[ -z "$API_BASE_URL" ]]; then
   echo "ERROR: Some ENV variable(s) not defined! See entrypoint.sh"
   exit 1
 fi

 sed " \
     s|%api-base-url-placeholder%|${API_BASE_URL}|g; \
   " -i /var/www/html/js/*.js

exec "$@"
