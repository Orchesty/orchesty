#!/bin/sh

 if [[ -z "$API_BASE_URL" || -z "$FIREBASE_API_KEY" || -z "$FIREBASE_AUTH_DOMAIN" ]]; then
   echo "ERROR: Some ENV variable(s) not defined! See entrypoint.sh"
   exit 1
 fi

 sed " \
     s|%api-base-url-placeholder%|${API_BASE_URL}|g; \
     s|%firebase-api-key-placeholder%|${FIREBASE_API_KEY}|g; \
     s|%firebase-auth-domain-placeholder%|${FIREBASE_AUTH_DOMAIN}|g; \
   " -i /var/www/html/js/*.js

exec "$@"
