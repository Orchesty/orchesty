.PHONY: docker-build docker-push go-test install-dep install-dependencies

DOCKER_SERVICE_NAME := limiter
DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

install-dep:
	curl https://raw.githubusercontent.com/golang/dep/master/install.sh | sh

install-dependencies:
	dep ensure
	dep status

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		.env.dist >> .env; \

docker-build:
	cd ../ && docker build --build-arg UID=$(shell id -u) --build-arg GID=$(shell id -g) -t $(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -f ./$(DOCKER_SERVICE_NAME)/Dockerfile .

docker-push:
	docker push $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG)

go-test: .env
	# for mac users: $ sudo ifconfig lo0 alias 127.0.0.10 up
	docker-compose up -d
	RABBITMQ_HOST=127.0.0.10 MONGO_HOST=127.0.0.10 go test ./...

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

ci-test: .env
	# docker-compose pull
	docker-compose up -d --force-recreate
	# docker-compose exec -T limiter make install-dep
	docker-compose exec -T limiter make install-dependencies
	docker-compose exec -T limiter go test ./...
	#docker run --rm -e GID=$(shell id -g) -e UID=$(shell id -u) -v ${CURDIR}/..:/go/src/clever-monitor -w /go/src/clever-monitor/limiter cmhanaboso.azurecr.io/devtools/go-build:latest go test ./...
	# docker-compose run --rm -e GID=$(shell id -g) -e UID=$(shell id -u) limiter go test ./...
	# docker-compose run --rm -u $(shell id -u):$(shell id -g) -v ${PWD}:/go/src/clevermonitor/limiter -w /go/src/clevermonitor/limiter cmhanaboso.azurecr.io/devtools/go-build:latest bash -c 'go test ./...'
