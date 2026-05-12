#GC_PROXY_NAME=
GC_CERT_PREFIX=${GC_CERT_PREFIX:=primary-letsencrypt}

CERT_NAME=cloud${INFIX}.orchesty.io
DOMAINS="*.cloud${INFIX}.orchesty.io *.eu1.cloud${INFIX}.orchesty.io *.tenant-eu1.cloud${INFIX}.orchesty.io orchesty.io *.orchesty.io orchesty.com *.orchesty.com orchesti.io *.orchesti.io orchesti.com *.orchesti.com applinth.io *.applinth.io applinth.com *.applinth.com orchesty-solutions.com *.orchesty-solutions.com orchesty-solutions.cz *.orchesty-solutions.cz orchestysolutions.com *.orchestysolutions.com orchestysolutions.com *.orchestysolutions.cz"

GC_CERT_NAME=${GC_CERT_PREFIX}-$(date +%s)

DOMAIN_ARGS=""
for D in $DOMAINS; do
    DOMAIN_ARGS="$DOMAIN_ARGS -d $D"
done

echo "DOMAINS: $DOMAINS"
