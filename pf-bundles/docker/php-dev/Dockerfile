FROM dkr.hanaboso.net/hanaboso/symfony3-base:php-7.3

RUN apt-get update && \
    apt-get install -y git mc sudo nginx-extras socat libpng-dev && \
    pecl install xdebug && \
    docker-php-ext-install gd && \
    apt-get clean && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/lib/log/* /var/log/* /tmp/* /var/tmp/*

# Container has the same ssh as the host machine
ENV SSH_AUTH_SOCK=/tmp/bound-ssh-agent

ENV TERM=xterm

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

WORKDIR /var/www

CMD sudo -E php-xdebug-w-nginx.sh
