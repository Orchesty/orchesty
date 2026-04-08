FROM --platform=$BUILDPLATFORM golang:1.24 AS builder
WORKDIR /src
COPY go.mod go.sum ./
RUN go mod download
COPY . .
ARG TARGETOS TARGETARCH
RUN CGO_ENABLED=0 GOOS=$TARGETOS GOARCH=$TARGETARCH go build -ldflags='-s -w' -o /tunel-proxy ./cmd/server

FROM gcr.io/distroless/static-debian12
COPY --from=builder /tunel-proxy /tunel-proxy
EXPOSE 8080 50051
ENTRYPOINT ["/tunel-proxy"]
