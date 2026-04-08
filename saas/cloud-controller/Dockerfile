FROM --platform=$BUILDPLATFORM hanabosocom/go-base:1.24
COPY . .
ARG TARGETOS TARGETARCH
RUN GOOS=$TARGETOS GOARCH=$TARGETARCH go build -ldflags='-s -w' -o /cloud-controller main.go && upx -9 /cloud-controller

FROM alpine/helm:4
RUN apk update --no-cache && apk upgrade --no-cache && apk add tzdata --no-cache
COPY --from=0 /cloud-controller /bin/cloud-controller

WORKDIR /bin
ENTRYPOINT ["./cloud-controller"]
CMD []
