FROM hanabosocom/php-base:php-7.4-alpine
RUN apk add composer --no-cache && composer global require hirak/prestissimo
COPY . .
RUN cd pf-bundles && \
    sed -i -e 's/"symlink": true/"symlink": false/g' composer.json && \
    sed -i -e 's/"symlink": true/"symlink": false/g' composer.lock && \
    composer install -a --no-dev && \
    APP_ENV=prod APP_DEBUG=0 MONGODB_DSN=mongodb://mongo:27017 MONGODB_DB=pipes bin/console cache:warmup

FROM hanabosocom/php-base:php-7.4-alpine
ENV APP_DEBUG=0 APP_ENV=prod PHP_FPM_MAX_CHILDREN=10 PHP_FPM_MAX_REQUESTS=500
COPY pf-bundles/php-local.ini /usr/local/etc/php/conf.d/zz_local.ini
COPY --from=0 /var/www/pf-bundles .
RUN rm -rf html localhost
RUN mkdir -p /var/lib/nginx/tmp/client_body && \
    chmod -R 774 /var/www/var /var/lib/nginx && \
    chown -R www-data /var/www/var /var/lib/nginx

CMD [ "php-w-nginx.sh" ]
