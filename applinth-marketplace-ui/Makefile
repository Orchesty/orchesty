DC=docker-compose -f docker-compose.test.yml
DCU=$(DC) exec -T ui

IMAGE?=dkr.hanaboso.net/pipes/pipes/applinth-marketplace-ui

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

init-test: .env .lo0-up
	$(DC) up -d --force-recreate
	$(DCU) pnpm install

init: .env .lo0-up
	docker-compose up -d --force-recreate
	docker-compose exec app bin/console service:install node-sdk node-sdk:8080
	docker-compose exec app bin/console api-token:create --key "$(shell grep 'ORCHESTY_API_KEY' .env | cut -d "=" -f2)"

rebuild:
	docker build -t ${IMAGE}:${TAG} --pull .
	docker push ${IMAGE}:${TAG}

generate-link:
	echo "http://localhost:8080/?u=$$(node JWEGen.js)"

test:
	$(DCU) pnpm run validate

ci-test: init-test test
