.PHONY: docker-build docker-push

DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

docker-build:
	cd ../ && docker build -t topology-api:$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)topology-api:$(DOCKER_DEFAULT_TAG) -f topologygenerator/Dockerfile .
 
docker-push: docker-build
	docker push $(DOCKER_REGISTRY)topology-api:$(DOCKER_DEFAULT_TAG)
