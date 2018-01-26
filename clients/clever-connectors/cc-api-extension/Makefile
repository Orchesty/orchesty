PHONY: init test phpunit phpstan codesniffer

# Docker
DR= docker pull dkr.hanaboso.net/pipes/pipes/cc-api-extension:dev && docker run --rm --name php -v "$(shell pwd)":/var/www/html dkr.hanaboso.net/pipes/pipes/cc-api-extension:dev

build:
	docker build --build-arg DEV_USERNAME=$(shell id -un) --build-arg DEV_UID=$(shell id -u) -t dkr.hanaboso.net/pipes/pipes/cc-api-extension:dev .
	docker push dkr.hanaboso.net/pipes/pipes/cc-api-extension:dev

init: build composer-install

# Composer
composer-install:
	$(DR) composer install

composer-update:
	$(DR) composer update

# Test tools
codesniffer:
	$(DR) php ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p src/ tests/

phpstan:
	$(DR) php ./vendor/bin/phpstan analyse -l 7 src/
	$(DR) php ./vendor/bin/phpstan analyse -l 4 tests/

phpunit:
	$(DR) php ./vendor/bin/phpunit --color tests

removeCache:
	rm -rf temp || true
	mkdir temp
	chmod -R 777 temp

test: removeCache codesniffer phpstan phpunit removeCache