FROM node:14-alpine

RUN apt-get update && \
    apt-get install -y --force-yes sudo socat netcat nginx-extras && \
    apt-get clean

ENV SSH_AUTH_SOCK=/ssh-agent
ENV WEBROOT /var/www/html

COPY nginx.conf /etc/nginx/

# Allow these binaries to be executed with root privileges (Don't you dare use this in prod image!)
RUN chmod u+s /usr/sbin/useradd && \
    chmod u+s /usr/sbin/groupadd

# Pre-create npm cache for later volume binding. See entrypoint.sh.
RUN mkdir /srv/.npm

# sudo for dev user
RUN echo "dev ALL=NOPASSWD: ALL" > /etc/sudoers.d/dev

COPY entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]

WORKDIR /app

CMD sudo -E nginx -g 'daemon off;'
