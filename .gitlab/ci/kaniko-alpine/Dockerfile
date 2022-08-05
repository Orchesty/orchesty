FROM gcr.io/kaniko-project/executor:latest as kaniko
FROM alpine:latest

ENV HOME=/kaniko
WORKDIR /kaniko
COPY --from=kaniko /kaniko/executor /usr/local/sbin/executor

RUN apk add --no-cache bash git && \
    # prevents "Illegal option -o pipefail"
    ln -sf /bin/bash /bin/sh
