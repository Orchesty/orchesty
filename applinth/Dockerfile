FROM hanabosocom/php-base:php-8.3-alpine

COPY . .
RUN cd applinth && \
    sed -i -e 's/"symlink": true/"symlink": false/g' composer.json && \
    sed -i -e 's/"symlink": true/"symlink": false/g' composer.lock && \
    composer install -a --no-dev && \
    APP_ENV=prod APP_DEBUG=0 RABBITMQ_DSN=amqp://rabbitmq:5672/ \
    MONGODB_DSN=mongodb://mongo:27017 MONGODB_DB=pipes \
    METRICS_DSN=mongodb://mongo:27017 METRICS_DB=metrics \
    CRON_DSN=cron-api:8080 \
    TOPOLOGY_API_DSN=topology-api:8080 STARTING_POINT_URL=starting-point:8080 \
    FRONTEND_URL=frontend BACKEND_URL=backend JWT_KEY=key \
    ALPHA_INSTANCE_ID=applinth JWE_PRIVATE_KEY=1 \
    bin/console cache:warmup

FROM hanabosocom/php-base:php-8.3-alpine

ENV APP_DEBUG=0 APP_ENV=prod PHP_FPM_MAX_CHILDREN=10 PHP_FPM_MAX_REQUESTS=500
ENV INSTALLER_DIRS='["/var/www/topology"]'
COPY applinth/php-local.ini /usr/local/etc/php/conf.d/zz_local.ini
COPY --from=0 /var/www/applinth .
RUN rm -rf html localhost || true
RUN mkdir -p /var/lib/nginx/tmp/client_body && \
    chmod -R 774 /var/www/var /var/lib/nginx && \
    chown -R www-data /var/www/var /var/lib/nginx

#  Prepares crontab folder & it's logs
RUN mkdir -p /var/log/cron \
    && mkdir -m 0644 -p /var/spool/cron/crontabs \
    && touch /var/log/cron/cron.log
COPY applinth/cron/start-cron.sh /usr/local/bin/start-cron.sh
COPY applinth/cron/scripts/ /etc/cronscripts/
RUN chmod 777 /usr/local/bin/start-cron.sh
RUN chmod 777 /etc/cronscripts -R
RUN /usr/bin/crontab /var/www/cron/crontab.txt

CMD [ "start-cron.sh" ]
