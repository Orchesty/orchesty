.PHONY: init-dev init-prod .env generate

DC_DEV=docker-compose -f docker-compose.dev.yml
DE_DEV=docker-compose -f docker-compose.dev.yml exec -T
DC_PROD=docker-compose -f docker-compose.prod.yml
DE_PROD=docker-compose -f docker-compose.prod.yml exec -T

.env:
	@if ! [ -f .env ]; then \
		sed -e "s/{DEV_UID}/$(shell id -u)/g" \
			-e "s/{DEV_GID}/$(shell id -u)/g" \
			.env.dist >> .env; \
	fi;

init-dev: .env
	$(DC_DEV) up -d --force-recreate
	$(DE_DEV) app composer global require hirak/prestissimo
	$(DE_DEV) app composer install --ignore-platform-reqs
	#$(DE_DEV) magento install-sampledata
	$(DE_DEV) magento install-magento

composer-update-dev:
	$(DE_DEV) app composer global require hirak/prestissimo
	$(DE_DEV) app composer update --ignore-platform-reqs

init-prod: .env
	$(DC_DEV) up -d --force-recreate
	$(DE_DEV) app composer install --no-dev --optimize-autoloader --ignore-platform-reqs

generate:
	curl -X POST -H content-type=application/json http://127.0.0.66/api/gateway/test/topology/generate