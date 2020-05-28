FROM hanabosocom/php-base:php-7.4-alpine
RUN apk add composer --no-cache && composer global require hirak/prestissimo
COPY . .
RUN cd clients/demo/pipes-api && \
    sed -i -e 's/"symlink": true/"symlink": false/g' composer.json && \
    sed -i -e 's/"symlink": true/"symlink": false/g' composer.lock && \
    composer install -a --no-dev && \
    APP_ENV=prod APP_DEBUG=0 bin/console cache:warmup

FROM hanabosocom/php-base:php-7.4-alpine
ENV APP_DEBUG=0 APP_ENV=prod PHP_FPM_MAX_CHILDREN=10 PHP_FPM_MAX_REQUESTS=500
COPY clients/demo/php-local.ini /usr/local/etc/php/conf.d/zz_local.ini
COPY --from=0 /var/www/clients/demo/pipes-api .
RUN rm -rf html localhost && chown -R www-data:www-data /var/www/var
CMD [ "php-w-nginx.sh" ]
