FROM hanabosocom/go-base:dev
COPY . .
RUN go build -ldflags='-s -w' -o /counter main.go && upx -9 /counter

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache
COPY --from=0 /counter /bin/counter
WORKDIR /bin
CMD ./counter start
