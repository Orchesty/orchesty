.PHONY: docker-build docker-push go-test

DOCKER_SERVICE_NAME := multi-probe
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/
PUBLIC_IMAGE=hanaboso/pipes-

lint:
	gofmt -w cmd pkg
	golint ./cmd/... ./pkg/...

build:
	docker build -t $(DOCKER_SERVICE_NAME):$(TAG) -t $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(TAG) .
	docker push $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(TAG)
	docker tag  $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(TAG) $(PUBLIC_IMAGE)$(DOCKER_SERVICE_NAME):$(TAG)
	docker push $(PUBLIC_IMAGE)$(DOCKER_SERVICE_NAME):$(TAG)

go-test:
	go test ./...

ci-test: docker-compose.ci.yml
	docker-compose -f docker-compose.ci.yml up -d --force-recreate
	docker-compose -f docker-compose.ci.yml exec -T multi-probe make go-test

docker-compose.ci.yml:
	cp docker-compose.yml docker-compose.ci.yml