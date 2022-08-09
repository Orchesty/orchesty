FROM swaggerapi/swagger-ui

ARG URLS
ENV URLS=${URLS}

COPY cron/openapi.yaml /usr/share/nginx/html/docs/cron.yaml
COPY lib-python/xml_parser/openapi.yaml /usr/share/nginx/html/docs/xml-parser.yaml
COPY limiter/openapi.yaml /usr/share/nginx/html/docs/limiter.yaml
COPY notification-sender/openapi.yaml /usr/share/nginx/html/docs/notification-sender.yaml
COPY pf-bundles/openapi.yaml /usr/share/nginx/html/docs/pipes-framework.yaml
COPY pipes-php-sdk/openapi.yaml /usr/share/nginx/html/docs/php-sdk.yaml
COPY portal/openapi.yaml /usr/share/nginx/html/docs/portal.yaml
COPY starting-point/openapi.yaml /usr/share/nginx/html/docs/starting-point.yaml
COPY topology-generator/openapi.yaml /usr/share/nginx/html/docs/topology-generator.yaml
