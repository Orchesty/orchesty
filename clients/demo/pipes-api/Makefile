.PHONY: docker-up-force docker-down-clean test

DC=docker-compose
DE=docker-compose exec -T app
DEC=docker-compose exec -T app composer

.env:
	sed -e "s/{DEV_UID}/$(shell id -u)/g" \
		-e "s/{DEV_GID}/$(shell id -u)/g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo '${SSH_AUTH_SOCK}' | sed 's/\//\\\//g'; else echo '\/run\/host-services\/ssh-auth.sock'; fi)/g" \
		.env.dist >> .env; \

docker-down-clean: .env
	$(DC) down -v

docker-up-force: .env
	$(DC) up -d --force-recreate --remove-orphans

composer-install:
	$(DEC) install --no-suggest

composer-update:
	$(DEC) update --no-suggest

composer-outdated:
	$(DE) composer outdated

clear-cache:
	$(DE) rm -rf var/log
	$(DE) php bin/console cache:clear --env=test
	$(DE) php bin/console cache:warmup --env=test

phpcodesniffer:
	$(DE) php ./vendor/bin/phpcs --standard=./tests/ruleset.xml src tests

phpstan:
	$(DE) php ./vendor/bin/phpstan analyse -c ./tests/phpstan.neon -l 8 src tests

phpintegration:
	$(DE) php ./vendor/bin/phpunit -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist --colors tests/Integration

phpcontroller:
	$(DE) php ./vendor/bin/phpunit -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist --colors tests/Controller

phpcoverage:
	$(DE) ./vendor/bin/paratest -c vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --coverage-html var/coverage --whitelist src tests

phpcoverage-ci:
	$(DE) ./vendor/hanaboso/php-check-utils/bin/coverage.sh -c 50

phplive:
	$(DE) ./vendor/bin/phpunit -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist tests/Live

init: docker-up-force composer-install

test: init fasttest

fasttest: clear-cache phpcodesniffer phpstan phpintegration phpcontroller phpcoverage-ci

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml
