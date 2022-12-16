#GC_PROXY_NAME=
GC_CERT_PREFIX=${GC_CERT_PREFIX:=primary-letsencrypt}

CERT_NAME=cloud${INFIX}.orchesty.io
DOMAINS="*.cloud${INFIX}.orchesty.io *.eu1.cloud${INFIX}.orchesty.io *.tenant-eu1.cloud${INFIX}.orchesty.io *.dummy1.cloud${INFIX}.orchesty.io"

GC_CERT_NAME=${GC_CERT_PREFIX}-$(date +%s)

DOMAIN_ARGS=""
for D in $DOMAINS; do
    DOMAIN_ARGS="$DOMAIN_ARGS -d $D"
done

