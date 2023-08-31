FROM hanabosocom/go-base:1.18
COPY . .
RUN go build -ldflags='-s -w' -o /detector main.go && upx -9 /detector

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache && \
    apk add --no-cache docker curl su-exec

RUN curl -LO "https://dl.k8s.io/release/$(curl -L -s https://dl.k8s.io/release/stable.txt)/bin/linux/amd64/kubectl"

RUN su-exec root install -o root -g root -m 0755 kubectl /usr/local/bin/kubectl

COPY --from=0 /detector /bin/detector

WORKDIR /bin

CMD ./detector
