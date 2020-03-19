FROM swaggerapi/swagger-ui

ARG URLS
ENV URLS=${URLS}

COPY cron/openapi.yaml /usr/share/nginx/html/docs/cron.yaml
COPY starting-point/openapi.yaml /usr/share/nginx/html/docs/starting-point.yaml
COPY topology-generator/openapi.yaml /usr/share/nginx/html/docs/topology-generator.yaml
