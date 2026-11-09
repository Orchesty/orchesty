FROM --platform=$BUILDPLATFORM hanabosocom/go-base:1.27
COPY . .
ARG TARGETOS TARGETARCH
RUN GOOS=$TARGETOS GOARCH=$TARGETARCH go build -ldflags='-s -w' -o /cron cmd/cron.go && upx -9 /cron

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache && apk add curl tzdata --no-cache

COPY --from=0 /cron /bin/cron
WORKDIR /bin
CMD ["/bin/sh", "-c", "crond && cron"]
