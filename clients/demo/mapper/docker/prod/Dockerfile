FROM dkr.hanaboso.net/hanaboso/symfony3-base

RUN apt-get update && \
    apt-get install -y git mc sudo && \
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    composer global require "hirak/prestissimo:^0.3" && \
    apt-get clean && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/lib/log/* /var/log/* /tmp/* /var/tmp/*

COPY php-local.ini /usr/local/etc/php/conf.d/zz_local.ini

WORKDIR /var/www/html

COPY . ./

CMD sudo -E php-fpm