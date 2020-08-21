FROM hanabosocom/go-base:dev
COPY . .
RUN go build -ldflags='-s -w' -o /multi-probe ./cmd/probe.go && upx -9 /multi-probe

FROM alpine
RUN apk update --no-cache && apk upgrade --no-cache
COPY --from=0 /multi-probe /bin/multi-probe
WORKDIR /bin
CMD ./multi-probe