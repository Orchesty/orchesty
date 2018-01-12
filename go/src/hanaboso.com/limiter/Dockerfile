# Build the binary
FROM golang:1.9
WORKDIR /go/src/hanaboso.com/limiter/cmd/
RUN go get -u github.com/stretchr/testify/assert github.com/streadway/amqp gopkg.in/mgo.v2
COPY ./limiter /go/src/hanaboso.com/limiter
# Add custom local hanaboso dependencies like below
COPY ./utils /go/src/hanaboso.com/utils
RUN CGO_ENABLED=0 GOOS=linux go build -a -installsuffix cgo -o ./../build/limiter .

# Create runnable image with the binary only
FROM scratch
USER 65534:65534
WORKDIR /usr/local/bin
COPY --from=0 /go/src/hanaboso.com/limiter/build/limiter .
CMD ["./limiter"]