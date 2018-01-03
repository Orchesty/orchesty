FROM dkr.hanaboso.net/hanaboso/symfony3-base:latest

COPY ./ ./

# Meh, you'd better remove the commited config
RUN > app/config/config.local.neon

RUN   chown -R www-data:www-data ./temp ./logs
