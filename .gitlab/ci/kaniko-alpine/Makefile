IMAGE=dkr.hanaboso.net/pipes/pipes/kaniko-alpine:$(TAG)

# Build
build:
	if ! docker buildx inspect multi; then docker buildx create --name multi --platform linux/amd64,linux/arm64/v8 --use --bootstrap; fi
	docker buildx build -f Dockerfile --pull --push --platform linux/amd64,linux/arm64/v8 -t $(IMAGE) .