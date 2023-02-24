.PHONY: docker-build docker-push

DOCKER_TAG = dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/fluentd
PUBLIC_DOCKER_REGISTRY := hanaboso/pipes-fluentd

docker-build:
	docker build -t $(DOCKER_REGISTRY):$(DOCKER_TAG) . --pull

docker-push:
	docker push $(DOCKER_REGISTRY):$(DOCKER_TAG)
	docker tag $(DOCKER_REGISTRY):$(DOCKER_TAG) $(PUBLIC_DOCKER_REGISTRY):$(DOCKER_TAG)
	docker push $(PUBLIC_DOCKER_REGISTRY):$(DOCKER_TAG)
