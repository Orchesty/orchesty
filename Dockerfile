FROM hanabosocom/go-base:1.18
COPY . .
RUN go build -ldflags='-s -w' -o /detector main.go && upx -9 /detector

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache && \
    apk add --no-cache docker
COPY --from=0 /detector /bin/detector
WORKDIR /bin
CMD ./detector
