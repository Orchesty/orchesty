#!/bin/sh

if [ -z "$VITE_BACKEND_URL" ]; then
  echo "ERROR: VITE_BACKEND_URL not defined! See entrypoint.sh"
  exit 1
fi

sed " \
    s|%VITE_BACKEND_URL%|${VITE_BACKEND_URL}|g; \
    s|%VITE_NOTIFIER_URL%|${VITE_NOTIFIER_URL:-}|g; \
    s|%VITE_TITLE%|${VITE_TITLE:-}|g; \
    s|%VITE_AUTH0_DOMAIN%|${VITE_AUTH0_DOMAIN:-}|g; \
    s|%VITE_AUTH0_CLIENT_ID%|${VITE_AUTH0_CLIENT_ID:-}|g; \
    s|%VITE_AUTH0_AUDIENCE%|${VITE_AUTH0_AUDIENCE:-}|g; \
  " -i /var/www/html/assets/*.js

exec "$@"
