# Build stage

FROM golang:1.12-stretch as build
RUN apt-get update && \
    apt-get install -y make git && \
    apt-get clean
COPY go.mod go.sum /precache/
WORKDIR /precache
RUN go mod download
COPY ./ /app
WORKDIR /app
RUN go mod download
RUN go build -o build/starting-point cmd/starting-point.go


# Package stage

FROM debian:stretch
COPY --from=build /app/build/starting-point /app/
WORKDIR /app
CMD ["./starting-point"]
