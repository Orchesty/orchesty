FROM --platform=$BUILDPLATFORM hanabosocom/go-base:1.24
COPY . .
ARG TARGETOS TARGETARCH
RUN GOOS=$TARGETOS GOARCH=$TARGETARCH go build -ldflags='-s -w' -o /bridge main.go && upx -9 /bridge

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache && apk add tzdata --no-cache
COPY --from=0 /bridge /bin/bridge
WORKDIR /bin
CMD ./bridge start
