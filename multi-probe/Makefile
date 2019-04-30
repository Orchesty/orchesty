.PHONY: docker-build docker-push go-test

DOCKER_SERVICE_NAME := multi-probe
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
	go test ./...

ci-test: docker-compose.ci.yml
	docker-compose -f docker-compose.ci.yml up -d --force-recreate
	docker-compose -f docker-compose.ci.yml exec -T multi-probe make go-test

docker-compose.ci.yml:
	cp docker-compose.yml docker-compose.ci.yml