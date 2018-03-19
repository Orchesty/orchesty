# Build the binary
FROM cmhanaboso.azurecr.io/devtools/go-build:latest
ARG UID
ARG GID
WORKDIR /go/src/clever-monitor/limiter/cmd/
COPY ./limiter /go/src/clever-monitor/limiter
COPY ./utils /go/src/clever-monitor/utils
RUN chown -R $UID:$GID /go/src/clever-monitor/limiter && \
    chown -R $UID:$GID /go/src/clever-monitor/utils
USER $UID:$GID
RUN CGO_ENABLED=0 GOOS=linux /entrypoint.sh go build -a -installsuffix cgo -o ./../build/limiter .

# Create runnable image with the binary only
FROM scratch
USER 65534:65534
WORKDIR /usr/local/bin
COPY --from=0 /go/src/clever-monitor/limiter/build/limiter .
CMD ["./limiter"]
