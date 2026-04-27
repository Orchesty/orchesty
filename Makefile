IMAGE=orchesty/counter:$(TAG)

DC=docker compose
DE=docker compose exec -T app
DR=docker compose exec -T rabbitmq

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		.env.dist >> .env; \

build:
	docker buildx build --pull --push --platform linux/amd64,linux/arm64/v8 -t $(IMAGE) .

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g; s/^(\s+- \$$\{GOPATH\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

init-dev: docker-up-force

lint:
	$(DE) go fmt ./...
	excludes='';\
	for file in $$(ls -R $$(find . -type f ) | grep test.go); do\
		excludes="$${excludes} -exclude $$(echo $${file} | cut -c 3-)";\
	done;\
	$(DE) revive -config config.toml $${excludes} -formatter friendly ./...

fasttest: lint
	$(DE) mkdir var || true
	$(DE) go test -p 8 --failfast -cover -coverpkg=./... -coverprofile var/coverage.out ./...
	$(DE) go tool cover -html=var/coverage.out -o var/coverage.html

test: init-dev fasttest docker-down-clean

ci-test: test

run-demo-counter:
	docker compose -f ../clients/demo/docker-compose.yml up -d --force-recreate multi-counter
	docker compose -f ../clients/demo/docker-compose.yml logs -f multi-counter
