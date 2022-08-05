#!/bin/sh

if [[ -z "$FRONTEND_URL" || -z "$BACKEND_URL" || -z "$STARTINGPOINT_URL" ]]; then
  echo "ERROR: Some ENV variable(s) not defined! See entrypoint.sh"
  exit 1
fi

sed " \
    s|%frontend-base-url-placeholder%|${FRONTEND_URL}|g; \
    s|%api-base-url-placeholder%|${BACKEND_URL}|g; \
    s|%api-startingpoint-url-placeholder%|${STARTINGPOINT_URL}|g; \
  " -i /var/www/html/js/*.js

exec "$@"
