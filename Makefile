.PHONY: docker-build docker-push

DOCKER_DEFAULT_TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

docker-build:
	docker build -t topology-api-v2:$(DOCKER_DEFAULT_TAG) -t $(DOCKER_REGISTRY)topology-api-v2:$(DOCKER_DEFAULT_TAG) -f docker/build/Dockerfile .
 
docker-push: docker-build
	docker push $(DOCKER_REGISTRY)topology-api-v2:$(DOCKER_DEFAULT_TAG)

go-test:
	gofmt -w cmd pkg
	go vet ./...
	go test ./...

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

ci-test:
	docker-compose up -d --force-recreate
	docker-compose exec -T topology-generator gofmt -w cmd pkg
	docker-compose exec -T topology-generator go vet ./...
	docker-compose exec -T topology-generator go test ./...

run:
	export DOCKER_API_VERSION=1.37
	go run main.go server
