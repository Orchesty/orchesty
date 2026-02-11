IMAGE=orchesty/bridge:$(TAG)

DC=docker-compose
DE=docker-compose exec -T app
DR=docker-compose exec -T rabbitmq

TEST_IMAGE = bridge:test
NEW_UI_IMAGE = dkr.hanaboso.net/pipes/pipes/pf-bridge:new-ui
TOPOLOGY_ID = 690e1281a439e5b99905ffa3
TOPOLOGY_NAME = benchmark

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		.env.dist >> .env; \

build:
	docker buildx build --pull --push --platform linux/amd64,linux/arm64/v8 -t $(IMAGE) .

build-new-ui:
	docker buildx build --pull --push --platform linux/amd64,linux/arm64/v8 -t $(NEW_UI_IMAGE) .

docker-up-force: .env

docker-up-force: .env .lo0-up
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g; s/^(\s+- \$$\{GOPATH\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

init-dev: docker-up-force
	sleep 10
	$(DR) rabbitmq-plugins enable rabbitmq_consistent_hash_exchange
	sleep 10

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

run-demo-bridge:
	docker build -t $(TEST_IMAGE) .
	docker compose -f ../clients/topology/$(TOPOLOGY_ID)-$(TOPOLOGY_NAME)/docker-compose.yml up -d --force-recreate
	docker compose -f ../clients/topology/$(TOPOLOGY_ID)-$(TOPOLOGY_NAME)/docker-compose.yml logs -f topology-$(TOPOLOGY_ID)
