.PHONY: docker-build docker-push

IMAGE=orchesty/fluentd:$(TAG)

build:
	docker buildx build --pull --push --platform linux/amd64,linux/arm64/v8 -t $(IMAGE) .
