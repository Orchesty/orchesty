FROM node:8

RUN apt-get update && \
    apt-get install -y --force-yes sudo nginx-extras && \
    apt-get clean

# SSH agent socket should be mounted here from the host
ENV SSH_AUTH_SOCK=/ssh-agent

ENV WEBROOT /var/www/html

COPY docker/build/nginx.conf /etc/nginx/

# Allow these binaries to be executed with root privileges (Don't you dare use this in prod image!)
RUN chmod u+s /usr/sbin/useradd && \
    chmod u+s /usr/sbin/groupadd

# We dont need this any more
RUN deluser node

# sudo for dev user
RUN echo "dev ALL=NOPASSWD: ALL" > /etc/sudoers.d/dev

COPY docker/build/entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]

WORKDIR /app

CMD sudo -E nginx -g 'daemon off;'
