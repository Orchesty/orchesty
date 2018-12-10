.PHONY: build

# TAG?=dev # no, default tag is a nonsense and can cause unwanted overwrites!
REGISTRY=dkr.hanaboso.net/pipes/pipes
APP_IMAGE=$(REGISTRY)/starting-point:$(TAG)

.env:
	@sed -e "s|{GOCACHE}|$(shell go env GOCACHE)|g" \
		.env.dist > .env

build-image:
	docker build -t $(APP_IMAGE) .

build-dev-image:
	docker build -f Dockerfile.dev -t starting-point-dev .

push:
	docker push $(APP_IMAGE)


init: .env build-dev-image
	docker-compose up -d --force-recreate --remove-orphans

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
