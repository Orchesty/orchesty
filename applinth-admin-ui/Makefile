DC=docker-compose -f docker-compose.test.yml
DCU=$(DC) exec -T ui

IMAGE=dkr.hanaboso.net/pipes/pipes/applinth-admin-ui:$(TAG)

ALIAS?=alias
Darwin:
	sudo ifconfig lo0 $(ALIAS) $(shell awk '$$1 ~ /^DEV_IP/' .env.dist | sed -e "s/^DEV_IP=//")
Linux:
	@echo 'skipping ...'
.lo0-up:
	-@make `uname`
.lo0-down:
	-@make `uname` ALIAS='-alias'
.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		.env.dist > .env

docker-compose.ci.yml:
	# Comment out any port
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.test.yml > docker-compose.ci.yml

init-test: .env
	$(DC) up -d --force-recreate
	$(DCU) pnpm install

init: .env .lo0-up
	docker-compose pull --ignore-pull-failures
	docker-compose up -d --force-recreate --remove-orphans

start-mock: init
	docker-compose exec applinth-admin-ui pnpm run api:mock

down: .lo0-down
	docker-compose down -v

rebuild:
	docker buildx build --platform=linux/amd64 -f Dockerfile -t ${IMAGE} --pull --push .

generate-fixture-data: init
	docker-compose exec console-api pnpm run create-fixture-data

test:
	$(DCU) pnpm run validate

ci-test: init-test test
