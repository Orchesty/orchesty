FROM golang:alpine AS builder
RUN apk update --no-cache && apk upgrade --no-cache && apk add --no-cache git upx
ENV GOPATH /
COPY . .
RUN go build -ldflags='-s -w' -o /starting-point cmd/starting-point.go && upx /starting-point

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache
COPY --from=builder /starting-point /bin/starting-point
WORKDIR /bin
CMD ./starting-point
