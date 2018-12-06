.PHONY: build

.env:
	@sed -e "s|{GOCACHE}|$(shell go env GOCACHE)|g" \
		.env.dist > .env

build-image:
	docker build -t dkr.hanaboso.net/pipes/pipes/starting-point:dev .

init: .env build-image
	docker-compose up -d --force-recreate  --remove-orphans

build:
	docker-compose exec app go build -o build/starting-point cmd/starting-point.go

lint:
	docker-compose exec app gofmt -w cmd pkg
	docker-compose exec app golint ./cmd/... ./pkg/...

test: lint
	docker-compose exec app go test -cover -coverprofile build/cover.out ./... -count=1
	docker-compose exec app go tool cover -html=build/cover.out -o build/cover.html

format-local:
	gofmt -w cmd pkg

lint-local:
	golint ./cmd/... ./pkg/...

test-local: format-local lint-local
	go test ./...
