FROM --platform=$BUILDPLATFORM hanabosocom/go-base:1.24
COPY . .
ARG TARGETOS TARGETARCH
RUN GOOS=$TARGETOS GOARCH=$TARGETARCH go build -ldflags='-s -w' -o /notifier cmd/notifier.go && upx -9 /notifier

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache && apk add tzdata --no-cache
COPY --from=0 /notifier /bin/notifier
WORKDIR /bin
CMD [ "./notifier" ]
