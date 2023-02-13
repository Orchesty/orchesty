#!/bin/sh

if [ -n "$BRANDING_PACKAGE_URL" ]; then
  echo "Using branding package: ${BRANDING_PACKAGE_URL}"
  curl $BRANDING_PACKAGE_URL | lz4cat | tar xv -C /var/www/html/
fi

if [[ -z "$FRONTEND_URL" || -z "$BACKEND_URL" || -z "$AUTH_BACKLINK" ]]; then
  echo "ERROR: Some ENV variable(s) not defined! See entrypoint.sh"
  exit 1
fi

sed " \
    s|%frontend-base-url-placeholder%|${FRONTEND_URL}|g; \
    s|%api-base-url-placeholder%|${BACKEND_URL}|g; \
    s|%auth-backlink-placeholder%|${AUTH_BACKLINK}|g; \
  " -i /var/www/html/js/*.js

exec "$@"
