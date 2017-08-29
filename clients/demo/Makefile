.PHONY: init-dev init-prod .env

DC_DEV=docker-compose -f docker-compose.dev.yml
DE_DEV=docker-compose -f docker-compose.dev.yml exec -T
DC_PROD=docker-compose -f docker-compose.prod.yml
DE_PROD=docker-compose -f docker-compose.prod.yml exec -T app

.env:
	@if ! [ -f .env ]; then \
		sed -e "s/{DEV_UID}/$(shell id -u)/g" \
			-e "s/{DEV_GID}/$(shell id -u)/g" \
			.env.dist >> .env; \
	fi;

init-dev: .env
	$(DC_DEV) up -d --force-recreate
	$(DE_DEV) app composer install --ignore-platform-reqs
	$(DE_DEV) magento install-sampledata
	#$(DE_DEV) magento /usr/local/bin/install-magento

init-prod: .env
	$(DC_DEV) up -d --force-recreate
	$(DE_DEV) composer install --no-dev --optimize-autoloader --ignore-platform-reqs