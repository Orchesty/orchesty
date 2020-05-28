.PHONY: docker-build docker-push

DOCKER_TAG = dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes
TYPE?=mongo

docker-build:
	docker build --build-arg TYPE=$(TYPE) -t $(DOCKER_REGISTRY)/logstash-$(TYPE):$(DOCKER_TAG) .

docker-push:
	docker push $(DOCKER_REGISTRY)/logstash-$(TYPE):$(DOCKER_TAG)