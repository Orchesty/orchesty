IMAGE=dkr.hanaboso.net/pipes/pipes/kaniko-alpine:$(TAG)

# Build
build:
	docker buildx build --pull --push --platform linux/amd64,linux/arm64/v8 -t $(IMAGE) .