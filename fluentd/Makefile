.PHONY: docker-build docker-push

DOCKER_REGISTRY := orchesty/fluentd

docker-build:
	docker build -t $(DOCKER_REGISTRY):$(DOCKER_TAG) . --pull

docker-push:
	docker push $(DOCKER_REGISTRY):$(DOCKER_TAG)
	docker tag $(DOCKER_REGISTRY):$(DOCKER_TAG)
