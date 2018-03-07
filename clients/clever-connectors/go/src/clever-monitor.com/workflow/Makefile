.PHONY: docker-build docker-push go-test install-deps proto-gen

DOCKER_SERVICE_NAME := workflow
DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

install-deps:
	go get -u github.com/stretchr/testify/assert
	go get -u gopkg.in/mgo.v2

proto-gen:
    protoc -I workflow/ workflow/workflow.proto --go_out=plugins=grpc:workflow

docker-build:
	cd ../ && docker build -t $(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -f ./$(DOCKER_SERVICE_NAME)/Dockerfile .

docker-push:
	docker push $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG)

go-test:
	# for mac users: $ sudo ifconfig lo0 alias 127.0.0.11 up
	docker-compose up -d
	RABBITMQ_HOST=127.0.0.11 MONGO_HOST=127.0.0.11 go test ./...

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

ci-test:
	docker-compose up -d --force-recreate
	docker-compose exec -T workflow make install-deps
	docker-compose exec -T workflow go test ./...
