.PHONY: docker-build docker-push go-test

DOCKER_SERVICE_NAME := pf-limiter
DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

docker-build:
	docker build -t $(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) .

docker-push:
	docker push $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG)

go-test:
	go test ./...