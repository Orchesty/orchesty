FROM golang:alpine AS builder
RUN apk update --no-cache && apk upgrade --no-cache && apk add --no-cache git upx
ENV GOPATH /
COPY . .
RUN go build -ldflags='-s -w' -o /topology-generator main.go && upx /topology-generator

FROM alpine
RUN apk update --no-cache && \
    apk upgrade --no-cache && \
    apk add --no-cache docker docker-compose libcap && \
    mkdir -p /srv/topology && chmod -R 777 /srv
COPY --from=builder /topology-generator /bin/topology-generator
RUN setcap cap_net_bind_service=+ep /bin/topology-generator
WORKDIR /bin
ENV DOCKER_API_VERSION=1.37
CMD ./topology-generator server
