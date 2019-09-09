.PHONY: init-dev test

DC=docker-compose -f docker-compose.dev.yml
DE=docker-compose -f docker-compose.dev.yml exec -T app

IMAGE=dkr.hanaboso.net/pipes/pipes/rabbitmq-telegraf
TAG?=dev

.env:
	@if ! [ -f .env ]; then \
		sed -e "s/{DEV_UID}/$(shell id -u)/g" \
			-e "s/{DEV_GID}/$(shell id -u)/g" \
			.env.dist >> .env; \
	fi;

build-dev-image:
	docker build -f docker/Dockerfile -t $(IMAGE):dev .
	docker push $(IMAGE):dev

build-binary: build-dev-image docker-up-force
	$(DE) go build

build: .env build-binary
	docker build -f docker/Dockerfile.prod -t $(IMAGE):${TAG} .
	docker push $(IMAGE):${TAG}

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

init-dev: build-dev-image docker-up-force

test: docker-up-force
	$(DE) gofmt -w pkg
	$(DE) go test ./...

fasttest:
	$(DE) gofmt -w pkg
	$(DE) go test ./...
