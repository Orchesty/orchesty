# Build the binary
FROM golang:1-alpine AS builder

RUN apk add git gcc libc-dev
WORKDIR /go/src/app

ENV GO111MODULE=on

COPY ../../go.mod .
COPY ../../go.sum .

RUN go mod download

COPY ../.. .

RUN go generate ./...
RUN CGO_ENABLED=1 GOOS=linux GOARCH=amd64 go install -a -ldflags '-w -extldflags "-static"' ./


FROM docker:18.05
RUN apk update --no-cache && \
    apk add --no-cache libcap py-pip python-dev libffi-dev openssl-dev gcc libc-dev make && \
    pip install --upgrade pip && \
    pip install docker-compose
RUN apk --purge del libffi-dev openssl-dev gcc libc-dev make

RUN mkdir -p /srv/topology && chmod -R 777 /srv

WORKDIR /go/src/app

COPY --from=builder /go/bin/topology-generator /bin/
RUN setcap cap_net_bind_service=+ep /bin/topology-generator

# docker lib needs this variable set through env var
ENV DOCKER_API_VERSION=1.37
CMD ["./topology-generator", "server"]
