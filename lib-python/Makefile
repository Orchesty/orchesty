DOCKER_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

docker-build-base:
	docker build -t python-base:$(DOCKER_TAG) -t $(DOCKER_REGISTRY)python-base:$(DOCKER_TAG) -f docker/base/Dockerfile .

docker-build-base3:
	docker build -t python-base3:$(DOCKER_TAG) -t $(DOCKER_REGISTRY)python-base3:$(DOCKER_TAG) -f docker/base3/Dockerfile .

docker-build--xml-parser-dev: docker-build-base
	docker build -t python-xml-parser:$(DOCKER_TAG) -t $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_TAG) -f docker/dev/Dockerfile .

docker-build-cron-dev: docker-build-base3
	docker build -t python-cron:$(DOCKER_TAG) -t $(DOCKER_REGISTRY)python-cron:$(DOCKER_TAG) -f cron/Dockerfile .

docker-build-cron-prod: docker-build-base3
	docker build -t python-cron:$(DOCKER_TAG) -t $(DOCKER_REGISTRY)python-cron:$(DOCKER_TAG) -f cron/Dockerfile .

docker-build-xml-parser-prod: docker-build-base
	docker build -t python-xml-parser:$(DOCKER_TAG) -t $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_TAG) -f docker/prod/Dockerfile .

docker-build-sdk-dev: docker-build-base3
	docker build -t python-sdk:$(DOCKER_TAG) -t $(DOCKER_REGISTRY)python-sdk:$(DOCKER_TAG) -f docker/sdk/dev/Dockerfile .

docker-build-sdk-prod: docker-build-base3
	docker build -t python-sdk:$(DOCKER_TAG) -t $(DOCKER_REGISTRY)python-sdk:$(DOCKER_TAG) -f docker/sdk/prod/Dockerfile .

docker-push-base: docker-build-base
	docker push $(DOCKER_REGISTRY)python-base:$(DOCKER_TAG)

docker-push-base3: docker-build-base3
	docker push $(DOCKER_REGISTRY)python-base3:$(DOCKER_TAG)

docker-push-xml-parser-prod: docker-build-xml-parser-prod
	docker push $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_TAG)

docker-push-xml-parser-dev: docker-build--xml-parser-dev
	docker push $(DOCKER_REGISTRY)python-xml-parser:$(DOCKER_TAG)

docker-push-cron-dev: docker-build-cron-dev
	docker push $(DOCKER_REGISTRY)python-cron:$(DOCKER_TAG)

docker-push-cron-prod: docker-build-cron-prod
	docker push $(DOCKER_REGISTRY)python-cron:$(DOCKER_TAG)

docker-push-sdk-dev: docker-build-sdk-dev
	docker push $(DOCKER_REGISTRY)python-sdk:$(DOCKER_TAG)

docker-push-sdk-prod: docker-build-sdk-prod
	docker push $(DOCKER_REGISTRY)python-sdk:$(DOCKER_TAG)

docker-build-dev-all: docker-build-base docker-build-base3 docker-build--xml-parser-dev docker-build-cron-dev docker-build-sdk-dev

docker-push-dev-all: docker-push-base docker-push-base3 docker-push-xml-parser-dev docker-push-cron-dev docker-push-cron-dev

docker-build-all: docker-build-base docker-build-base3 docker-build-xml-parser-prod docker-build-cron-prod docker-build-sdk-prod

docker-push-all: docker-push-base docker-push-base3 docker-push-xml-parser-prod docker-push-cron-prod docker-push-sdk-prod
