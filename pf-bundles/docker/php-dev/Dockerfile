FROM dkr.hanaboso.net/hanaboso/symfony3-base

RUN apt-get update && \
    apt-get install -y apt-transport-https ca-certificates curl gnupg2 software-properties-common && \
    curl -fsSL https://download.docker.com/linux/$(. /etc/os-release; echo "$ID")/gpg | apt-key add - && \
    add-apt-repository \
       "deb [arch=amd64] https://download.docker.com/linux/$(. /etc/os-release; echo "$ID") \
       $(lsb_release -cs) \
       stable"

RUN apt-get update && \
    apt-get install -y git mc sudo docker-ce libpng-dev && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer global require "hirak/prestissimo:^0.3" && \
    pecl install xdebug && \
    docker-php-ext-install gd && \
    docker-php-ext-enable xdebug && \
    apt-get clean && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/lib/log/* /var/log/* /tmp/* /var/tmp/*

RUN groupmod -g 800 docker

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
