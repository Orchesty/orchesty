.PHONY: docker-build docker-push go-test install-deps

DOCKER_SERVICE_NAME := limiter
DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

install-deps:
	go get -u github.com/stretchr/testify/assert
	go get -u github.com/streadway/amqp
	go get -u gopkg.in/mgo.v2

docker-build:
	cd ../ && docker build -t $(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -f ./$(DOCKER_SERVICE_NAME)/Dockerfile .

docker-push:
	docker push $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG)

go-test:
	# for mac users: $ sudo ifconfig lo0 alias 127.0.0.10 up
	docker-compose up -d
	RABBITMQ_HOST=127.0.0.10 MONGO_HOST=127.0.0.10 go test ./...

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

ci-test:
	docker-compose up -d --force-recreate
	docker-compose exec limiter make install-deps
	docker-compose exec limiter go test ./...
