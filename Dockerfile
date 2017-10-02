FROM debian:stretch

# TODO: better cleanup
RUN apt-get update && \
    apt-get install -y --force-yes nginx-extras && \
    apt-get clean

ENV PHP_APP_INDEX app.php
ENV PHP_WEBROOT /srv/app

COPY nginx.conf /etc/nginx/

WORKDIR /var/www/html
COPY dist/ ui/

COPY entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]

CMD nginx -g 'daemon off;'
