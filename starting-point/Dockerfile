FROM hanabosocom/go-base:1.19
COPY . .
RUN go build -ldflags='-s -w' -o /starting-point cmd/starting-point.go && upx -9 /starting-point

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache
COPY --from=0 /starting-point /bin/starting-point
WORKDIR /bin
CMD ./starting-point
