.PHONY: docker-build docker-push go-test install-deps

DOCKER_SERVICE_NAME := limiter
DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

install-deps:
	go get -u github.com/stretchr/testify/assert
	go get -u github.com/streadway/amqp

docker-build:
	cd ../ && docker build -t $(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -f ./$(DOCKER_SERVICE_NAME)/Dockerfile .

docker-push:
	docker push $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG)

go-test:
	go test ./...