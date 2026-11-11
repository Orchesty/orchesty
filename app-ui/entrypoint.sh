#!/bin/sh

if [ -z "$VITE_BACKEND_URL" ]; then
  echo "ERROR: VITE_BACKEND_URL not defined! See entrypoint.sh"
  exit 1
fi

sed " \
    s|%VITE_BACKEND_URL%|${VITE_BACKEND_URL}|g; \
    s|%VITE_STARTING_POINT_URL%|${VITE_STARTING_POINT_URL:-}|g; \
  " -i /var/www/html/assets/*.js

exec "$@"
