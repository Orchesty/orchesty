.PHONY: docker-build docker-push go-test

TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/limiter
PUBLIC_REGISTRY := hanaboso/limiter

lint:
	gofmt -w cmd pkg
	golint ./cmd/... ./pkg/...

build:
	docker build -t $(DOCKER_REGISTRY):$(TAG) .
	docker push $(DOCKER_REGISTRY):$(TAG)
	docker tag ${DOCKER_REGISTRY}:${TAG} $(PUBLIC_REGISTRY):$(TAG)
	docker push $(PUBLIC_REGISTRY):$(TAG)

go-test:
	# for mac users: $ sudo ifconfig lo0 alias 127.0.0.10 up
	docker-compose up -d
	RABBITMQ_HOST=localhost MONGO_HOST=localhost go test ./...

ci-test:
	docker-compose -f docker-compose.ci.yml pull
	docker-compose -f docker-compose.ci.yml up -d --force-recreate
	# waiting for rabbitMQ to get ready...
	sleep 10
	docker-compose -f docker-compose.ci.yml exec -T limiter go test ./...

ci-clean:
	docker-compose down -v
