.PHONY: docker-up-force docker-down-clean test

TAG?=dev
IMAGE=dkr.hanaboso.net/pipes/pipes/php-sdk/php-dev:${TAG}
BASE=hanabosocom/php-dev:php-7.4-alpine
DC=docker-compose
DE=docker-compose exec -T app
DEC=docker-compose exec -T app composer

# Docker
docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans
	sleep 10

docker-down-clean: .env
	$(DC) down -v

#Composer
composer-install:
	$(DEC) install --no-suggest

composer-update:
	$(DEC) update --no-suggest

composer-outdated:
	$(DEC) outdated

# App
init: .env docker-up-force composer-install

#CI
phpcodesniffer:
	$(DE) vendor/bin/phpcs --standard=tests/ruleset.xml src tests

phpstan:
	$(DE) vendor/bin/phpstan analyse -c tests/phpstan.neon -l 8 src tests

phpunit:
	$(DE) vendor/bin/phpunit -c vendor/hanaboso/php-check-utils/phpunit.xml.dist tests/Unit

phpcontroller:
	$(DE) vendor/bin/phpunit -c vendor/hanaboso/php-check-utils/phpunit.xml.dist tests/Controller

phpintegration:
	$(DE) vendor/bin/phpunit -c vendor/hanaboso/php-check-utils/phpunit.xml.dist tests/Integration

phpcoverage:
	$(DE) vendor/bin/paratest -c vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 8 --coverage-html var/coverage --whitelist src tests

phpcoverage-ci:
	$(DE) vendor/hanaboso/php-check-utils/bin/coverage.sh

phpmanual-up:
	cd tests/Manual; $(MAKE) docker-up-force;

phpmanual-tests:
	$(DE) ./vendor/bin/phpunit -c vendor/hanaboso/php-check-utils/phpunit.xml.dist  tests/Manual

phpmanual-down:
	cd tests/Manual; $(MAKE) docker-down-clean;

test: docker-up-force composer-install fasttest

fasttest: phpcodesniffer clear-cache phpstan phpunit phpintegration phpcontroller phpcoverage-ci

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

#Other
console:
	$(DE) php bin/console ${command}

clear-cache:
	$(DE) rm -rf var/log
	$(DE) php tests/bin/console cache:clear --env=test
	$(DE) php tests/bin/console cache:warmup --env=test

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo '${SSH_AUTH_SOCK}' | sed 's/\//\\\//g'; else echo '\/run\/host-services\/ssh-auth.sock'; fi)/g" \
		.env.dist >> .env; \
