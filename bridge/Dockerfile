FROM hanabosocom/go-base:1.18
COPY . .
RUN go build -ldflags='-s -w' -o /bridge main.go && upx -9 /bridge

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache
COPY --from=0 /bridge /bin/bridge
WORKDIR /bin
CMD ./bridge start
