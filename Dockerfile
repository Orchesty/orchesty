FROM hanabosocom/go-base:dev
COPY . .
RUN go build -ldflags='-s -w' -o /limiter cmd/limiter_app.go && upx -9 /limiter

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache
COPY --from=0 /limiter /bin/limiter
WORKDIR /bin
CMD ./limiter