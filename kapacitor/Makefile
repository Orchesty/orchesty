.PHONY: build

TAG? = dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/kapacitor
PUBLIC_REGISTRY := hanaboso/kapacitor

build:
	docker build -t $(DOCKER_REGISTRY):$(TAG) .
	docker push $(DOCKER_REGISTRY):$(TAG)
	docker tag ${DOCKER_REGISTRY}:${TAG} $(PUBLIC_REGISTRY):$(TAG)
	docker push $(PUBLIC_REGISTRY):$(TAG)
