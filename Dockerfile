FROM debian:stretch

# TODO: better cleanup
RUN apt-get update && \
    apt-get install -y --force-yes nginx-extras procps bind9-host && \
    apt-get clean

ENV PHP_APP_INDEX index.php
ENV PHP_WEBROOT /srv/app

RUN rm /etc/nginx/nginx.conf
COPY nginx.conf.tpl /etc/nginx/
COPY upstream_resolver /usr/sbin

WORKDIR /var/www/html
COPY dist/ ui/

COPY entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]

CMD nginx -g 'daemon off;'
