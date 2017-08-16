FROM node:8

RUN apt-get update && \
    apt-get install sudo && \
    apt-get clean

# SSH agent socket should be mounted here from the host
ENV SSH_AUTH_SOCK=/ssh-agent

WORKDIR /app

COPY entrypoint.sh /
ENTRYPOINT [ "/entrypoint.sh" ]

CMD [ "/bin/bash" ]
