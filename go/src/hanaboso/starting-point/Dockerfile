FROM golang:1.11.2-alpine3.8
RUN apk update && \
    apk upgrade && \
    apk add git vim && \
	apk add --no-cache --virtual build-base && \
	go get -u golang.org/x/lint/golint

#WORKDIR /app
#VOLUME ["/app"]