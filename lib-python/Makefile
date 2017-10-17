.PHONY: docker-build-base docker-build-dev docker-build-prod docker-push-base docker-push-xml-parser-prod docker-push-xml-parser-dev

DOCKER_DEFAULT_TAG := dev
DOCKER_PROD_TAG := prod
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

docker-build-base:
	docker build -t python-base:$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)python-base:$(DOCKER_DEFAULT_TAG) -f docker/base/Dockerfile .

docker-build-dev: docker-build-base
	docker build -t python-xml-parser:$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_DEFAULT_TAG) -f docker/dev/Dockerfile .

docker-build-prod: docker-build-base
	docker build -t python-xml-parser:$(DOCKER_PROD_TAG) -t $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_PROD_TAG) -f docker/prod/Dockerfile .

docker-push-base: docker-build-base
	docker push $(DOCKER_REGISTRY)python-base:$(DOCKER_DEFAULT_TAG)
	
docker-push-xml-parser-prod: docker-build-prod
	docker push $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_PROD_TAG)
	
docker-push-xml-parser-dev: docker-build-dev
	docker push $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_DEFAULT_TAG)

docker-build-all: docker-build-dev docker-build-prod

docker-push-all: docker-push-base docker-push-xml-parser-prod docker-push-xml-parser-dev
