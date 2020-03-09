FROM hanabosocom/go-base:dev
COPY . .
RUN go build -ldflags='-s -w' -o /rabbitmq-telegraf main.go && upx -9 /rabbitmq-telegraf

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache
COPY --from=0 /rabbitmq-telegraf /bin/rabbitmq-telegraf
WORKDIR /bin
CMD ./rabbitmq-telegraf
