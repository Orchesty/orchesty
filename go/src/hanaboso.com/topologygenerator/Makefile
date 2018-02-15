.PHONY: docker-build docker-push install-deps

DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

install-deps:
	go get -v -u github.com/streadway/amqp github.com/spf13/viper github.com/gorilla/mux github.com/docker/docker/api/types github.com/docker/docker/client gopkg.in/mgo.v2

docker-build:
	cd ../ && docker build -t topology-api:$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)topology-api:$(DOCKER_DEFAULT_TAG) -f topologygenerator/Dockerfile .
 
docker-push: docker-build
	docker push $(DOCKER_REGISTRY)topology-api:$(DOCKER_DEFAULT_TAG)

go-test:
	go test ./...

ci-test:
	docker-compose up -d
	docker-compose exec topology-generator make install-deps go-test