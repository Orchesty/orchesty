# Build the binary
FROM golang:latest
WORKDIR /limiter/cmd/
COPY . /limiter
RUN XDG_CACHE_HOME=/tmp/.cache CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o ./../build/limiter .

# Create runnable image with the binary only
FROM scratch
USER 65534:65534
WORKDIR /usr/local/bin
COPY --from=0 /limiter/build/limiter .
CMD ["./limiter"]
