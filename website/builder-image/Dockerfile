FROM node:13-stretch-slim

RUN apt-get update && \
    apt-get install -y wget gnupg ca-certificates apt-transport-https && \
    wget -q https://packages.sury.org/php/apt.gpg -O- | apt-key add - && \
    echo "deb https://packages.sury.org/php/ stretch main" | tee /etc/apt/sources.list.d/php.list

RUN apt-get update && \
    apt-get install -y \
      php7.2-cli \
      php7.2-curl \
      php7.2-xml \
      php7.2-zip \
      composer \
      awscli && \
    rm -rf /var/lib/* /var/cache/apt/*

RUN composer global require jms/serializer:~1.7.1 cvuorinen/phpdoc-markdown-public
RUN chmod 0755 /root & \
    chmod -R 0777 /root/.composer/vendor/phpdocumentor/phpdocumentor/data

ENV HOME=/tmp PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:/root/.composer/vendor/bin
