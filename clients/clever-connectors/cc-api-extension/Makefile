PHONY: init test phpunit phpstan codesniffer

# Docker
DR=docker run --rm --name php -v "$(shell pwd)":/var/www/html php-image

build:
	docker build --build-arg DEV_USERNAME=$(shell id -un) --build-arg DEV_UID=$(shell id -u) -t php-image .

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

test: codesniffer phpstan phpunit phpintegration