FROM golang:latest

RUN apt-get update
RUN apt-get install -y git libc-dev

RUN mkdir -p /var/www/logs
RUN chmod 777 /var/www/logs

RUN chmod u+s /usr/sbin/useradd && \
    chmod u+s /usr/sbin/groupadd

RUN mkdir /etc/sudoers.d
RUN echo "dev ALL=NOPASSWD: ALL" > /etc/sudoers.d/dev

COPY docker/entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]
