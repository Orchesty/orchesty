.PHONY: docker-build-base docker-build-dev docker-build-prod docker-push-base docker-push-xml-parser-prod docker-push-xml-parser-dev docker-build-sdk-dev docker-build-sdk-prod docker-push-sdk-dev docker-push-sdk-prod

DOCKER_DEFAULT_TAG := dev
DOCKER_PROD_TAG := prod
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

docker-build-base:
	docker build -t python-base:$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)python-base:$(DOCKER_DEFAULT_TAG) -f docker/base/Dockerfile .

docker-build-base3:
	docker build -t python-base3:$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)python-base3:$(DOCKER_DEFAULT_TAG) -f docker/base3/Dockerfile .

docker-build-dev: docker-build-base
	docker build -t python-xml-parser:$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_DEFAULT_TAG) -f docker/dev/Dockerfile .

docker-build-cron-dev: docker-build-base3
	docker build -t python-cron:$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)python-cron:$(DOCKER_DEFAULT_TAG) -f cron/Dockerfile .

docker-build-prod: docker-build-base
	docker build -t python-xml-parser:$(DOCKER_PROD_TAG) -t $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_PROD_TAG) -f docker/prod/Dockerfile .

docker-build-sdk-dev: docker-build-base3
	docker build -t python-sdk:$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)python-sdk:$(DOCKER_DEFAULT_TAG) -f docker/sdk/dev/Dockerfile .

docker-build-sdk-prod: docker-build-base3
	docker build -t python-sdk:$(DOCKER_PROD_TAG) -t $(DOCKER_REGISTRY)python-sdk:$(DOCKER_PROD_TAG) -f docker/sdk/prod/Dockerfile .

docker-push-base: docker-build-base
	docker push $(DOCKER_REGISTRY)python-base:$(DOCKER_DEFAULT_TAG)

docker-push-base3: docker-build-base3
	docker push $(DOCKER_REGISTRY)python-base3:$(DOCKER_DEFAULT_TAG)

docker-push-xml-parser-prod: docker-build-prod
	docker push $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_PROD_TAG)

docker-push-xml-parser-dev: docker-build-dev
	docker push $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_DEFAULT_TAG)

docker-push-cron-dev: docker-build-cron-dev
	docker push $(DOCKER_REGISTRY)python-cron:$(DOCKER_DEFAULT_TAG)

docker-push-sdk-dev: docker-build-sdk-dev
	docker push $(DOCKER_REGISTRY)python-sdk:$(DOCKER_DEFAULT_TAG)

docker-push-sdk-prod: docker-build-sdk-prod
	docker push $(DOCKER_REGISTRY)python-sdk:$(DOCKER_PROD_TAG)

docker-build-all: docker-build-base docker-build-base3 docker-build-dev docker-build-cron-dev docker-build-prod docker-build-sdk-dev docker-build-sdk-prod

docker-push-all: docker-push-base docker-push-base3 docker-push-xml-parser-prod docker-push-xml-parser-dev docker-push-cron-dev docker-push-sdk-dev docker-push-sdk-prod
