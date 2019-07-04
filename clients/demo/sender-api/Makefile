.PHONY: docker-up docker-up-force .env composer-install composer-update codesniffer phpstan phpunit init test

FILE?=docker-compose.ci.yml
DC=docker-compose -f $(FILE)
DE=docker-compose exec -T notification-sender-api
DEC=docker-compose exec -T notification-sender-api composer

.env:
	@if ! [ -f .env ]; then \
		sed -e "s/{DEV_UID}/$(shell id -u)/g" \
			-e "s/{DEV_GID}/$(shell id -u)/g" \
			.env.dist >> .env; \
	fi;

docker-down-clean: .env
	$(DC) down -v

docker-up: .env
	$(DC) up -d

docker-up-force: .env
	$(DC) up -d --force-recreate --remove-orphans

composer-install:
	$(DEC) install --ignore-platform-reqs

composer-update:
	$(DEC) update --ignore-platform-reqs

clear-cache:
	$(DE) sudo rm -rf var/cache
	$(DE) php bin/console cache:warmup --env=test

codesniffer:
	$(DE) php ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p src/

phpstan:
	$(DE) php ./vendor/bin/phpstan analyse -c ./phpstan.neon -l 7 src/

init: docker-up-force composer-install

test: init fasttest

fasttest: clear-cache codesniffer phpstan

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml
