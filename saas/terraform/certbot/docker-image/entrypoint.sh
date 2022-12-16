#!/bin/bash

set -e

#BUCKET=

mkdir -p /certbot

if [ -n "$GOOGLE_SA_KEY" ]; then
    export CLOUDSDK_CORE_PROJECT=$(echo "$GOOGLE_SA_KEY" | jq -r '.project_id')
    gcloud auth activate-service-account --key-file <(echo $GOOGLE_SA_KEY)
    gcsfuse --key-file <(echo $GOOGLE_SA_KEY) --debug_gcs --debug_fuse $BUCKET /certbot
else
    gcsfuse --debug_gcs --debug_fuse $BUCKET /certbot
fi

cd /certbot

exec "$@"
