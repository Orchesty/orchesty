#!/bin/bash

set -e

if [ -z "$BACKEND_URL" ]; then
  echo "env var BACKEND_URL not defined"
  exit 1
fi

sed -ri "s|http://url\.to\.api\.gateway|$BACKEND_URL|g" -i /var/www/html/ui/bundle.js

exec "$@"
