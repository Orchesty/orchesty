# Build the binary
FROM golang:1.9
WORKDIR /go/src/clever-monitor.com/limiter/cmd/
RUN go get -u github.com/stretchr/testify/assert github.com/streadway/amqp gopkg.in/mgo.v2
COPY ./limiter /go/src/clever-monitor.com/limiter
RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o ./../build/limiter .

# Create runnable image with the binary only
FROM scratch
USER 65534:65534
WORKDIR /usr/local/bin
COPY --from=0 /go/src/clever-monitor.com/limiter/build/limiter .
CMD ["./limiter"]