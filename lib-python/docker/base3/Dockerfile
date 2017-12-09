FROM python:slim-stretch

WORKDIR /usr/src/app

RUN apt-get update && \
    apt-get install -y git joe vim mc sudo && \
    apt-get clean && apt-get autoremove -y && \
    rm -rf /var/lib/apt/lists/* /var/lib/log/* /var/log/* /tmp/* /var/tmp/*

RUN pip install pyyaml mock

# Container has the same ssh as the host machine
ENV SSH_AUTH_SOCK=/tmp/bound-ssh-agent
ENV TERM=xterm

RUN chmod u+s /usr/sbin/useradd && \
    chmod u+s /usr/sbin/groupadd

# Enable paswordless sudo for the dev user
RUN echo "dev ALL=NOPASSWD: ALL" > /etc/sudoers.d/dev

COPY docker/base3/entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]
