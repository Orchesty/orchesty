FROM node:8

RUN apt-get update && \
    apt-get install -y --force-yes sudo socat netcat nginx-extras && \
    apt-get clean

# SSH agent socket should be mounted here from the host
# TODO: rewrite to /tmp/ssh-agent when overrides are ready in build scripts)
ENV SSH_AUTH_SOCK=/ssh-agent

ENV WEBROOT /var/www/html

COPY nginx.conf /etc/nginx/

# Allow these binaries to be executed with root privileges (Don't you dare use this in prod image!)
RUN chmod u+s /usr/sbin/useradd && \
    chmod u+s /usr/sbin/groupadd

# We dont need this any more
# "dialout" collides with osx default user group
RUN deluser node && delgroup dialout

# Pre-create npm cache for later volume binding. See entrypoint.sh.
RUN mkdir /srv/.npm && \ 
    npm i -g gulp

# sudo for dev user
RUN echo "dev ALL=NOPASSWD: ALL" > /etc/sudoers.d/dev

COPY entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]

WORKDIR /app

CMD sudo -E nginx -g 'daemon off;'
