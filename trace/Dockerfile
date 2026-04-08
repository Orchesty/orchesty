FROM --platform=$BUILDPLATFORM hanabosocom/go-base:1.24
COPY . .
ARG TARGETOS TARGETARCH
RUN GOOS=$TARGETOS GOARCH=$TARGETARCH go build -ldflags='-s -w' -o /trace cmd/trace.go && upx -9 /trace

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache && apk add tzdata --no-cache
COPY --from=0 /trace /bin/trace
WORKDIR /bin
CMD [ "./trace" ]
