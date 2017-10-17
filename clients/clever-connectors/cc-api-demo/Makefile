.PHONY: init

DC=docker-compose
DE=docker-compose exec webserver

.env:
	@if ! [ -f .env ]; then \
		sed -e "s/{DEV_UID}/$(shell id -u)/g; s/{DEV_USERNAME}/$(shell id -un)/g;" .env.dist > .env; \
	fi


init: .env
	$(DC) build
	$(DC) up -d --force-recreate
	$(DE) composer install