.PHONY: build

# TAG?=dev # no, default tag is a nonsense and can cause unwanted overwrites!
REGISTRY=dkr.hanaboso.net/pipes/pipes
APP_IMAGE=$(REGISTRY)/starting-point:$(TAG)
APP_DEV_IMAGE=$(REGISTRY)/starting-point:app-dev

.env:
	@sed -e "s|{GOCACHE}|$(shell go env GOCACHE)|g" \
		.env.dist > .env

build-image:
	docker build -t $(APP_IMAGE) .

build-dev-image:
	docker build -f Dockerfile.dev -t $(APP_DEV_IMAGE) .
	docker push $(APP_DEV_IMAGE)

push:
	docker push $(APP_IMAGE)

init: .env
	docker-compose pull
	docker-compose up -d --force-recreate --remove-orphans

lint:
	docker-compose exec -T app gofmt -w cmd pkg
	docker-compose exec -T app golint ./cmd/... ./pkg/...

test: lint
	docker-compose exec -T app go test -cover -coverprofile build/cover.out ./... -count=1
	docker-compose exec -T app go tool cover -html=build/cover.out -o build/cover.html