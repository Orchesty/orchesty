FROM debian:stretch

# Variables
ENV TERM=xterm
ARG DEV_USERNAME
ARG DEV_UID

# Update system and install packages
RUN apt-get update && \
    apt-get dist-upgrade -y --force-yes && \
    apt-get install -y --force-yes \
        nano \
        make \
        wget \
        curl \
        lsb-release \
        sudo \
        apt-transport-https

# Install Apache with PHP and extensions
RUN wget -O /etc/apt/trusted.gpg.d/php.gpg https://packages.sury.org/php/apt.gpg && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" > /etc/apt/sources.list.d/php.list && \
    apt-get update && \
    apt-get install -y --force-yes \
      php7.1-cli \
      php7.1-apcu \
      php7.1-mysql \
      php7.1-zip \
      php7.1-xml \
      php7.1-mbstring \
      php7.1-curl \
      php7.1-xdebug

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
    php composer-setup.php --filename=composer --install-dir=/usr/local/bin && \
    php -r "unlink('composer-setup.php');"

# Add current user
RUN useradd -m -u ${DEV_UID} ${DEV_USERNAME} && \
    echo "${DEV_USERNAME} ALL=NOPASSWD: ALL" > /etc/sudoers.d/developer

# Change root to current user
USER ${DEV_USERNAME}

WORKDIR /var/www/html