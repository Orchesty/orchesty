FROM hanabosocom/php-base:php-8.1-alpine

COPY . .
RUN cd status-service && \
    composer install -a --no-dev && \
    APP_ENV=prod APP_DEBUG=0 RABBITMQ_DSN=amqp://rabbitmq:5672/ \
    METRICS_SERVICE=mongo \
    bin/console cache:warmup

FROM hanabosocom/php-base:php-8.1-alpine

ENV APP_DEBUG=0 APP_ENV=prod PHP_FPM_MAX_CHILDREN=10 PHP_FPM_MAX_REQUESTS=500
COPY status-service/php-local.ini /usr/local/etc/php/conf.d/zz_local.ini
COPY --from=0 /var/www/status-service .
RUN rm -rf html localhost
RUN mkdir -p /var/lib/nginx/tmp/client_body && \
    chmod -R 774 /var/www/var /var/lib/nginx && \
    chown -R www-data /var/www/var /var/lib/nginx

CMD bin/console rabbit_mq:consumer:status-service
