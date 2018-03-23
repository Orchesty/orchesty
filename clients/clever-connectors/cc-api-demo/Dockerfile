FROM dkr.hanaboso.net/hanaboso/symfony3-base:php-7.2

COPY ./ ./

# Meh, you'd better remove the commited config
RUN > app/config/config.local.neon

RUN   chown -R www-data:www-data ./temp ./logs
