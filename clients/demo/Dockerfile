FROM hanabosocom/php-base:php-8.1-alpine
COPY . .
RUN cd clients/demo/pipes-api && \
    sed -i -e 's/"symlink": true/"symlink": false/g' composer.json && \
    sed -i -e 's/"symlink": true/"symlink": false/g' composer.lock && \
    composer install -a --no-dev --ignore-platform-reqs && \
    APP_ENV=prod APP_DEBUG=0 BACKEND_DSN=backend STARTING_POINT_DSN=start RABBIT_DSN=rabbit \
    bin/console cache:warmup

FROM hanabosocom/php-base:php-8.1-alpine
ENV APP_DEBUG=0 APP_ENV=prod PHP_FPM_MAX_CHILDREN=10 PHP_FPM_MAX_REQUESTS=500
COPY clients/demo/php-local.ini /usr/local/etc/php/conf.d/zz_local.ini
COPY --from=0 /var/www/clients/demo/pipes-api .
RUN rm -rf html localhost && chown -R www-data:www-data /var/www/var
CMD [ "php-w-nginx.sh" ]
