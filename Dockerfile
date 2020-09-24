FROM node:14-alpine
COPY . .
RUN npm i && npm run build

FROM alpine

RUN apk update --no-cache && \
    apk upgrade --no-cache && \
    apk add \
    bash \
    bind-tools \
    nginx \
    procps && \
    rm /etc/nginx/nginx.conf && \
    rm -rf /etc/nginx/conf.d

RUN rm -rf /var/www/html/*
COPY entrypoint.sh /
COPY nginx.conf /etc/nginx
COPY --from=0 /dist /var/www/html/ui

WORKDIR /var/www/html/ui

ENTRYPOINT [ "/entrypoint.sh" ]

CMD nginx -g 'daemon off;'