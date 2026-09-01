FROM --platform=$BUILDPLATFORM hanabosocom/go-base:1.24
COPY . .
ARG TARGETOS TARGETARCH
RUN GOOS=$TARGETOS GOARCH=$TARGETARCH go build -ldflags='-s -w' -o /topology-generator main.go && upx -9 /topology-generator

FROM alpine
RUN apk update --no-cache && \
    apk upgrade --no-cache && \
    apk add --no-cache docker docker-compose libcap curl tzdata && \
    mkdir -p /srv/topology && chmod -R 777 /srv
COPY --from=0 /topology-generator /bin/topology-generator
RUN setcap cap_net_bind_service=+ep /bin/topology-generator
WORKDIR /bin
ENV DOCKER_API_VERSION=1.45
CMD ./topology-generator server
