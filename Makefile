.PHONY: docker-build docker-push

DOCKER_TAG = dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/logstash
PUBLIC_DOCKER_REGISTRY := hanaboso/pipes-logstash
TYPE?=mongo

docker-build:
	docker build --build-arg TYPE=$(TYPE) -t $(DOCKER_REGISTRY)-$(TYPE):$(DOCKER_TAG) . --pull

docker-push:
	docker push $(DOCKER_REGISTRY)-$(TYPE):$(DOCKER_TAG)
	docker tag $(DOCKER_REGISTRY)-$(TYPE):$(DOCKER_TAG) $(PUBLIC_DOCKER_REGISTRY)-$(TYPE):$(DOCKER_TAG)
	docker push $(PUBLIC_DOCKER_REGISTRY)-$(TYPE):$(DOCKER_TAG)
