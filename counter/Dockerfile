FROM --platform=$BUILDPLATFORM hanabosocom/go-base:1.24
COPY . .
ARG TARGETOS TARGETARCH
RUN GOOS=$TARGETOS GOARCH=$TARGETARCH go build -ldflags='-s -w' -o /counter main.go && upx -9 /counter

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache && apk add tzdata --no-cache
COPY --from=0 /counter /bin/counter
WORKDIR /bin
CMD ./counter start
