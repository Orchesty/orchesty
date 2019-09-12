.PHONY: init-dev test

DC=docker-compose
DE=docker-compose exec -T app

IMAGE=dkr.hanaboso.net/pipes/pipes/rabbitmq-telegraf
TAG?=dev

.env:
	@if ! [ -f .env ]; then \
		sed -e "s/{DEV_UID}/$(shell id -u)/g" \
			-e "s/{DEV_GID}/$(shell id -u)/g" \
			.env.dist >> .env; \
	fi;

build-dev-image:
	docker build -f Dockerfile.dev -t $(IMAGE):${TAG} .
	docker push $(IMAGE):${TAG}

build: .env
	docker build -t $(IMAGE):${TAG} .
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

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml
