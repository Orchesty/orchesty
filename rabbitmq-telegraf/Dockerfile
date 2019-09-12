FROM golang:alpine AS builder
RUN apk update --no-cache && apk upgrade --no-cache && apk add --no-cache git upx
ENV GOPATH /
COPY . .
RUN go build -ldflags='-s -w' -o /rabbitmq-telegraf main.go && upx /rabbitmq-telegraf

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache
COPY --from=builder /rabbitmq-telegraf /bin/rabbitmq-telegraf
WORKDIR /bin
CMD ./rabbitmq-telegraf
