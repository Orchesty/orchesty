.PHONY: docker-build docker-push go-test

DOCKER_SERVICE_NAME := limiter
DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

lint:
	gofmt -w cmd pkg
	golint ./cmd/... ./pkg/...

docker-build:
	docker build -t $(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) .

docker-push:
	docker push $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG)

go-test:
	# for mac users: $ sudo ifconfig lo0 alias 127.0.0.10 up
	docker-compose up -d
	RABBITMQ_HOST=localhost MONGO_HOST=localhost go test ./...

ci-test:
	docker-compose -f docker-compose.ci.yml pull
	docker-compose -f docker-compose.ci.yml up -d --force-recreate
	# waiting for rabbitMQ to get ready...
	sleep 10
	docker-compose -f docker-compose.ci.yml exec -T limiter go test ./...

ci-clean:
	docker-compose down -v
