.PHONY: docker-up-force docker-down-clean test

DC=docker-compose
DE=docker-compose exec -T app
DEC=docker-compose exec -T app composer

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
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo '${SSH_AUTH_SOCK}' | sed 's/\//\\\//g'; else echo '\/run\/host-services\/ssh-auth.sock'; fi)/g" \
		.env.dist > .env; \

docker-down-clean: .env .lo0-down
	$(DC) down -v

docker-up-force: .env .lo0-up
	$(DC) up -d --force-recreate --remove-orphans

composer-install:
	$(DEC) install

composer-update:
	$(DEC) update
	$(DEC) update --dry-run roave/security-advisories
	$(DEC) normalize

composer-outdated:
	$(DE) composer outdated

clear-cache:
	$(DE) rm -rf var/log
	$(DE) php bin/console cache:clear --env=test
	$(DE) php bin/console cache:warmup --env=test

phpcodesniffer:
	$(DE) php ./vendor/bin/phpcs --parallel=$$(nproc) --standard=./tests/ruleset.xml src tests

phpstan:
	$(DE) php ./vendor/bin/phpstan analyse -c ./tests/phpstan.neon -l 8 src tests

phpintegration:
	$(DE) php ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --colors=always tests/Integration

phpcontroller:
	$(DE) php ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --colors=always tests/Controller

phpcoverage:
	$(DE) ./vendor/bin/paratest -c vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --coverage-html var/coverage --whitelist src --exclude-group live tests

phpcoverage-ci:
	$(DE) ./vendor/hanaboso/php-check-utils/bin/coverage.sh -c 40 -p $$(nproc) -e live

phplive:
	$(DE) ./vendor/bin/phpunit -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist tests/Live

init: docker-up-force composer-install

test: init fasttest

fasttest: clear-cache phpcodesniffer phpstan phpintegration phpcontroller phpcoverage-ci

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml
