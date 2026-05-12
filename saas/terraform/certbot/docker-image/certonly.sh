#!/bin/bash

set -e

. /config.sh

CERT_SUM1=$(md5sum live/${CERT_NAME}/cert.pem || true)

echo "Generating..."
certbot certonly \
    --agree-tos \
    --email fakturace-ops@hanaboso.com \
    --non-interactive \
    --preferred-challenges dns \
    --dns-google \
    --cert-name $CERT_NAME \
    $DOMAIN_ARGS \
    $CERTBOT_EXTRA_ARGS \
    #--dns-google-credentials <(echo $GOOGLE_SA_KEY) \

CERT_SUM2=$(md5sum live/${CERT_NAME}/cert.pem)

if [ "$CERT_SUM1" != "$CERT_SUM2" ]; then
    /manage-gc.sh
fi
