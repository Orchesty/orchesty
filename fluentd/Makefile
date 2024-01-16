.PHONY: docker-build docker-push

DOCKER_REGISTRY := orchesty/fluentd

build:
	if ! docker buildx inspect multi; then docker buildx create --name multi --platform linux/amd64,linux/arm64/v8 --use --bootstrap; fi
	docker buildx build --pull --push --platform linux/amd64,linux/arm64/v8 -t $(DOCKER_REGISTRY):$(DOCKER_TAG) .
