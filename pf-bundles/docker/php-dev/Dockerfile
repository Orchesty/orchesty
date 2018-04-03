FROM dkr.hanaboso.net/hanaboso/symfony3-base:php-7.2

RUN apt-get update && \
    apt-get install -y git mc sudo nginx-extras socat libpng-dev && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer global require "hirak/prestissimo:^0.3" && \
    pecl install xdebug && \
    docker-php-ext-install gd && \
    docker-php-ext-enable xdebug && \
    apt-get clean && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/lib/log/* /var/log/* /tmp/* /var/tmp/*

# Nginx & FPM config
ENV PHP_APP_INDEX index.php
COPY nginx.conf /etc/nginx/nginx.conf
COPY php-fpm.conf /usr/local/etc/php-fpm.d/zzz-local.conf
RUN mkdir -p /var/log/nginx

# Container has the same ssh as the host machine
ENV SSH_AUTH_SOCK=/tmp/bound-ssh-agent

ENV TERM=xterm

RUN curl -L https://github.com/docker/compose/releases/download/1.16.1/docker-compose-`uname -s`-`uname -m` -o /usr/local/bin/docker-compose && \
    chmod +x /usr/local/bin/docker-compose

# Allow these binaries to be executed with root privileges (Don't you dare use this in prod image!)
RUN chmod u+s /usr/sbin/useradd && \
    chmod u+s /usr/sbin/groupadd

# Enable paswordless sudo for the dev user
RUN echo "dev ALL=NOPASSWD: ALL" > /etc/sudoers.d/dev

COPY php-local.ini /usr/local/etc/php/conf.d/zz_local.ini

# Run PHP FPM as the dev user
RUN sed -i "s/^user =.*/user = dev/; s/^group = .*/group = dev/;" /usr/local/etc/php-fpm.d/www.conf

COPY entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]

WORKDIR /srv/project

CMD sudo -E php-fpm
