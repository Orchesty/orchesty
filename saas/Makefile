DCS=docker-compose exec -T console-api
DCB=docker-compose exec -T applinth-billing-processor

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		.env.dist > .env; \

docker-up-force: .env
	docker-compose pull --ignore-pull-failures
	docker-compose up -d --force-recreate --remove-orphans

docker-down-clean: .env

init: docker-up-force
	$(DCS) sed -i -e "s/JSON.parse(data)/require('js-yaml').load(data)/g" node_modules/.pnpm/json-schema-deref-sync@0.14.0/node_modules/json-schema-deref-sync/lib/loaders/file.js

create-fixture-data:
	$(DCS) pnpm run create-fixture-data

generate-usage-stats:
	$(DCB) pnpm generate-usage-stats
