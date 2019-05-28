# Build the binary
FROM golang:1-alpine

RUN apk add git gcc libc-dev
WORKDIR /srv

ENV GO111MODULE=on

COPY . .
