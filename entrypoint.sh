#!/bin/bash

set -e

if [ -z "$BACKEND_URL" ]; then
  echo "env var BACKEND_URL not defined"
  exit 1
fi


if [ -z "$FRONTEND_URL" ]; then
  echo "env var FRONTEND_URL not defined"
  exit 1
fi

sed -ri "s|http://url\.to\.backend|$BACKEND_URL|g" -i /var/www/html/ui/bundle.js
sed -ri "s|http://url\.to\.frontend|$FRONTEND_URL|g" -i /var/www/html/ui/bundle.js

upstream_resolver
upstream_resolver --periodic &

exec "$@"
