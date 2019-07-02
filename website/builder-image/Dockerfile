FROM node:12-stretch-slim

RUN apt-get update && \
    apt-get install -y \
      php7.0-cli \
      php7.0-curl \
      php7.0-xml \
      php7.0-zip \
      composer && \
    rm -rf /var/lib/* /var/cache/apt/*

RUN composer global require jms/serializer:~1.7.1 cvuorinen/phpdoc-markdown-public
RUN chmod 0755 /root & \
    chmod -R 0777 /root/.composer/vendor/phpdocumentor/phpdocumentor/data

ENV HOME=/tmp PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:/root/.composer/vendor/bin
