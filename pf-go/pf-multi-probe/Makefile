.PHONY: docker-build docker-push

DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

docker-build:
	docker build -t go-multi-probe:$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)go-multi-probe:$(DOCKER_DEFAULT_TAG) .

docker-push:
	docker push $(DOCKER_REGISTRY)go-multi-probe:$(DOCKER_DEFAULT_TAG)
