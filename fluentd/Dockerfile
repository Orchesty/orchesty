FROM fluent/fluentd:v1.15-1

USER root

RUN apk update --no-cache && \
    apk upgrade --no-cache && \
    apk add --no-cache build-base ruby-dev && \
    gem install fluent-plugin-mongo && \
    apk del build-base ruby-dev

COPY fluent.conf /fluentd/etc/fluent.conf

USER fluent
