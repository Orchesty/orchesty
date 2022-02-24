FROM hanabosocom/php-base:php-8.1-alpine

COPY . .
RUN cd pf-bundles && \
    sed -i -e 's/"symlink": true/"symlink": false/g' composer.json && \
    sed -i -e 's/"symlink": true/"symlink": false/g' composer.lock && \
    composer install -a --no-dev && \
    APP_ENV=prod APP_DEBUG=0 NOTIFICATION_DSN=notification-sender-api RABBITMQ_DSN=amqp://rabbitmq:5672/ \
    MONGODB_DSN=mongodb://mongo:27017 MONGODB_DB=pipes \
    METRICS_DSN=mongodb://mongo:27017 METRICS_DB=metrics METRICS_ODM_DSN=mongodb://mongo:27017 METRICS_SERVICE=mongo \
    CRON_DSN=cron-api:8080 MONOLITH_API_DSN=php-sdk \
    TOPOLOGY_API_DSN=topology-api:8080 WORKER_DEFAULT_PORT=8008 STARTING_POINT_DSN=starting-point:8080 \
    FRONTEND_DSN=frontend BACKEND_DSN=backend \
    ELASTICSEARCH_DSN=elastic JWT_KEY=key \
    bin/console cache:warmup

FROM hanabosocom/php-base:php-8.1-alpine

ENV APP_DEBUG=0 APP_ENV=prod PHP_FPM_MAX_CHILDREN=10 PHP_FPM_MAX_REQUESTS=500
COPY pf-bundles/php-local.ini /usr/local/etc/php/conf.d/zz_local.ini
COPY --from=0 /var/www/pf-bundles .
RUN rm -rf html localhost
RUN mkdir -p /var/lib/nginx/tmp/client_body && \
    chmod -R 774 /var/www/var /var/lib/nginx && \
    chown -R www-data /var/www/var /var/lib/nginx

CMD [ "php-w-nginx.sh" ]
