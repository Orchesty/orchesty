.PHONY: docker-build docker-push go-test install-dep install-dependencies proto-gen

DOCKER_SERVICE_NAME := workflow
DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		.env.dist >> .env; \

install-dep:
	curl https://raw.githubusercontent.com/golang/dep/master/install.sh | sh

install-dependencies:
	dep ensure
	dep status

proto-gen:
	# Downloading protofile
	rm -rf protofiles
	git clone git@gitlab.hanaboso.net:clever-monitor/protofiles.git
	rm -rf pkg/workflowservice
	mkdir pkg/workflowservice
	cp ./protofiles/protos/workflow/WorkflowService.proto .
	rm -rf protofiles
	# Generating structs from proto file
	# Todo download protoc binary in order to run following command
	protoc ./WorkflowService.proto --go_out=plugins=grpc:pkg/WorkflowService
	rm ./WorkflowService.proto

init: install-dep install-dependencies proto-gen

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

ci-test: .env
	docker-compose up -d --force-recreate
	docker-compose exec -T workflow make install-dep
	docker-compose exec -T workflow make install-dependencies
	docker-compose exec -T workflow go test ./...

docker-build:
	cd ../ && docker build --build-arg UID=$(shell id -u) --build-arg GID=$(shell id -u) -t $(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG) -f ./$(DOCKER_SERVICE_NAME)/Dockerfile .

docker-push:
	docker push $(DOCKER_REGISTRY)$(DOCKER_SERVICE_NAME):$(DOCKER_DEFAULT_TAG)
