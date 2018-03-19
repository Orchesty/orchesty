# Build the binary
FROM cmhanaboso.azurecr.io/devtools/go-build:latest
WORKDIR /go/src/clever-monitor/workflow/cmd/
COPY ./workflow /go/src/clever-monitor/workflow
COPY ./utils /go/src/clever-monitor/utils
RUN dep ensure && dep status && \
    CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o ./../build/workflow .

# Create lightweigh runnable image just with the binary
FROM scratch
USER 65534:65534
WORKDIR /usr/local/bin
COPY --from=0 /go/src/clever-monitor/workflow/build/workflow .
CMD ["./workflow"]