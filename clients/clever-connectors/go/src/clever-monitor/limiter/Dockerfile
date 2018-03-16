# Build the binary
FROM golang:1.9
WORKDIR /go/src/clever-monitor/limiter/cmd/
COPY ./limiter /go/src/clever-monitor/limiter
# Add custom local non-published dependencies like below
COPY ./utils /go/src/clever-monitor/utils
RUN curl https://raw.githubusercontent.com/golang/dep/master/install.sh | sh && \
    dep ensure && dep status && \
    CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o ./../build/limiter .

# Create runnable image with the binary only
FROM scratch
USER 65534:65534
WORKDIR /usr/local/bin
COPY --from=0 /go/src/clever-monitor/limiter/build/limiter .
CMD ["./limiter"]