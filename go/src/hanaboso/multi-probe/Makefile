.PHONY: docker-build docker-push go-test install-deps

DOCKER_SERVICE_NAME := multi-probe
DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

install-deps:
	go get -u github.com/go-redis/redis github.com/stretchr/testify/assert gopkg.in/mgo.v2

docker-build:
	cd ../ && docker build -t $(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -f ./$(DOCKER_SERVICE_NAME)/Dockerfile .

docker-push:
	docker push $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG)

go-test:
	go test ./...

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

ci-test:
	docker-compose up -d --force-recreate
	docker-compose exec -T multi-probe make install-deps go-test