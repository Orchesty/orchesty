.PHONY: docker-build docker-push

DOCKER_TAG? = dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes

docker-build:
	docker build -t $(DOCKER_REGISTRY)/kapacitor:$(DOCKER_TAG) .

docker-push:
	docker push $(DOCKER_REGISTRY)/kapacitor:$(DOCKER_TAG)
