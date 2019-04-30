# Build the binary
FROM golang:latest
WORKDIR /app/cmd/
COPY ./ /app
RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o ./../build/multi-probe .

# Create runnable image with the binary only
FROM scratch
USER 65534:65534
WORKDIR /usr/local/bin
COPY --from=0 /app/build/multi-probe .
CMD ["./multi-probe"]