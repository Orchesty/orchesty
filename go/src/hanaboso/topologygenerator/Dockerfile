# Build the binary
FROM golang:1.9
WORKDIR /go/src/hanaboso/topologygenerator
RUN go get -v -u github.com/streadway/amqp github.com/spf13/viper github.com/gorilla/mux github.com/docker/docker/api/types github.com/docker/docker/client gopkg.in/mgo.v2

COPY ./topologygenerator /go/src/hanaboso/topologygenerator
COPY ./utils /go/src/hanaboso/utils

RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -ldflags "-X main.compiled=$(date +\"%y%m%d%H%M%S\")" -o topologygenerator .

FROM docker:18.05
RUN apk update --no-cache && apk add --no-cache py-pip libcap
#RUN pip install docker-compose :: Disabled because failed build

WORKDIR /usr/local/bin
COPY topologygenerator/config.yml /etc/default/topology-generator/config.yml
COPY --from=0 /go/src/hanaboso/topologygenerator/topologygenerator .
RUN setcap cap_net_bind_service=+ep /usr/local/bin/topologygenerator

RUN mkdir -p /srv/topology && chmod -R 777 /srv

# docker lib needs this variable set through env var
ENV DOCKER_API_VERSION=1.37
CMD ["./topologygenerator"]
