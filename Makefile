.PHONY: docker-build docker-push

DOCKER_REGISTRY := orchesty/fluentd

build:
	docker buildx build --pull --push --platform linux/amd64,linux/arm64/v8 -t $(DOCKER_REGISTRY):$(DOCKER_TAG) .
