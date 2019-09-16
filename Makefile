.PHONY: docker-up-force docker-down-clean test

DC=docker-compose
DE=docker-compose exec -T app
IMAGE=dkr.hanaboso.net/pipes/notification-sender
BASE=dkr.hanaboso.net/hanaboso/php-base:php-7.3

.env:
	sed -e "s/{DEV_UID}/$(shell id -u)/g" \
		-e "s/{DEV_GID}/$(shell id -u)/g" \
		.env.dist >> .env; \

prod-build: .env
	docker pull $(IMAGE):dev
	docker-compose -f docker-compose.yml run --rm --no-deps app  composer install --ignore-platform-reqs
	docker build -t $(IMAGE):master .
	docker push $(IMAGE):master

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

# Docker
docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

# Composer
composer-install:
	$(DE) composer install --ignore-platform-reqs

composer-update:
	$(DE) composer update --ignore-platform-reqs

clear-cache:
	$(DE) sudo rm -rf var
	$(DE) bin/console cache:warmup --env=test

# App
init-dev: docker-up-force composer-install

create-index:
	$(DE) bin/console d:m:s:c --index

phpcodesniffer:
	$(DE) vendor/bin/phpcs -p --standard=ruleset.xml --colors src tests

phpstan:
	$(DE) vendor/bin/phpstan analyse -c phpstan.neon -l 7 --memory-limit=512M src tests

phpintegration:
	$(DE) vendor/bin/paratest -c phpunit.xml.dist -p 4 --colors tests/Integration

phpcontroller:
	$(DE) vendor/bin/paratest -c phpunit.xml.dist -p 4 --colors tests/Controller

test: docker-up-force composer-install fasttest

fasttest: phpcodesniffer clear-cache phpstan phpintegration phpcontroller
