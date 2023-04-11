DCS=docker-compose exec -T console-api
DCB=docker-compose exec -T applinth-billing-processor

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
		.env.dist > .env; \

docker-up-force: .env .lo0-up
	docker-compose pull --ignore-pull-failures
	docker-compose up -d --force-recreate --remove-orphans

docker-down-clean: .env .lo0-down

init: docker-up-force
	$(DCS) sed -i -e "s/JSON.parse(data)/require('js-yaml').load(data)/g" node_modules/.pnpm/json-schema-deref-sync@0.14.0/node_modules/json-schema-deref-sync/lib/loaders/file.js

create-fixture-data:
	$(DCS) pnpm run create-fixture-data

run-command:
	$(DCB) pnpm run-command
