FROM node:18-alpine

RUN apk update && apk upgrade && apk add --no-cache \
    aws-cli bash ca-certificates gnupg \
    autoconf automake g++ make zlib-dev && \
    rm -rf /var/lib/* /var/cache/apt/*

RUN chmod 0755 /root
ENV HOME=/tmp PATH=/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin:/root/.composer/vendor/bin
