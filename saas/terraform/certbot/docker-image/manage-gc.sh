#!/bin/bash

set -e

. /config.sh

set -x 

gcloud compute ssl-certificates create $GC_CERT_NAME --global \
    --certificate=/certbot/live/${CERT_NAME}/fullchain.pem \
    --private-key=/certbot/live/${CERT_NAME}/privkey.pem

if [ -z "$SKIP_DEPLOYMENT" ]; then
    gcloud compute target-https-proxies update $GC_PROXY_NAME --ssl-certificates=$GC_CERT_NAME
fi
