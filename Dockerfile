FROM dkr.hanaboso.net/hanaboso/symfony3-base:php-7.3

RUN apt-get update && \
    apt-get install -y git mc sudo && \
    apt-get clean && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/lib/log/* /var/log/* /tmp/* /var/tmp/*

ENV TERM=xterm

COPY php-local.ini /usr/local/etc/php/conf.d/zz_local.ini
COPY ./ /var/www


ENV APP_ENV=prod
ENV APP_DEBUG=0

RUN rm -rf /var/www/cache && mkdir -p /var/www/var/cache/prod && chmod -R 777 /var/www/var/cache
RUN mkdir -p /var/www/var/log && chmod -R 777 /var/www/var/log

WORKDIR /var/www


RUN mkdir -p /var/log/nginx && \
    ln -s /proc/self/fd/2 /var/log/nginx/error.log

ENV PHP_WEBROOT /var/www/public
CMD [ "php-w-nginx.sh" ]
