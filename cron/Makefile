DC=docker-compose
DE=docker-compose exec -T app
IMAGE=dkr.hanaboso.net/pipes/pipes/cron
PUBLIC_IMAGE=hanaboso/pipes-cron

.env:
	sed -e 's/{DEV_UID}/$(shell id -u)/g' \
		-e 's/{DEV_GID}/$(shell id -g)/g' \
		.env.dist >> .env; \

build:
	docker build -t ${IMAGE}:${TAG} --pull .
	docker push ${IMAGE}:${TAG}
	docker tag ${IMAGE}:${TAG} $(PUBLIC_IMAGE):$(TAG)
	docker push $(PUBLIC_IMAGE):$(TAG)

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g; s/^(\s+- \$$\{GOPATH\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

go-update:
	$(DE) su-exec root go get -u all
	$(DE) su-exec root go mod tidy
	$(DE) su-exec root chown dev:dev go.mod go.sum

init-dev: docker-up-force wait-for-server-start

wait-for-server-start:
	$(DE) /bin/sh -c 'while [ $$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/status) == 000 ]; do sleep 1; done'

lint:
	$(DE) gofmt -w .
	$(DE) golint ./...

fast-test: lint
	$(DE) mkdir var || true
	$(DE) go test -count 1 -cover -coverprofile var/coverage.out -p 1 ./...
	$(DE) go tool cover -html=var/coverage.out -o var/coverage.html

test: init-dev fast-test docker-down-clean

ci-test: test
