DC=docker-compose
DE=docker-compose exec -T app
REGISTRY=dkr.hanaboso.net/pipes/pipes
IMAGE=dkr.hanaboso.net/pipes/pipes/starting-point

.env:
	sed -e 's/{DEV_UID}/$(shell id -u)/g' \
		-e 's/{DEV_GID}/$(shell id -g)/g' \
		-e 's/{GITLAB_CI}/$(echo $GITLAB_CI)/g' \
		.env.dist >> .env; \

build:
	docker build -t ${IMAGE}:${TAG} .
	docker push ${IMAGE}:${TAG}

build-dev:
	docker build -f Dockerfile.dev -t ${IMAGE}:${TAG} .
	docker push ${IMAGE}:${TAG}

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g; s/^(\s+- \$$\{GOPATH\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

init-dev: docker-up-force wait-for-server-start

wait-for-server-start:
	$(DE) /bin/sh -c 'while [ $$(curl -s -o /dev/null -w "%{http_code}" http://localhost/status) == 000 ]; do sleep 1; done'
	$(DE) /bin/sh -c 'while [ $$(curl -s -o /dev/null -w "%{http_code}" http://guest:guest@rabbitmq:15672/api/overview) == 000 ]; do sleep 1; done'

lint:
	$(DE) gofmt -w .
	$(DE) golint ./...

fast-test: lint
	$(DE) mkdir var || true
	$(DE) go test -cover -coverprofile var/coverage.out ./... -count=1
	$(DE) go tool cover -html=var/coverage.out -o var/coverage.html

test: init-dev fast-test docker-down-clean

ci-test: test
