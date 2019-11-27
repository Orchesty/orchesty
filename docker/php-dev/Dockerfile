FROM hanabosocom/php-base:php-7.3-alpine

# PHP
RUN apk update && apk upgrade && apk add --no-cache \
    autoconf build-base g++ && \
    pecl install pcov && \
    pecl install xdebug && \
    docker-php-ext-enable pcov && \
    apk del autoconf build-base g++ && \
    rm -rf /var/cache/apk/* /var/log/* /tmp/*

# Container has the same ssh as the host machine
ENV SSH_AUTH_SOCK=/tmp/.ssh-auth-sock
LABEL com.ensody.ssh-agent-inject=true

ENV TERM=xterm

COPY php-local.ini /usr/local/etc/php/conf.d/zz_local.ini

# Run PHP FPM as the dev user
RUN sed -i "s/^user =.*/user = dev/; s/^group = .*/group = dev/;" /usr/local/etc/php-fpm.d/www.conf

COPY entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]

WORKDIR /var/www

CMD su-exec root php-xdebug-w-nginx.sh
