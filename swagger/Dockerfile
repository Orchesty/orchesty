FROM swaggerapi/swagger-ui

ARG URLS
ENV URLS=${URLS}

COPY app-store/openapi.yaml /usr/share/nginx/html/docs/app-store.yaml
COPY cron/openapi.yaml /usr/share/nginx/html/docs/cron.yaml
COPY lib-python/xml_parser/openapi.yaml /usr/share/nginx/html/docs/xml-parser.yaml
COPY limiter/openapi.yaml /usr/share/nginx/html/docs/limiter.yaml
COPY notification-sender/openapi.yaml /usr/share/nginx/html/docs/notification-sender.yaml
COPY starting-point/openapi.yaml /usr/share/nginx/html/docs/starting-point.yaml
COPY topology-generator/openapi.yaml /usr/share/nginx/html/docs/topology-generator.yaml
