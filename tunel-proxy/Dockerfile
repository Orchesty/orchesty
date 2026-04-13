FROM --platform=$BUILDPLATFORM hanabosocom/go-base:1.26
COPY . .
ARG TARGETOS TARGETARCH
RUN GOOS=$TARGETOS GOARCH=$TARGETARCH go build -ldflags='-s -w' -o /tunnel-proxy cmd/server/main.go && upx -9 /tunnel-proxy

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache && apk add tzdata --no-cache
COPY --from=0 /tunnel-proxy /bin/tunnel-proxy
WORKDIR /bin
CMD [ "./tunnel-proxy" ]
